<?php

namespace Tests\Feature\Identity;

use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyIdentityStorageTest extends TestCase
{
    public function test_guest_parent_temp_preview_is_session_bound_and_private(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $upload = $this->postJson(route('parent.register.temp-upload'), [
            'government_id' => UploadedFile::fake()->createWithContent('identity.pdf', 'parent-id')->mimeType('application/pdf'),
        ])->assertOk()->json('upload');
        $path = $upload['path'];
        $this->assertSame(
            route('registration.temp-document.preview', ['parent', 'government_id']),
            $upload['preview_url']
        );
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $preview = $this->get($upload['preview_url']);

        $preview->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(
            'parent-id',
            file_get_contents($preview->baseResponse->getFile()->getPathname())
        );

        $this->flushSession()
            ->get(route('registration.temp-document.preview', ['parent', 'government_id']))
            ->assertNotFound();
    }

    public function test_child_temp_preview_requires_the_owning_approved_guardian_session(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $owner = $this->createApprovedGuardian();
        $other = $this->createApprovedGuardian();
        $path = 'registration-temp/child/verification_document/proof.pdf';
        Storage::disk('local')->put($path, 'child-document');
        $session = ['registration_temp_uploads.child.verification_document' => $this->metadata($path)];

        $this->actingAs($owner)
            ->withSession($session)
            ->get(route('registration.temp-document.preview', ['child', 'verification_document']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($other)
            ->flushSession()
            ->get(route('registration.temp-document.preview', ['child', 'verification_document']))
            ->assertNotFound();

        $unapproved = User::factory()->create();
        $unapproved->assignRole('learner');
        $this->actingAs($unapproved)
            ->withSession($session)
            ->get(route('registration.temp-document.preview', ['child', 'verification_document']))
            ->assertForbidden();
    }

    public function test_admin_child_preview_uses_record_path_and_never_generates_public_asset_url(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->createAdmin();
        [$parent, $verification] = $this->createChildVerification('child-verifications/parent/legacy-proof.pdf');
        Storage::disk('public')->put($verification->verification_document_path, 'legacy-child-document');

        $preview = $this->actingAs($admin)
            ->get(route('admin.parent-verifications.children.document', $verification))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame(
            'legacy-child-document',
            file_get_contents($preview->baseResponse->getFile()->getPathname())
        );

        Storage::disk('local')->put($verification->verification_document_path, 'private-child-document');
        $privatePreview = $this->actingAs($admin)
            ->get(route('admin.parent-verifications.children.document', $verification))
            ->assertOk();
        $this->assertSame(
            'private-child-document',
            file_get_contents($privatePreview->baseResponse->getFile()->getPathname())
        );

        $queue = $this->actingAs($admin)->get(route('admin.parent-verifications.index', ['type' => 'children']));
        $queue->assertOk()
            ->assertSee(route('admin.parent-verifications.children.document', $verification), false)
            ->assertDontSee(asset('storage/'.$verification->verification_document_path), false);

        $learner = User::factory()->create();
        $learner->assignRole('learner');
        $this->actingAs($learner)
            ->get(route('admin.parent-verifications.children.document', $verification))
            ->assertForbidden();
    }

    public function test_migration_command_dry_run_apply_and_second_run_are_safe(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $path = 'registration-temp/child/verification_document/proof.pdf';
        Storage::disk('public')->put($path, 'document-bytes');

        $this->artisan('identity:move-legacy-documents')
            ->expectsOutputToContain('candidates=1 ready=1 moved=0')
            ->assertExitCode(0);
        Storage::disk('public')->assertExists($path);
        Storage::disk('local')->assertMissing($path);

        $this->artisan('identity:move-legacy-documents --apply')
            ->expectsOutputToContain('moved=1')
            ->assertExitCode(0);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
        $this->assertSame(
            hash('sha256', 'document-bytes'),
            hash_file('sha256', Storage::disk('local')->path($path))
        );

        $this->artisan('identity:move-legacy-documents')
            ->expectsOutputToContain('candidates=0 ready=0 moved=0')
            ->assertExitCode(0);
    }

    public function test_migration_command_inventories_database_references_and_rejects_unsafe_paths(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $parentPath = 'parent-verifications/parent/front.pdf';
        $backPath = 'parent-verifications/parent/back.pdf';
        $childPath = 'child-verifications/parent/child.pdf';
        $relationshipPath = 'guardian-verifications/parent/relationship.pdf';
        foreach ([$parentPath, $backPath, $childPath, $relationshipPath] as $path) {
            Storage::disk('public')->put($path, $path);
        }

        $parent = User::factory()->create([
            'is_parent_registration' => true,
            'parent_id_document_path' => $parentPath,
            'parent_id_document_back_path' => $backPath,
        ]);
        $parent->assignRole('learner');
        $child = User::factory()->create();
        $child->assignRole('learner');
        $verification = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'verification_document_path' => $childPath,
        ]);
        GuardianRelationshipVerificationDocument::query()->create([
            'parent_child_account_id' => $verification->id,
            'uploaded_by_user_id' => $parent->id,
            'document_type' => 'care_arrangement',
            'disk' => 'public',
            'path' => $relationshipPath,
            'original_name' => 'relationship.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($relationshipPath),
        ]);
        $unsafeParent = User::factory()->create([
            'is_parent_registration' => true,
            'parent_id_document_path' => '../outside/identity.pdf',
        ]);

        $this->artisan('identity:move-legacy-documents')
            ->expectsOutputToContain('candidates=4 ready=4 moved=0 missing=0 conflicts=0 unsafe=1')
            ->assertExitCode(0);
        foreach ([$parentPath, $backPath, $childPath, $relationshipPath] as $path) {
            Storage::disk('public')->assertExists($path);
            Storage::disk('local')->assertMissing($path);
        }
        $this->assertSame('../outside/identity.pdf', $unsafeParent->parent_id_document_path);

        $this->artisan('identity:move-legacy-documents --apply')
            ->expectsOutputToContain('candidates=4 ready=4 moved=4 missing=0 conflicts=0 unsafe=1')
            ->assertExitCode(0);
        foreach ([$parentPath, $backPath, $childPath, $relationshipPath] as $path) {
            Storage::disk('public')->assertMissing($path);
            Storage::disk('local')->assertExists($path);
            $this->assertSame($path, Storage::disk('local')->get($path));
        }
    }

    public function test_migration_command_preserves_conflicts_and_reports_missing_files(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $conflict = 'child-verifications/parent/conflict.pdf';
        $missing = 'child-verifications/parent/missing.pdf';
        Storage::disk('public')->put($conflict, 'public-version');
        Storage::disk('local')->put($conflict, 'private-version');
        $parent = $this->createApprovedGuardian();
        $firstChild = User::factory()->create();
        $firstChild->assignRole('learner');
        $secondChild = User::factory()->create();
        $secondChild->assignRole('learner');
        foreach ([[$firstChild, $conflict], [$secondChild, $missing]] as [$child, $path]) {
            ParentChildAccount::query()->create([
                'parent_user_id' => $parent->id,
                'child_user_id' => $child->id,
                'can_view_progress' => true,
                'can_view_quiz_answers' => true,
                'can_approve_content' => false,
                'verification_document_path' => $path,
            ]);
        }

        $this->artisan('identity:move-legacy-documents --apply')
            ->expectsOutputToContain('conflicts=1 unsafe=0 failures=0')
            ->assertExitCode(0);

        $this->assertSame('private-version', Storage::disk('local')->get($conflict));
        $this->assertSame('public-version', Storage::disk('public')->get($conflict));
        Storage::disk('local')->assertMissing($missing);
        Storage::disk('public')->assertMissing($missing);
    }

    private function metadata(string $path): array
    {
        return [
            'path' => $path,
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12,
            'disk' => 'local',
        ];
    }

    private function createApprovedGuardian(): User
    {
        $guardian = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');

        return $guardian;
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    /** @return array{User, ParentChildAccount} */
    private function createChildVerification(string $path): array
    {
        $parent = $this->createApprovedGuardian();
        $child = User::factory()->create();
        $child->assignRole('learner');
        $verification = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'verification_status' => 'pending',
            'verification_document_path' => $path,
        ]);

        return [$parent, $verification];
    }
}
