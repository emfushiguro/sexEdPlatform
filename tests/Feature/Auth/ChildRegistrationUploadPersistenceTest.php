<?php

namespace Tests\Feature\Auth;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\Admin\ChildVerificationRequestSubmittedNotification;
use App\Services\ParentChildVerificationService;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChildRegistrationUploadPersistenceTest extends TestCase
{
    public function test_child_relationship_verification_page_uses_one_initial_evidence_form_setup(): void
    {
        $parent = $this->createApprovedParent();
        $session = $this->childWizardSession();
        $session['child_step3'] = ['username' => 'single-row'];

        $this->actingAs($parent)
            ->withSession($session)
            ->get(route('parent.create-child.relationship-verification'))
            ->assertOk()
            ->assertSee('x-data="guardianEvidenceForm', false)
            ->assertSee('Add back side', false)
            ->assertDontSee('x-init="init()"', false);
    }

    public function test_child_credentials_submit_notifies_admins_about_new_child_verification_request(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $parent = $this->createApprovedParent();

        $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.pdf', 120, 'application/pdf'),
            ])->assertOk();

        $this->actingAs($parent)
            ->withSession($this->childWizardSession())
            ->post(route('parent.create-child.credentials.store'), [
                'username' => 'childnotifyadmin',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('parent.create-child.validation'));

        $this->actingAs($parent)
            ->post(route('parent.create-child.validation.store'))
            ->assertRedirect(route('parent.create-child.relationship-verification'));

        $this->submitRelationshipEvidence($parent, 'childnotifyrelationship');

        Notification::assertSentTo(
            [$admin],
            ChildVerificationRequestSubmittedNotification::class,
            fn (ChildVerificationRequestSubmittedNotification $notification) => data_get($notification->toDatabase($admin), 'status') === 'pending'
                && str_contains((string) data_get($notification->toDatabase($admin), 'action_url'), 'admin/parent-verifications')
        );
    }

    public function test_child_temp_upload_accepts_only_allowed_psa_document_types(): void
    {
        Storage::fake('public');

        $parent = $this->createApprovedParent();

        $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.txt', 20, 'text/plain'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['verification_document']);

        $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.pdf', 120, 'application/pdf'),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'upload' => [
                    'path',
                    'original_name',
                    'mime_type',
                    'size',
                    'preview_url',
                ],
            ]);
    }

    public function test_child_credentials_page_rehydrates_preview_from_temp_session(): void
    {
        Storage::fake('public');

        $parent = $this->createApprovedParent();

        $uploadResponse = $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.pdf', 120, 'application/pdf'),
            ])->assertOk();

        $path = $uploadResponse->json('upload.path');

        $this->assertSame($path, session('registration_temp_uploads.child.verification_document.path'));

        $this->actingAs($parent)
            ->withSession($this->childWizardSession())
            ->withSession(['child_step3' => ['username' => 'childpreview', 'password' => 'Password123!']])
            ->get(route('parent.create-child.validation'))
            ->assertOk()
            ->assertSee('data-testid="child-verification-preview"', false)
            ->assertSee('birth-cert.pdf', false);
    }

    public function test_child_temp_remove_and_replace_keep_session_metadata_in_sync(): void
    {
        Storage::fake('public');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $parent = $this->createApprovedParent();

        $firstUpload = $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
            ])->assertOk();

        $firstPath = $firstUpload->json('upload.path');

        $secondUpload = $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('second.pdf', 100, 'application/pdf'),
            ])->assertOk();

        $secondPath = $secondUpload->json('upload.path');

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
        $this->assertSame($secondPath, session('registration_temp_uploads.child.verification_document.path'));

        $this->actingAs($parent)
            ->deleteJson(route('parent.create-child.credentials.temp-upload.remove'))
            ->assertOk();

        Storage::disk('public')->assertMissing($secondPath);
        $this->assertNull(session('registration_temp_uploads.child.verification_document'));
    }

    public function test_child_credentials_submit_requires_preview_ready_temp_upload_state(): void
    {
        Storage::fake('public');

        $parent = $this->createApprovedParent();

        $this->actingAs($parent)
            ->withSession($this->childWizardSession())
            ->withSession(['child_step3' => ['username' => 'childwithoutdoc', 'password' => 'Password123!']])
            ->post(route('parent.create-child.validation.store'))
            ->assertSessionHasErrors(['verification_document']);
    }

    public function test_child_credentials_submit_finalizes_temp_upload_and_clears_session(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $parent = $this->createApprovedParent();

        $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.pdf', 120, 'application/pdf'),
            ])->assertOk();

        $this->actingAs($parent)
            ->withSession($this->childWizardSession())
            ->post(route('parent.create-child.credentials.store'), [
                'username' => 'childwithdoc',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])
            ->assertRedirect(route('parent.create-child.validation'));

        $this->actingAs($parent)
            ->post(route('parent.create-child.validation.store'))
            ->assertRedirect(route('parent.create-child.relationship-verification'));

        $this->submitRelationshipEvidence($parent, 'childwithrelationship');

        $link = DB::table('parent_child_accounts')
            ->where('parent_user_id', $parent->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($link);
        $this->assertNotEmpty($link->verification_document_path);
        $this->assertStringStartsWith('child-verifications/'.$parent->id.'/', $link->verification_document_path);
        Storage::disk('public')->assertExists($link->verification_document_path);
        $this->assertNull(session('registration_temp_uploads.child.verification_document'));
    }

    public function test_adoptive_registration_stores_multiple_relationship_documents_in_one_round(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $parent = $this->createApprovedParent();
        $this->completeChildWizardUntilRelationshipReview($parent, 'adoptive_parent', 'adoptivechild');

        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), [
                'documents' => [
                    ['document_type' => 'adoption_order', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => UploadedFile::fake()->createWithContent('adoption-order.pdf', 'adoption-order')->mimeType('application/pdf')],
                    ['document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => UploadedFile::fake()->createWithContent('support.pdf', 'supporting-evidence')->mimeType('application/pdf')],
                ],
                'confirm_submission' => '1',
            ])->assertRedirect(route('parent.create-child.support-information'));

        $relationship = ParentChildAccount::query()->latest('id')->firstOrFail();
        $this->assertTrue($relationship->can_manage_support_information);
        $this->assertSame(2, $relationship->verificationDocuments()->count());
        $this->assertSame(1, $relationship->verificationDocuments()->pluck('submission_round')->unique()->sole());
    }

    /**
     * @dataProvider manuallySelectedPairRelationshipTypes
     */
    public function test_registration_pairs_manually_selected_front_and_back_documents(
        string $relationshipType,
        string $documentType,
        ?string $relationshipNotes,
    ): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $parent = $this->createApprovedParent();
        $this->completeChildWizardUntilRelationshipReview($parent, $relationshipType, 'manualpairchild'.$relationshipType);

        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), [
                'documents' => [
                    ['document_type' => $documentType, 'document_side' => 'front', 'pairing_key' => null, 'file' => UploadedFile::fake()->createWithContent('evidence-front.pdf', 'front')->mimeType('application/pdf')],
                    ['document_type' => $documentType, 'document_side' => 'back', 'pairing_key' => null, 'file' => UploadedFile::fake()->createWithContent('evidence-back.pdf', 'back')->mimeType('application/pdf')],
                ],
                'relationship_notes' => $relationshipNotes,
                'confirm_submission' => '1',
            ])->assertRedirect(route('parent.create-child.support-information'));

        $documents = ParentChildAccount::query()->latest('id')->firstOrFail()
            ->verificationDocuments()
            ->orderBy('display_order')
            ->get();

        $this->assertCount(2, $documents);
        $this->assertNotNull($documents[0]->pairing_key);
        $this->assertSame($documents[0]->pairing_key, $documents[1]->pairing_key);
    }

    public static function manuallySelectedPairRelationshipTypes(): array
    {
        return [
            'adoptive parent' => ['adoptive_parent', 'adoption_order', null],
            'non-parent' => ['aunt', 'care_arrangement', 'The guardian provides ongoing care for this dependent.'],
        ];
    }

    public function test_non_parental_registration_requires_context_before_relationship_submission(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 200)]);

        $parent = $this->createApprovedParent();
        $this->completeChildWizardUntilRelationshipReview($parent, 'aunt', 'auntchild');

        $payload = [
            'documents' => [[
                'document_type' => 'care_arrangement',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create('care-arrangement.pdf', 100, 'application/pdf'),
            ]],
            'confirm_submission' => '1',
        ];

        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), $payload)
            ->assertSessionHasErrors('relationship_notes');

        $payload['relationship_notes'] = 'The guardian provides ongoing care for this dependent.';
        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), $payload)
            ->assertRedirect(route('parent.create-child.support-information'));

        $this->assertTrue(ParentChildAccount::query()->latest('id')->firstOrFail()->can_manage_support_information);
    }

    public function test_child_account_approval_does_not_verify_the_relationship(): void
    {
        $parent = $this->createApprovedParent();
        $child = User::factory()->create(['email_verified_at' => now()]);
        $child->assignRole('learner');
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => GuardianRelationshipTypes::pathway('biological_mother'),
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'current_evidence_round' => 1,
            'verification_status' => 'pending',
            'verification_document_path' => 'child-verifications/test.pdf',
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
        app(ParentChildVerificationService::class)->approveChild($relationship);

        $relationship->refresh();
        $this->assertSame(ParentChildAccount::STATUS_PENDING, $relationship->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_PENDING, $relationship->relationship_verified_status);
        $this->assertNull($relationship->relationship_verified_at);
    }

    public function test_guardian_can_start_dependent_account_creation_for_older_dependent(): void
    {
        $parent = $this->createApprovedParent();

        $this->actingAs($parent)
            ->post(route('parent.create-child.store'), [
                'first_name' => 'Adult',
                'middle_initial' => null,
                'last_name' => 'Dependent',
                'suffix' => null,
                'birthdate' => now()->subYears(22)->toDateString(),
                'gender' => 'prefer_not_to_say',
                'relationship_type' => 'biological_mother',
                'relationship_custom' => null,
            ])
            ->assertRedirect(route('parent.create-child.location'))
            ->assertSessionHasNoErrors();

        $this->assertSame(22, session('child_step1.age'));
    }

    private function createApprovedParent(): User
    {
        $this->seedLocationRows();

        $parent = User::factory()->create([
            'first_name' => 'Approved',
            'last_name' => 'Parent',
            'birthdate' => now()->subYears(30)->toDateString(),
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $parent->assignRole('learner');

        $parent->learnerProfile()->create([
            'username' => 'approvedparent'.$parent->id,
            'birthdate' => now()->subYears(30)->toDateString(),
            'gender' => 'female',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => true,
            'requires_parental_consent' => false,
        ]);

        return $parent;
    }

    private function seedLocationRows(): void
    {
        DB::table('provinces')->insert([
            'code' => '402100000',
            'name' => 'Sample Province',
            'region_code' => '040000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cities')->insert([
            'code' => '402101000',
            'name' => 'Sample City',
            'region_code' => '040000000',
            'province_code' => '402100000',
            'is_city' => true,
            'city_class' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('barangays')->insert([
            'code' => '402101001',
            'name' => 'Sample Barangay',
            'city_code' => '402101000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function childWizardSession(): array
    {
        return [
            'child_step1' => [
                'first_name' => 'Child',
                'middle_initial' => 'T',
                'last_name' => 'Learner',
                'suffix' => null,
                'birthdate' => now()->subYears(10)->toDateString(),
                'age' => 10,
                'gender' => 'male',
                'relationship_type' => 'biological_mother',
                'relationship_custom' => null,
            ],
            'child_step2' => [
                'city_code' => '402101000',
                'barangay_code' => '402101001',
            ],
        ];
    }

    private function completeChildWizardUntilRelationshipReview(User $parent, string $relationshipType, string $username): void
    {
        $session = $this->childWizardSession();
        $session['child_step1']['relationship_type'] = $relationshipType;

        $this->actingAs($parent)
            ->postJson(route('parent.create-child.credentials.temp-upload'), [
                'verification_document' => UploadedFile::fake()->create('birth-cert.pdf', 120, 'application/pdf'),
            ])->assertOk();

        $this->actingAs($parent)->withSession($session)
            ->post(route('parent.create-child.credentials.store'), [
                'username' => $username,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ])->assertRedirect(route('parent.create-child.validation'));

        $this->actingAs($parent)
            ->post(route('parent.create-child.validation.store'))
            ->assertRedirect(route('parent.create-child.relationship-verification'));
    }

    private function submitRelationshipEvidence(User $parent, string $username): void
    {
        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), [
                'documents' => [[
                    'document_type' => 'civil_registry_record',
                    'document_side' => 'not_applicable',
                    'pairing_key' => null,
                    'file' => UploadedFile::fake()->create($username.'.pdf', 100, 'application/pdf'),
                ]],
                'confirm_submission' => '1',
            ])->assertRedirect(route('parent.create-child.support-information'));

        $this->assertTrue(ParentChildAccount::query()->latest('id')->firstOrFail()->can_manage_support_information);
    }
}
