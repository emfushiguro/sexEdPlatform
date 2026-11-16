<?php

namespace Tests\Unit;

use App\Models\LearnerIdentityVerification;
use App\Models\AdultDependentAuthorityRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Http\Controllers\Auth\ParentRegistrationController;
use App\Http\Controllers\Admin\AdultDependentAuthorityReviewController;
use App\Http\Controllers\AdultDependentAuthorityController;
use App\Http\Requests\Auth\ActivateMinorAccountRequest;
use App\Notifications\Admin\AdultAuthorityReviewRequestedNotification;
use App\Services\Auth\MinorAccountHandoffService;
use App\Services\DependentSupportInformationService;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentityReview;
use App\Services\Identity\LearnerIdentitySubmission;
use App\Services\Persona\PersonaInquiryService;
use App\Support\LearnerAgePolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Mockery;

class DependentAdultWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'persona.local_manual_review' => false]);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('role')->default('learner');
            $table->boolean('is_parent_registration')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('status')->default('active');
            $table->string('parent_verification_status')->nullable();
            $table->boolean('parent_identity_sandbox_manual_review')->default(false);
            $table->string('password')->nullable();
            $table->boolean('minor_account_activation_required')->default(false);
            $table->timestamp('minor_account_activated_at')->nullable();
            $table->string('minor_privacy_notice_version')->nullable();
            $table->timestamp('minor_privacy_accepted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('parent_child_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_user_id');
            $table->unsignedBigInteger('child_user_id');
            $table->string('relationship_status');
            $table->string('relationship_verified_status');
            $table->timestamp('relationship_verified_at')->nullable();
            $table->unsignedInteger('current_evidence_round')->default(1);
            $table->string('verification_document_path')->nullable();
            $table->string('verification_status')->nullable();
            $table->boolean('can_view_progress')->default(false);
            $table->boolean('can_view_quiz_answers')->default(false);
            $table->boolean('can_manage_support_information')->default(false);
            $table->boolean('can_approve_content')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('learner_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('username')->nullable();
            $table->timestamps();
        });
        Schema::create('minor_account_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('child_user_id');
            $table->unsignedBigInteger('guardian_user_id');
            $table->unsignedBigInteger('parent_child_account_id');
            $table->string('purpose');
            $table->string('token_hash')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
        Schema::create('dependent_support_information_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('dependent_user_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('parent_child_account_id')->nullable();
            $table->string('action');
            $table->json('changed_fields')->nullable();
            $table->timestamp('occurred_at');
        });
        Schema::create('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_child_account_id');
            $table->string('document_type');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('submission_round');
            $table->timestamp('superseded_at')->nullable();
        });
        Schema::create('learner_identity_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('pathway');
            $table->string('document_type')->nullable();
            $table->string('evidence_source')->nullable();
            $table->unsignedBigInteger('persona_evidence_attempt_id')->nullable();
            $table->json('requested_corrections')->nullable();
            $table->text('correction_note')->nullable();
            $table->boolean('resubmission_allowed')->default(true);
            $table->string('government_id_type')->nullable();
            $table->string('government_id_type_other')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->nullable();
            $table->boolean('sandbox_manual_review')->default(false);
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('submission_round')->default(1);
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
        });
        Schema::create('learner_identity_evidence', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('verification_id');
            $table->string('slot');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedInteger('byte_size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('learner_identity_audits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('verification_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->unsignedInteger('submission_round');
            $table->text('reason')->nullable();
            $table->json('correction_request')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        (require database_path('migrations/2026_11_14_000001_create_dependent_adult_authorization_records.php'))->up();
        (require database_path('migrations/2026_11_14_000002_create_adult_dependent_authority_requests.php'))->up();
        (require database_path('migrations/2026_10_26_000000_create_persona_identity_tables.php'))->up();
        Notification::fake();
    }

    public function test_age_classification_does_not_open_teen_content_to_younger_children(): void
    {
        $this->assertSame(LearnerAgePolicy::INELIGIBLE_UNDERAGE, LearnerAgePolicy::classify('2017-01-01', '2026-01-01'));
        $this->assertNull(LearnerAgePolicy::category('2017-01-01', '2026-01-01'));
        $this->assertSame(LearnerAgePolicy::CATEGORY_TEENAGERS, LearnerAgePolicy::category('2009-01-01', '2026-01-01'));
        $this->assertSame(LearnerAgePolicy::CATEGORY_ADULTS, LearnerAgePolicy::category('1920-01-01', '2026-01-01'));
    }

    public function test_guardian_can_begin_registration_for_dependents_older_than_the_guardian(): void
    {
        $guardian = Mockery::mock(User::class)->makePartial();
        foreach (['hasVerifiedEmail', 'canBeParent', 'isParentRegistration', 'isParentVerificationApproved', 'hasCompletedGuardianOnboarding'] as $method) {
            $guardian->shouldReceive($method)->andReturn(true);
        }
        Auth::shouldReceive('user')->andReturn($guardian);
        $request = \Illuminate\Http\Request::create('/parent/create-child', 'POST', [
            'first_name' => 'Older', 'last_name' => 'Dependent', 'birthdate' => '1920-01-01',
            'gender' => 'prefer_not_to_say', 'relationship_type' => 'caregiver',
        ]);
        $response = app(ParentRegistrationController::class)->storeChildInfo($request);
        $this->assertSame(route('parent.create-child.location'), $response->getTargetUrl());
        $this->assertSame('1920-01-01', session('child_step1.birthdate'));
    }

    public function test_adult_activation_requires_the_dependents_consent_and_records_it(): void
    {
        [$adult, $relationship] = $this->relationship();
        DB::table('learner_profiles')->insert(['user_id' => $adult->id, 'username' => 'initial']);
        $requirement = Mockery::mock(LearnerIdentityRequirement::class);
        $requirement->shouldReceive('createForNewLearner')->once()->andReturn(new LearnerIdentityVerification);
        $handoffs = new MinorAccountHandoffService($requirement);
        [$handoff, $code] = $handoffs->issueActivation($relationship);

        try {
            $handoffs->consumeActivation($code, 'newname', 'MyPassword1!');
            $this->fail('Adult activation must require personal consent.');
        } catch (InvalidArgumentException) {
            $this->assertNull($handoff->fresh()->used_at);
        }

        $activated = $handoffs->consumeActivation($code, 'newname', 'MyPassword1!', true);
        $this->assertTrue(Hash::check('MyPassword1!', $activated->password));
        $this->assertNotNull($handoff->fresh()->used_at);
        $this->assertSame(1, DB::table('dependent_adult_authorization_records')->where('dependent_user_id', $adult->id)->count());
        $this->assertSame('self', DB::table('dependent_adult_authorization_records')->value('authorization_method'));
        $this->assertSame($adult->id, DB::table('dependent_adult_authorization_records')->value('authorized_by_user_id'));
    }

    public function test_representative_cannot_authorize_activation_until_independent_review(): void
    {
        [$adult, $relationship] = $this->relationship();
        DB::table('users')->insert(['id' => 3, 'name' => 'Reviewer', 'birthdate' => '1980-01-01', 'status' => 'active']);
        DB::table('learner_profiles')->insert(['user_id' => $adult->id, 'username' => 'initial']);
        $requirement = Mockery::mock(LearnerIdentityRequirement::class);
        $requirement->shouldReceive('createForNewLearner')->once()->andReturn(new LearnerIdentityVerification);
        $handoffs = new MinorAccountHandoffService($requirement);
        [$handoff, $code] = $handoffs->issueActivation($relationship);
        $path = 'cc-authority-'.bin2hex(random_bytes(12)).'.pdf';
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => sys_get_temp_dir()]));
        Storage::disk('local')->put($path, 'synthetic authority evidence');

        try {
            $authority = AdultDependentAuthorityRequest::query()->create([
                'parent_child_account_id' => $relationship->id, 'guardian_user_id' => 1,
                'document_path' => $path, 'document_mime' => 'application/pdf',
                'document_hash' => hash('sha256', 'synthetic authority evidence'),
                'dependent_identity_hash' => AdultDependentAuthorityRequest::identityHash($adult),
                'status' => 'pending', 'policy_version' => config('learner_age_policy.adult_dependent_consent_version'),
                'privacy_notice_hash' => hash_file('sha256', resource_path('views/legal/privacy.blade.php')),
                'terms_hash' => hash_file('sha256', resource_path('views/legal/terms.blade.php')),
                'authorized_at' => now(),
            ]);
            try {
                $handoffs->consumeActivation($code, 'newname', 'MyPassword1!', false, 'representative');
                $this->fail('Pending authority must not authorize activation.');
            } catch (InvalidArgumentException) {
                $this->assertNull($handoff->fresh()->used_at);
            }

            $guardian = Mockery::mock(User::class)->makePartial();
            $guardian->id = 1;
            $withdrawal = \Illuminate\Http\Request::create('/', 'POST');
            $withdrawal->setUserResolver(fn () => $guardian);
            app(AdultDependentAuthorityController::class)->withdraw($withdrawal, $relationship, $authority);
            $this->assertSame('withdrawn', $authority->fresh()->status);
            $authority = $authority->replicate()->forceFill(['status' => 'pending']);
            $authority->save();

            $reviewer = Mockery::mock(User::class)->makePartial();
            $reviewer->id = 3;
            $reviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
            $missingConfirmation = \Illuminate\Http\Request::create('/', 'POST', ['decision' => 'approved']);
            $missingConfirmation->setUserResolver(fn () => $reviewer);
            try {
                app(AdultDependentAuthorityReviewController::class)->decide($missingConfirmation, $relationship, $authority);
                $this->fail('Approval must confirm the specific authority scope.');
            } catch (\Illuminate\Validation\ValidationException) {
                $this->assertSame('pending', $authority->fresh()->status);
            }
            $guardianReviewer = Mockery::mock(User::class)->makePartial();
            $guardianReviewer->id = 1;
            $guardianReviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
            $selfReview = \Illuminate\Http\Request::create('/', 'POST', ['decision' => 'approved', 'authority_scope_confirmed' => '1']);
            $selfReview->setUserResolver(fn () => $guardianReviewer);
            try {
                app(AdultDependentAuthorityReviewController::class)->decide($selfReview, $relationship, $authority);
                $this->fail('A guardian cannot approve their own authority.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
            $request = \Illuminate\Http\Request::create('/', 'POST', ['decision' => 'approved', 'authority_scope_confirmed' => '1']);
            $request->setUserResolver(fn () => $reviewer);
            app(AdultDependentAuthorityReviewController::class)->decide($request, $relationship, $authority);
            $this->assertSame('approved', $authority->fresh()->status);

            try {
                app(AdultDependentAuthorityController::class)->withdraw($withdrawal, $relationship, $authority->fresh());
                $this->fail('Approved authority cannot be withdrawn through a pending-request action.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertSame('approved', $authority->fresh()->status);
            }

            $activationRequest = ActivateMinorAccountRequest::create('/', 'POST', ['code' => $code, 'consent_method' => 'representative']);
            $rules = $activationRequest->rules();
            $this->assertTrue(Validator::make(['consent_method' => 'representative'], [
                'consent_method' => $rules['consent_method'], 'adult_consent' => $rules['adult_consent'], 'privacy_notice' => $rules['privacy_notice'],
            ])->passes());
            $this->withViewErrors([])->view('auth.teenager-activation', [
                'child' => $adult, 'profile' => $adult->learnerProfile, 'code' => $code, 'adultAuthority' => $authority,
            ])->assertSee('My verified representative has authorized registration');

            Storage::disk('local')->put($path, 'modified evidence');
            $this->assertNull($handoffs->approvedAdultAuthority($relationship->fresh()));
            Storage::disk('local')->put($path, 'synthetic authority evidence');
            $handoffs->consumeActivation($code, 'newname', 'MyPassword1!', false, 'representative');
            $record = DB::table('dependent_adult_authorization_records')->first();
            $this->assertSame('representative', $record->authorization_method);
            $this->assertSame(1, $record->authorized_by_user_id);
            $this->assertSame($authority->id, $record->authority_request_id);
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    public function test_guardian_submits_authority_privately_and_notifies_administrators(): void
    {
        [$adult, $relationship] = $this->relationship();
        DB::table('users')->insert(['id' => 3, 'name' => 'Reviewer', 'birthdate' => '1980-01-01', 'status' => 'active']);
        DB::table('roles')->insert(['id' => 1, 'name' => 'admin', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_type' => User::class, 'model_id' => 3]);
        $guardian = Mockery::mock(User::class)->makePartial();
        $guardian->id = 1;
        foreach (['hasVerifiedEmail', 'isParentVerificationApproved', 'hasCompletedGuardianOnboarding'] as $method) {
            $guardian->shouldReceive($method)->andReturn(true);
        }
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cc-authority-'.bin2hex(random_bytes(12));
        mkdir($root);
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => $root]));
        $request = \Illuminate\Http\Request::create('/', 'POST', ['authority_consent' => '1'], [], [
            'authority_document' => UploadedFile::fake()->create('authority.pdf', 4, 'application/pdf'),
        ]);
        $request->setUserResolver(fn () => $guardian);

        try {
            app(AdultDependentAuthorityController::class)->store($request, $relationship);
            $authority = AdultDependentAuthorityRequest::query()->sole();
            $this->assertSame('pending', $authority->status);
            $this->assertSame(AdultDependentAuthorityRequest::identityHash($adult), $authority->dependent_identity_hash);
            $this->assertTrue(Storage::disk('local')->exists($authority->document_path));
            Notification::assertSentTo(User::query()->findOrFail(3), AdultAuthorityReviewRequestedNotification::class);
        } finally {
            $path = AdultDependentAuthorityRequest::query()->value('document_path');
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            if (is_dir($root.DIRECTORY_SEPARATOR.'adult-authority'.DIRECTORY_SEPARATOR.$relationship->id)) {
                rmdir($root.DIRECTORY_SEPARATOR.'adult-authority'.DIRECTORY_SEPARATOR.$relationship->id);
                rmdir($root.DIRECTORY_SEPARATOR.'adult-authority');
            }
            rmdir($root);
        }
    }

    public function test_adult_permissions_require_the_dependents_explicit_choices(): void
    {
        [$adult, $relationship] = $this->relationship();
        $relationship->update(['can_view_progress' => true, 'can_view_quiz_answers' => true, 'can_approve_content' => true]);
        $this->assertFalse(ParentChildAccount::query()->withPermission('can_view_progress')->whereKey($relationship->id)->exists());

        app(DependentSupportInformationService::class)->setGuardianPermissions($relationship, $adult, [
            'can_view_progress' => true, 'can_view_quiz_answers' => false, 'can_manage_support_information' => false,
        ]);
        $this->assertTrue(ParentChildAccount::query()->withPermission('can_view_progress')->whereKey($relationship->id)->exists());
        $this->assertFalse(ParentChildAccount::query()->withPermission('can_view_quiz_answers')->whereKey($relationship->id)->exists());
        $this->assertFalse((bool) $relationship->fresh()->can_approve_content);
        $this->assertSame(3, DB::table('dependent_support_information_audits')->count());
    }

    public function test_guardian_permissions_stop_at_the_eighteenth_birthday_until_confirmed(): void
    {
        [$adult, $relationship] = $this->relationship();
        $relationship->update(['can_view_progress' => true]);
        DB::table('users')->where('id', $adult->id)->update(['birthdate' => now()->subYears(17)->toDateString()]);
        $this->assertTrue(ParentChildAccount::query()->withPermission('can_view_progress')->whereKey($relationship->id)->exists());
        DB::table('users')->where('id', $adult->id)->update(['birthdate' => now()->subYears(18)->toDateString()]);
        $this->assertFalse(ParentChildAccount::query()->withPermission('can_view_progress')->whereKey($relationship->id)->exists());
    }

    public function test_adult_without_email_keeps_account_consent_after_relationship_revocation(): void
    {
        [$adult, $relationship] = $this->relationship();
        DB::table('dependent_adult_authorization_records')->insert([
            'dependent_user_id' => $adult->id, 'parent_child_account_id' => $relationship->id,
            'policy_version' => 'v1', 'privacy_notice_hash' => str_repeat('a', 64),
            'terms_hash' => str_repeat('b', 64), 'accepted_at' => now(),
        ]);
        $relationship->update(['relationship_status' => 'revoked']);
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $adult->id;
        $user->birthdate = '1990-01-01';
        $user->minor_account_activation_required = false;
        $user->shouldReceive('isLearner')->andReturn(true);

        $this->assertTrue($user->hasAcceptedDependentAdultAuthorization());
    }

    public function test_assisted_review_requires_relationship_evidence_an_alternative_record_and_selfie(): void
    {
        [$adult, $relationship] = $this->relationship();
        DB::table('users')->insert(['id' => 3, 'name' => 'Reviewer', 'birthdate' => '1980-01-01', 'status' => 'active']);
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $adult->id, 'pathway' => 'adult', 'document_type' => 'assisted_review',
            'status' => 'pending', 'submission_round' => 1, 'evidence_source' => 'persona_selfie',
        ]);
        $case->forceFill(['evidence_source' => 'persona_selfie'])->save();
        DB::table('learner_identity_evidence')->insert([
            'verification_id' => $case->id, 'slot' => 'identity_front',
            'storage_path' => 'record.jpg', 'mime_type' => 'image/jpeg',
        ]);
        $disk = Mockery::mock();
        $disk->shouldReceive('exists')->andReturn(true);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $check = new \ReflectionMethod(LearnerIdentityReview::class, 'requiredEvidenceExists');
        $review = app(LearnerIdentityReview::class);

        $this->assertFalse($check->invoke($review, $case));
        DB::table('guardian_relationship_verification_documents')->insert([
            'parent_child_account_id' => $relationship->id, 'document_type' => 'dependent_birth_record', 'submission_round' => 1,
            'disk' => 'local', 'path' => 'birth.jpg',
        ]);
        $this->assertTrue($check->invoke($review, $case));

        $reviewer = Mockery::mock(User::class)->makePartial();
        $reviewer->id = 3;
        $reviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
        $requirement = Mockery::mock(LearnerIdentityRequirement::class);
        $requirement->shouldReceive('pathwayFor')->andReturn('adult');
        $persona = Mockery::mock(PersonaInquiryService::class);
        $persona->shouldReceive('assertLearnerMayApprove')->andReturn(new \App\Models\PersonaInquiry());
        $review = new LearnerIdentityReview($requirement, $persona);
        try {
            $review->approve($reviewer, $case, 1);
            $this->fail('Assisted review must require administrator confirmation.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('pending', $case->fresh()->status);
        }
        $guardianReviewer = Mockery::mock(User::class)->makePartial();
        $guardianReviewer->id = 1;
        $guardianReviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
        try {
            $review->approve($guardianReviewer, $case, 1, assistedEvidenceReviewed: true, assistedReviewBasis: 'trusted_verifier_attestation');
            $this->fail('A Guardian cannot review their dependent identity.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        try {
            $review->approve($reviewer, $case, 1, assistedEvidenceReviewed: true, assistedReviewBasis: 'trusted_verifier_attestation');
            $this->fail('An attestation requires independent issuer verification.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('pending', $case->fresh()->status);
        }
        $review->approve($reviewer, $case, 1, assistedEvidenceReviewed: true, assistedReviewBasis: 'trusted_verifier_attestation',
            assistedIssuerVerified: true, assistedIssuerVerification: 'Called the school registrar via its official directory on 2026-11-15; registrar confirmed the signed identity statement.');
        $this->assertSame('approved', $case->fresh()->status);
        $this->assertSame('Alternative evidence: trusted_verifier_attestation', DB::table('learner_identity_audits')->value('reason'));
        $this->assertStringContainsString('official directory', $case->audits()->where('action', 'approved')->sole()->correction_request['assisted_review']['issuer_verification']);
        $relationship->update(['relationship_status' => 'revoked']);
        $this->assertFalse($check->invoke($review, $case));
    }

    public function test_assisted_identity_submission_and_manual_approval_with_persona_enabled(): void
    {
        config(['persona.enabled' => true]);
        [$adult, $relationship] = $this->relationship();
        DB::table('users')->insert(['id' => 3, 'name' => 'Reviewer', 'birthdate' => '1980-01-01', 'status' => 'active']);
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cc-identity-'.bin2hex(random_bytes(12));
        mkdir($root);
        Storage::set('local', Storage::build(['driver' => 'local', 'root' => $root]));
        $source = public_path('media/Logo.png');
        Storage::disk('local')->put('birth.png', file_get_contents($source));
        DB::table('guardian_relationship_verification_documents')->insert([
            'parent_child_account_id' => $relationship->id, 'document_type' => 'dependent_birth_record',
            'submission_round' => 1, 'disk' => 'local', 'path' => 'birth.png',
        ]);
        DB::table('roles')->insert(['id' => 1, 'name' => 'admin', 'guard_name' => 'web']);
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $adult->id, 'pathway' => 'adult', 'submission_round' => 0,
        ]);
        $actor = Mockery::mock(User::class)->makePartial();
        $actor->id = $adult->id;
        $actor->birthdate = '1990-01-01';
        $actor->shouldReceive('isLearner')->andReturn(true);
        $actor->shouldReceive('isParentRegistration')->andReturn(false);
        $actor->shouldReceive('hasVerifiedEmail')->andReturn(false);
        $actor->shouldReceive('hasAcceptedDependentAdultAuthorization')->andReturn(true);
        $actor->shouldReceive('identityVerifications')->andReturnUsing(fn () => $adult->identityVerifications());
        $actor->shouldReceive('guardianRelationships')->andReturnUsing(fn () => $adult->guardianRelationships());
        $persona = Mockery::mock(PersonaInquiryService::class);
        $persona->shouldReceive('isRequired')->andReturn(true);
        $persona->shouldReceive('assertLearnerMaySubmit')->andReturn(new \App\Models\PersonaInquiry(['id' => 1, 'flow_type' => \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL]));
        $persona->shouldReceive('assertLearnerMayApprove')->andReturn(new \App\Models\PersonaInquiry());

        try {
            $submitted = (new LearnerIdentitySubmission($persona))->submit($actor, $case, [
                'document_type' => 'assisted_review', 'confirm_submission' => '1', 'submission_round' => 0, 'learner_note' => null,
            ], [
                'identity_front' => new UploadedFile($source, 'record.png', 'image/png', null, true),
            ]);
            $this->assertSame('pending', $submitted->status);
            $this->assertSame('persona_selfie', $submitted->evidence_source);
            $this->assertCount(1, $submitted->evidence);
            $audit = $submitted->audits()->where('action', 'submitted')->sole();
            $this->assertNull($audit->correction_request['learner_note']);
            $this->assertSame(1, $audit->submission_round);
            $this->assertCount(1, $audit->correction_request['evidence']);
            $reviewer = Mockery::mock(User::class)->makePartial();
            $reviewer->id = 3;
            $reviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
            $requirement = Mockery::mock(LearnerIdentityRequirement::class);
            $requirement->shouldReceive('pathwayFor')->andReturn('adult');
            $review = new LearnerIdentityReview($requirement, $persona);
            $originalPaths = $submitted->evidence->pluck('storage_path');
            $review->reject($reviewer, $submitted, \App\Enums\LearnerIdentityRejectionReason::UnclearSelfie, 1,
                corrections: ['identity_front'], note: 'Please replace the identity record.');
            $submitted = (new LearnerIdentitySubmission($persona))->submit($actor, $submitted->fresh(), [
                'document_type' => 'assisted_review', 'confirm_submission' => '1', 'submission_round' => 1,
                'learner_note' => 'Here is my clearer selfie.',
            ], [
                'identity_front' => new UploadedFile($source, 'replacement.png', 'image/png', null, true),
            ]);
            $this->assertSame(2, $submitted->submission_round);
            foreach ($originalPaths as $path) {
                $this->assertTrue(Storage::disk('local')->exists($path));
            }
            $this->assertNull($submitted->audits()->where('action', 'submitted')->sole()->correction_request['learner_note']);
            $this->assertSame('Here is my clearer selfie.', $submitted->audits()->where('action', 'resubmitted')->sole()->correction_request['learner_note']);
            $response = app(\App\Http\Controllers\Admin\LearnerIdentityVerificationController::class)->evidence(
                \Illuminate\Http\Request::create('/', 'GET', ['round' => 1]), $submitted, 'identity_front');
            $this->assertSame(Storage::disk('local')->path($originalPaths->last()), $response->getFile()->getPathname());
            (new LearnerIdentityReview($requirement, $persona))->approve($reviewer, $submitted, 2,
                assistedEvidenceReviewed: true, assistedReviewBasis: 'trusted_verifier_attestation',
                assistedIssuerVerified: true, assistedIssuerVerification: 'Called the school registrar via its official directory on 2026-11-15; registrar confirmed the signed identity statement.');
            $this->assertSame('approved', $submitted->fresh()->status);
        } finally {
            foreach (Storage::disk('local')->allFiles('learner-verifications') as $path) {
                Storage::disk('local')->delete($path);
            }
            Storage::disk('local')->delete('birth.png');
            foreach (['learner-verifications'.DIRECTORY_SEPARATOR.$adult->id.DIRECTORY_SEPARATOR.'adult',
                'learner-verifications'.DIRECTORY_SEPARATOR.$adult->id, 'learner-verifications'] as $directory) {
                if (is_dir($root.DIRECTORY_SEPARATOR.$directory)) {
                    rmdir($root.DIRECTORY_SEPARATOR.$directory);
                }
            }
            rmdir($root);
        }
    }

    public function test_reviewer_can_request_changes_when_assisted_evidence_is_missing(): void
    {
        [$adult] = $this->relationship();
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $adult->id, 'pathway' => 'adult', 'document_type' => 'assisted_review',
            'status' => 'pending', 'submission_round' => 1,
        ]);
        $reviewer = Mockery::mock(User::class)->makePartial();
        $reviewer->id = 3;
        $reviewer->shouldReceive('hasRole')->with('admin')->andReturn(true);
        $requirement = Mockery::mock(LearnerIdentityRequirement::class);
        $requirement->shouldReceive('pathwayFor')->andReturn('adult');
        $review = new LearnerIdentityReview($requirement, Mockery::mock(PersonaInquiryService::class));
        $review->reject($reviewer, $case, \App\Enums\LearnerIdentityRejectionReason::UnclearSelfie, 1,
            note: 'Provide independent identity evidence and a clear selfie.');
        $this->assertSame('rejected', $case->fresh()->status);
        $this->assertSame('Provide independent identity evidence and a clear selfie.', $case->fresh()->correction_note);
    }

    public function test_assisted_persona_start_sync_submit_and_upload_refusal(): void
    {
        config(['persona.enabled' => true, 'persona.environment' => 'sandbox',
            'persona.templates.selfie' => 'itmpl_selfie_test', 'persona.templates.govid_selfie' => 'itmpl_government_test', 'persona.api_key' => 'test-key']);
        config(['guardian_identity.accepted_id_types' => ['passport'],
            'guardian_identity.id_types.passport' => ['label' => 'Passport', 'requires_back' => false],
            'persona.government_id_classes.passport' => 'pp']);
        [$adult] = $this->relationship();
        DB::table('users')->where('id', $adult->id)->update(['email_verified_at' => now()]);
        $adult->refresh();
        // Keep authorization deterministic while exercising the real persisted identity pipeline.
        \Illuminate\Support\Facades\Gate::before(fn () => false);
        DB::table('roles')->insert(['id' => 1, 'name' => 'learner', 'guard_name' => 'web']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_type' => User::class, 'model_id' => $adult->id]);
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $adult->id, 'pathway' => 'adult', 'submission_round' => 0,
        ]);
        Storage::fake('local');
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps();
        });
        $drafts = app(\App\Services\Identity\LearnerIdentityDocumentDraft::class);
        $drafts->stage($adult, $case, ['id_selection' => 'assisted_review', 'document_type' => 'assisted_review'],
            new UploadedFile(public_path('media/Logo.png'), 'record.png', 'image/png', null, true), null);
        $selfie = ['data' => ['type' => 'verification/selfie', 'id' => 'ver_selfie_test', 'attributes' => [
            'status' => 'passed', 'capture-method' => 'photo',
            'checks' => [['name' => 'selfie_liveness_detection', 'status' => 'passed']],
        ]]];
        $inquiry = ['data' => ['id' => 'inq_test', 'attributes' => ['status' => 'completed'],
            'relationships' => ['verifications' => ['data' => [['type' => 'verification/selfie', 'id' => 'ver_selfie_test']]]]]];
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $inquiryNumber = 0;
        \Illuminate\Support\Facades\Http::fake([
            '*/inquiries' => function () use (&$inquiryNumber) { return \Illuminate\Support\Facades\Http::response(['data' => ['id' => 'inq_test_'.++$inquiryNumber, 'attributes' => ['status' => 'created']]]); },
            '*/inquiries/*/resume' => function () use (&$inquiryNumber) { return \Illuminate\Support\Facades\Http::response(['meta' => ['session-token' => 'session_test_'.$inquiryNumber]]); },
            '*/inquiries/*' => \Illuminate\Support\Facades\Http::response($inquiry),
            '*/verification/selfies/ver_selfie_test' => \Illuminate\Support\Facades\Http::response($selfie),
        ]);
        $service = app(PersonaInquiryService::class);
        $started = $service->start($adult, 'assisted_review', null);
        $this->assertSame('assisted_review', $started['attempt']->local_document_type);
        $this->assertSame(\App\Models\PersonaInquiry::TEMPLATE_SELFIE, $started['attempt']->template_key);
        $government = $service->start($adult, 'government_id', 'passport');
        $this->assertSame('government_id', $government['attempt']->local_document_type);
        $this->assertNotNull($started['attempt']->fresh()->superseded_at);
        $started = $service->start($adult, 'assisted_review', null);
        $this->assertNotNull($government['attempt']->fresh()->superseded_at);
        $synced = app(\App\Services\Persona\PersonaInquirySynchronizer::class)->sync($started['attempt']);
        $this->assertTrue($synced->isPolicyEligible());
        $retaken = $service->replace($adult, 'assisted_review', null);
        $this->assertNotNull($synced->fresh()->superseded_at);
        $this->assertSame(1, $retaken['attempt']->target_submission_round);
        $this->assertNotSame($started['session_token'], $retaken['session_token']);
        $synced = app(\App\Services\Persona\PersonaInquirySynchronizer::class)->sync($retaken['attempt']);
        $this->assertTrue($synced->isPolicyEligible());
        $this->assertNull($case->fresh()->status, 'Provider selfie completion must not automatically submit or approve identity.');
        $data = ['document_type' => 'assisted_review', 'confirm_submission' => '1', 'submission_round' => 0];
        $submission = new LearnerIdentitySubmission($service);
        foreach ([true, false] as $enabled) {
            config(['persona.enabled' => $enabled]);
            try {
                $submission->submit($adult, $case, $data, ['selfie' => new UploadedFile(public_path('media/Logo.png'), 'selfie.png', 'image/png', null, true)]);
                $this->fail('Raw selfie uploads must never be accepted.');
            } catch (InvalidArgumentException $exception) {
                $this->assertSame('Unsupported evidence slot.', $exception->getMessage());
            }
        }
        try {
            $submission->submit($adult, $case, $data, $drafts->files($adult, $case));
            $this->fail('Disabled Persona must fail closed.');
        } catch (\DomainException $exception) {
            $this->assertStringContainsString('unavailable', $exception->getMessage());
            $this->assertNull($case->fresh()->status);
        }
        config(['persona.enabled' => true]);
        $submitted = $submission->submit($adult, $case, $data, $drafts->files($adult, $case));
        $this->assertSame('pending', $submitted->status);
        $this->assertSame('persona_selfie', $submitted->evidence_source);
        $this->assertTrue($submitted->usesPersonaSelfie());
        $this->assertSame($synced->id, $submitted->persona_evidence_attempt_id);
        $this->assertSame(['identity_front'], $submitted->evidence->pluck('slot')->all());
        try {
            $service->replace($adult, 'assisted_review', null);
            $this->fail('Submitted evidence cannot be retaken while pending review.');
        } catch (\Illuminate\Validation\ValidationException) {
            $this->assertNull($synced->fresh()->superseded_at);
            $this->assertSame('pending', $submitted->fresh()->status);
        }
        $this->assertSame($synced->id, $service->assertLearnerMayApprove($adult, $submitted)->id);
        $drafts->clear();
    }

    private function relationship(): array
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Guardian', 'birthdate' => '2005-01-01', 'status' => 'active', 'parent_verification_status' => 'approved'],
            ['id' => 2, 'name' => 'Dependent', 'birthdate' => '1990-01-01', 'status' => 'active', 'parent_verification_status' => null],
        ]);
        DB::table('users')->where('id', 2)->update(['minor_account_activation_required' => true]);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => 1, 'child_user_id' => 2,
            'relationship_status' => 'active', 'relationship_verified_status' => 'verified',
            'relationship_verified_at' => now(), 'verification_status' => 'approved',
        ]);

        return [User::query()->findOrFail(2), $relationship];
    }
}
