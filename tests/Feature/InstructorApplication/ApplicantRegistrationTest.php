<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorIdentityVerification;
use App\Models\User;
use App\Services\InstructorApplication\InstructorApplicantPersonalInformation;
use App\Services\InstructorApplication\InstructorIdentityService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApplicantRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['instructor_application.enabled' => true]);

        DB::table('provinces')->updateOrInsert(
            ['code' => '402100000'],
            [
                'name' => 'Cavite',
                'region_code' => '400000000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            [
                'name' => 'Cavite City',
                'region_code' => '400000000',
                'province_code' => '402100000',
                'is_city' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            [
                'name' => 'Barangay Test',
                'city_code' => '402101000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function test_feature_flag_hides_direct_registration(): void
    {
        config(['instructor_application.enabled' => false]);

        $this->get(route('instructor-application.register.personal'))
            ->assertNotFound();
    }

    public function test_age_below_18_is_rejected_from_personal_information(): void
    {
        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload([
            'birthdate' => now()->subYears(17)->subDay()->format('Y-m-d'),
        ]))->assertSessionHasErrors('birthdate');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_exactly_18_is_accepted_using_the_birthdate(): void
    {
        $birthdate = now()->subYears(18)->format('Y-m-d');

        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload([
            'birthdate' => $birthdate,
        ]))->assertRedirect(route('instructor-application.register.account'));

        $this->assertSame($birthdate, session('pending_instructor_applicant_personal.birthdate'));
    }

    public function test_duplicate_email_returns_sign_in_guidance(): void
    {
        $existing = User::factory()->create(['email' => 'existing@gmail.com']);

        $this->withSession(['pending_instructor_applicant_personal' => $this->personalPayload()])
            ->post(route('instructor-application.register.account.store'), [
                'email' => $existing->email,
                'password' => 'Xk#9mP2@qL7nR4wZ',
                'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
            ])
            ->assertSessionHasErrors(['email' => 'This email is already registered. Please sign in to resume your instructor application.']);
    }

    public function test_account_registration_form_exposes_password_strength_guidance(): void
    {
        $this->withSession(['pending_instructor_applicant_personal' => $this->personalPayload()])
            ->get(route('instructor-application.register.account'))
            ->assertOk()
            ->assertSee('x-model="password"', false)
            ->assertSee(':type="showPassword ? \'text\' : \'password\'"', false)
            ->assertSee('Show password')
            ->assertSee('Show confirmation password')
            ->assertSee('Strength:', false)
            ->assertSee('Use upper, lower, number, symbol', false)
            ->assertSee('All requirements met', false)
            ->assertSee('x-model="passwordConfirmation"', false)
            ->assertSee('Passwords match.', false);
    }

    public function test_city_and_barangay_labels_are_ignored_and_canonical_values_are_saved(): void
    {
        Event::fake([Registered::class]);

        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload([
            'city_name' => 'Forged City',
            'barangay_name' => 'Forged Barangay',
        ]))->assertRedirect(route('instructor-application.register.account'));

        $this->post(route('instructor-application.register.account.store'), [
            'email' => 'applicant@gmail.com',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])->assertRedirect(route('verification.notice'));

        $user = User::query()->where('email', 'applicant@gmail.com')->firstOrFail();
        $this->assertSame('applicant', $user->fresh()->role);
        $this->assertTrue($user->fresh()->hasRole('applicant'));
        $this->assertTrue($user->fresh()->isInstructorApplicant());
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'province_code' => '402100000',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'location' => 'Cavite City, Cavite',
        ]);
        $this->assertDatabaseMissing('learner_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('learner_identity_verifications', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('instructor_applications', ['user_id' => $user->id]);
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
    }

    public function test_registration_creates_a_missing_applicant_role_without_granting_panel_access(): void
    {
        Event::fake([Registered::class]);
        Role::findByName('applicant', 'web')->delete();
        $this->assertDatabaseMissing('roles', ['name' => 'applicant', 'guard_name' => 'web']);

        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload())
            ->assertRedirect(route('instructor-application.register.account'));

        $this->post(route('instructor-application.register.account.store'), [
            'email' => 'missing-role-applicant@gmail.com',
            'password' => 'Xk#9mP2@qL7nR4wZ',
            'password_confirmation' => 'Xk#9mP2@qL7nR4wZ',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'missing-role-applicant@gmail.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('applicant', $user->role);
        $this->assertSame('applicant', $user->account_type);
        $this->assertSame(['applicant'], $user->getRoleNames()->all());
        $this->assertCount(0, $user->getAllPermissions());
        $this->assertFalse($user->isLearner());
        $this->assertFalse($user->isInstructor());
        $this->assertFalse($user->isAdmin());
        $this->assertDatabaseMissing('learner_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('instructor_profiles', ['user_id' => $user->id]);
        $this->assertSame(1, Role::where('name', 'applicant')->where('guard_name', 'web')->count());
        $this->assertFalse(session()->has('pending_instructor_applicant_personal'));
        Event::assertDispatched(Registered::class, fn (Registered $event): bool => $event->user->is($user));
    }

    public function test_invalid_city_and_barangay_pair_is_rejected_server_side(): void
    {
        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload([
            'barangay_code' => '402201001',
        ]))->assertSessionHasErrors('barangay_code');
    }

    public function test_initial_registration_form_exposes_age_eligibility_and_profile_choices(): void
    {
        $this->get(route('instructor-application.register.personal'))
            ->assertOk()
            ->assertSee('You are')
            ->assertSee('Eligible to apply')
            ->assertSee('must be at least 18')
            ->assertSee('name="suffix"', false)
            ->assertSee('value="Jr."', false)
            ->assertSee('value="prefer_not_to_say"', false);
    }

    public function test_personal_information_form_exposes_age_eligibility_and_profile_choices(): void
    {
        $applicant = $this->applicant();

        $this->actingAs($applicant)
            ->get(route('instructor-application.personal-information.edit'))
            ->assertOk()
            ->assertSee('You are')
            ->assertSee('Eligible to apply')
            ->assertSee('must be at least 18')
            ->assertSee('name="suffix"', false)
            ->assertSee('value="Jr."', false)
            ->assertSee('value="prefer_not_to_say"', false);
    }

    public function test_personal_information_rejects_unsupported_gender_values(): void
    {
        $this->actingAs($this->applicant())
            ->put(route('instructor-application.personal-information.update'), $this->personalPayload([
                'gender' => 'non-binary',
            ]))
            ->assertSessionHasErrors('gender');
    }

    public function test_requested_personal_correction_preserves_the_previous_snapshot_and_requires_a_new_attempt(): void
    {
        $applicant = $this->applicant();
        $applicant->update(['first_name' => 'Direct', 'last_name' => 'Applicant', 'middle_initial' => 'WRONG']);
        $case = $applicant->instructorIdentityVerifications()->create([
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_ACTION_REQUIRED,
            'submission_round' => 1,
            'declared_snapshot' => ['first_name' => 'Direct', 'middle_initial' => 'WRONG'],
            'requested_action_codes' => ['clarify_personal_information'],
            'resubmission_allowed' => true,
        ]);

        $this->actingAs($applicant)->get(route('instructor-application.personal-information.edit'))
            ->assertOk()->assertSee('start a replacement identity verification');
        $this->put(route('instructor-application.personal-information.update'), $this->personalPayload(['middle_initial' => null]))
            ->assertSessionHasNoErrors();

        $this->assertNull($applicant->fresh()->middle_initial);
        $this->assertSame('WRONG', $case->fresh()->declared_snapshot['middle_initial']);
        $this->assertDatabaseHas('instructor_identity_audits', [
            'instructor_identity_verification_id' => $case->id,
            'actor_id' => $applicant->id,
            'action' => 'personal_information_corrected',
        ]);

        $snapshot = app(InstructorApplicantPersonalInformation::class)->for($applicant->fresh());
        $replacement = app(InstructorIdentityService::class)->startAttempt($applicant->fresh(), $snapshot);
        $this->assertSame(2, $replacement->submission_round);
        $this->assertNull($replacement->declared_snapshot['middle_initial']);
        $this->assertNotNull($case->fresh()->superseded_at);
        $this->assertSame('WRONG', $case->fresh()->declared_snapshot['middle_initial']);
        $this->put(route('instructor-application.personal-information.update'), $this->personalPayload(['middle_initial' => 'NEW']))
            ->assertSessionHasErrors('personal_information');
        $this->assertNull($applicant->fresh()->middle_initial);
    }

    public function test_personal_information_remains_locked_without_a_clarification_request(): void
    {
        $applicant = $this->applicant();
        $case = $applicant->instructorIdentityVerifications()->create([
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
            'submission_round' => 1,
            'declared_snapshot' => ['middle_initial' => 'ORIGINAL'],
            'resubmission_allowed' => true,
        ]);

        $this->actingAs($applicant)->put(route('instructor-application.personal-information.update'), $this->personalPayload())
            ->assertSessionHasErrors('personal_information');
        $case->update(['status' => InstructorIdentityVerification::STATUS_ACTION_REQUIRED, 'requested_action_codes' => ['retry_persona']]);
        $this->put(route('instructor-application.personal-information.update'), $this->personalPayload())
            ->assertSessionHasErrors('personal_information');
        $this->assertSame('ORIGINAL', $case->fresh()->declared_snapshot['middle_initial']);
    }

    private function applicant(): User
    {
        $applicant = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'birthdate' => '2000-01-01',
        ]);
        $applicant->assignRole(User::ACCOUNT_TYPE_APPLICANT);

        return $applicant;
    }

    private function personalPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Direct',
            'middle_initial' => 'A.',
            'last_name' => 'Applicant',
            'suffix' => null,
            'birthdate' => '2000-01-01',
            'gender' => 'prefer_not_to_say',
            'contact' => '09171234567',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'address_line' => '123 Test Road',
        ], $overrides);
    }
}
