<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\LearnerIdentityVerification;
use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

    public function test_invalid_city_and_barangay_pair_is_rejected_server_side(): void
    {
        $this->post(route('instructor-application.register.personal.store'), $this->personalPayload([
            'barangay_code' => '402201001',
        ]))->assertSessionHasErrors('barangay_code');
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
