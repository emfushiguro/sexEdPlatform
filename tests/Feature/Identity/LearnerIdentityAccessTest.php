<?php

namespace Tests\Feature\Identity;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerIdentityAccessTest extends TestCase
{
    private function learner(bool $verified = true, string $birthdate = '2000-01-01', bool $covered = true): array
    {
        $user = User::factory()->create([
            'role' => 'learner', 'birthdate' => $birthdate,
            'email_verified_at' => $verified ? now() : null,
        ]);
        $user->assignRole('learner');

        return [$user, $covered ? app(LearnerIdentityRequirement::class)->createForNewLearner($user) : null];
    }

    public function test_covered_learner_is_routed_through_submission_review_and_approval(): void
    {
        [$user, $case] = $this->learner();
        $this->actingAs($user)->get(route('profile.complete'))->assertRedirect(route('learner.identity.create'));
        $this->get(route('learner.identity.create'))->assertOk()->assertSee('Identity Verification');
        $case->update(['status' => 'pending']);
        $this->get(route('learner.dashboard'))->assertRedirect(route('learner.identity.status'));
        $this->get(route('learner.identity.status'))->assertOk()->assertSee('pending');
        $case->update(['status' => 'rejected', 'rejection_reason' => 'Please retake the image.']);
        $this->get(route('learner.identity.status'))->assertOk()->assertSee('Please retake the image.');
        $this->get(route('learner.identity.create'))->assertOk();
        $case->update(['status' => 'approved']);
        $this->get(route('learner.identity.status'))->assertOk()->assertSee('approved');
        $this->get(route('profile.complete'))->assertOk();
    }

    public function test_unverified_learner_is_sent_to_email_verification(): void
    {
        [$user] = $this->learner(false);
        $this->actingAs($user)->get(route('learner.identity.create'))->assertRedirect(route('verification.notice'));
        $this->get(route('profile.complete'))->assertRedirect(route('verification.notice'));
    }

    public function test_pending_learner_cannot_bypass_gate_through_other_web_route_groups(): void
    {
        [$user, $case] = $this->learner();
        $case->update(['status' => 'pending']);
        $this->actingAs($user);

        foreach (['profile.complete', 'learner.dashboard', 'chat.page', 'seminars.index', 'subscription.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('learner.identity.status'));
        }
    }

    public function test_pending_learner_can_reach_verification_privacy_password_and_account_deletion(): void
    {
        [$user, $case] = $this->learner();
        $case->update(['status' => 'pending']);
        $this->actingAs($user);

        $this->get(route('verification.notice'))->assertRedirect(route('learner.identity.status'));
        $this->get(route('privacy'))->assertOk();
        $this->get(route('password.confirm'))->assertOk();
        $this->deleteJson(route('profile.account.delete'))->assertStatus(403);
    }

    public function test_legacy_learner_and_guardian_created_child_are_exempt(): void
    {
        [$legacy] = $this->learner(covered: false);
        $this->actingAs($legacy)->get(route('profile.complete'))->assertOk();

        $guardian = User::factory()->create([
            'role' => 'learner', 'is_parent_registration' => true,
            'parent_verification_status' => 'approved', 'email_verified_at' => now(),
        ]);
        $guardian->assignRole('learner');
        [$child] = $this->learner(birthdate: '2016-01-01', covered: false);
        $relationship = ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'verification_status' => ParentChildAccount::VERIFICATION_PENDING,
            'verification_document_path' => 'child-verifications/temp/dependent-id.png',
        ]);

        $this->assertSame($child->id, $relationship->child_user_id);
        $this->assertFalse($child->identityVerifications()->exists());
        $this->actingAs($child)->get(route('profile.complete'))->assertOk();
        $this->get(route('learner.dashboard'))->assertRedirect(route('child.verification.status'));
    }

    public function test_age_correction_below_thirteen_renders_support_hold_without_loop(): void
    {
        [$user] = $this->learner();
        $user->update(['birthdate' => '2016-01-01']);
        $this->actingAs($user)->get(route('learner.dashboard'))->assertRedirect(route('learner.identity.status'));
        $this->get(route('learner.identity.status'))->assertOk()->assertSee('support');
        $this->get(route('learner.identity.create'))->assertRedirect(route('learner.identity.status'));
    }

    public function test_missing_birthdate_on_existing_case_renders_support_hold_without_loop(): void
    {
        [$user, $case] = $this->learner();
        $user->update(['birthdate' => null]);

        $this->assertSame($user->id, $case->user_id);
        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertRedirect(route('learner.identity.status'));
        $this->get(route('learner.identity.status'))
            ->assertOk()->assertSee('guardian support')->assertSee('contact support');
        $this->get(route('learner.identity.create'))
            ->assertRedirect(route('learner.identity.status'));
    }

    public function test_identity_routes_require_an_owned_case(): void
    {
        [$legacy] = $this->learner(covered: false);
        $this->actingAs($legacy)->get(route('learner.identity.create'))->assertForbidden();
        $this->get(route('learner.identity.selfie.create'))->assertForbidden();
        $this->get(route('learner.identity.status'))->assertForbidden();
        $this->post(route('learner.identity.document.store'), [])->assertForbidden();
        $this->post(route('learner.identity.store'), [])->assertForbidden();
    }

    public function test_document_step_stages_id_privately_before_selfie_submission(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        [$user, $case] = $this->learner();

        $this->actingAs($user)->get(route('learner.identity.create'))
            ->assertOk()
            ->assertSee('name="id_selection"', false)
            ->assertSee('data-testid="learner-id-front-preview"', false)
            ->assertDontSee('name="selfie"', false);

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
            'identity_front' => $this->image('front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));

        $tempPath = session('registration_temp_uploads.learner.identity_front.path');
        $this->assertIsString($tempPath);
        Storage::disk('local')->assertExists($tempPath);
        $this->assertNull($case->fresh()->status);
        $this->assertSame(0, $case->evidence()->count());

        $this->get(route('learner.identity.selfie.create'))
            ->assertOk()
            ->assertSee('data-testid="selfie-capture"', false)
            ->assertSee('Take a selfie');
        $this->post(route('learner.identity.store'), [
            'selfie' => $this->image('selfie.png'),
            'confirm_submission' => '1',
        ])->assertRedirect(route('learner.identity.status'));

        $submitted = $case->fresh('evidence');
        $this->assertSame('pending', $submitted->status);
        $this->assertEqualsCanonicalizing(['identity_front', 'selfie'], $submitted->evidence->pluck('slot')->all());
        Storage::disk('local')->assertMissing($tempPath);
        Storage::disk('public')->assertMissing($submitted->evidence->pluck('storage_path')->all());
    }

    public function test_selfie_step_requires_an_owned_document_draft(): void
    {
        [$user, $case] = $this->learner();

        $this->actingAs($user)->get(route('learner.identity.selfie.create'))
            ->assertRedirect(route('learner.identity.create'));
        $this->post(route('learner.identity.store'), [
            'selfie' => $this->image('selfie.png'),
            'confirm_submission' => '1',
        ])->assertRedirect(route('learner.identity.create'));
        $this->assertNull($case->fresh()->status);
    }

    public function test_id_choice_controls_back_requirement_and_saved_images_can_be_reused(): void
    {
        Storage::fake('local');
        [$user, $case] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:national_id',
            'identity_front' => $this->image('front.png'),
        ])->assertSessionHasErrors(['identity_back']);
        $this->assertNull(session('learner_identity_document_draft'));

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:national_id',
            'identity_front' => $this->image('front.png'),
            'identity_back' => $this->image('back.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $frontPath = session('registration_temp_uploads.learner.identity_front.path');
        $backPath = session('registration_temp_uploads.learner.identity_back.path');

        $this->get(route('learner.identity.create'))->assertOk()->assertSee('Saved image available');
        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:national_id',
        ])->assertRedirect(route('learner.identity.selfie.create'));
        Storage::disk('local')->assertExists([$frontPath, $backPath]);

        $this->post(route('learner.identity.store'), [
            'selfie' => $this->image('selfie.png'),
            'confirm_submission' => '1',
        ])->assertRedirect(route('learner.identity.status'));
        $this->assertEqualsCanonicalizing(['identity_front', 'identity_back', 'selfie'], $case->fresh()->evidence->pluck('slot')->all());
    }

    public function test_id_choice_cannot_be_changed_without_a_new_front_image(): void
    {
        Storage::fake('local');
        [$user, $case] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
            'identity_front' => $this->image('front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:passport',
            'identity_back' => $this->image('back.png'),
        ])->assertSessionHasErrors(['identity_front']);
        $this->assertSame('government_id:philhealth', session('learner_identity_document_draft.id_selection'));
        $this->assertNull($case->fresh()->status);
    }

    public function test_other_government_id_requires_description_and_back_image(): void
    {
        [$user, $case] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:other',
            'identity_front' => $this->image('front.png'),
        ])->assertSessionHasErrors(['government_id_type_other', 'identity_back']);
        $this->assertNull($case->fresh()->status);

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:other',
            'government_id_type_other' => 'Government-issued service card',
            'identity_front' => $this->image('front.png'),
            'identity_back' => $this->image('back.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $this->post(route('learner.identity.store'), [
            'selfie' => $this->image('selfie.png'),
            'confirm_submission' => '1',
        ])->assertRedirect(route('learner.identity.status'));
        $this->assertSame('other', $case->fresh()->government_id_type);
        $this->assertSame('Government-issued service card', $case->fresh()->government_id_type_other);
    }

    public function test_post_requires_images_and_keeps_case_unsubmitted(): void
    {
        [$user, $case] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
        ])->assertSessionHasErrors(['identity_front']);
        $this->assertNull($case->fresh()->status);

        $this->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
            'identity_front' => $this->image('front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $this->post(route('learner.identity.store'), [
            'confirm_submission' => '1',
        ])->assertSessionHasErrors(['selfie']);
        $this->assertNull($case->fresh()->status);
    }

    public function test_selfie_form_offers_camera_upload_guidance_and_accessible_controls(): void
    {
        [$user] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
            'identity_front' => $this->image('front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $this->get(route('learner.identity.selfie.create'))
            ->assertOk()
            ->assertSee('data-testid="selfie-capture"', false)
            ->assertSee('Take a selfie')
            ->assertSee('Upload a selfie')
            ->assertSee('Use Photo')
            ->assertSee('Retake')
            ->assertSee('Replace')
            ->assertSee('Cancel')
            ->assertSee('aria-live="polite"', false)
            ->assertSee('playsinline', false)
            ->assertSee('manual review');
    }

    public function test_submission_uploads_to_private_disk_and_moves_to_pending_status(): void
    {
        Storage::fake('local');
        [$user, $case] = $this->learner();
        $this->actingAs($user)->post(route('learner.identity.document.store'), [
            'id_selection' => 'government_id:philhealth',
            'identity_front' => $this->image('front.png'),
        ])->assertRedirect(route('learner.identity.selfie.create'));
        $this->post(route('learner.identity.store'), [
            'confirm_submission' => '1', 'selfie' => $this->image('selfie.png'),
        ])->assertRedirect(route('learner.identity.status'));
        $this->assertSame('pending', $case->fresh()->status);
        $this->assertCount(2, $case->fresh()->evidence);
    }

    public function test_unlinked_teen_is_governed_by_identity_review_and_linked_teen_keeps_relationship_hold(): void
    {
        [$teen, $case] = $this->learner(birthdate: now()->subYears(17)->toDateString());
        $this->actingAs($teen)->get(route('profile.complete'))->assertRedirect(route('learner.identity.create'));
        $case->update(['status' => 'approved']);
        $this->get(route('profile.complete'))->assertOk();

        $parent = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true]);
        $parent->assignRole('learner');
        ParentChildAccount::create([
            'parent_user_id' => $parent->id, 'child_user_id' => $teen->id,
            'relationship_type' => 'mother', 'relationship_status' => 'pending',
            'verification_status' => 'pending', 'verification_document_path' => 'private/pending.png',
        ]);
        $this->get(route('learner.dashboard'))->assertRedirect(route('child.verification.status'));
    }

    private function image(string $name): UploadedFile
    {
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $raw = str_repeat("\x00".str_repeat("\x00\x00\x00", 800), 600);
        $png = "\x89PNG\r\n\x1a\n".$chunk('IHDR', pack('NNCCCCC', 800, 600, 8, 2, 0, 0, 0)).$chunk('IDAT', gzcompress($raw)).$chunk('IEND', '');

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
