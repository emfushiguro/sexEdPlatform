<?php

namespace Tests\Feature\Auth;

use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuardianTeenagerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_live_ten_to_seventeen_birthdates_enter_the_guardian_teenager_flow(): void
    {
        $parent = $this->approvedGuardian();

        foreach ([10, 17] as $age) {
            $this->actingAs($parent)
                ->post(route('parent.create-child.store'), $this->childInfo(now()->subYears($age)->toDateString()))
                ->assertRedirect(route('parent.create-child.location'))
                ->assertSessionHasNoErrors();
        }

        foreach ([9, 18, 100] as $age) {
            $this->actingAs($parent)
                ->post(route('parent.create-child.store'), $this->childInfo(now()->subYears($age)->toDateString()))
                ->assertSessionHasErrors('birthdate');
        }

        $this->actingAs($parent)
            ->post(route('parent.create-child.store'), $this->childInfo(now()->addDay()->toDateString()))
            ->assertSessionHasErrors('birthdate');

        $missing = $this->childInfo(now()->subYears(15)->toDateString());
        unset($missing['birthdate']);
        $this->actingAs($parent)
            ->post(route('parent.create-child.store'), $missing)
            ->assertSessionHasErrors('birthdate');
    }

    public function test_guardian_creates_a_restricted_teenager_without_a_password_or_fake_email(): void
    {
        Storage::fake('local');
        Notification::fake();
        $parent = $this->approvedGuardian();

        $child = $this->completeRegistration($parent, null, null, 'no-email-teen');

        $this->assertNull($child->email);
        $this->assertNull($child->email_verified_at);
        $this->assertTrue((bool) $child->minor_account_activation_required);
        $this->assertNull($child->minor_account_activated_at);
        $this->assertFalse(Hash::check('Password123!', (string) $child->password));
        $this->assertNull($child->learnerProfile?->username);
        $this->assertStringNotContainsString('@child.sexed-platform.local', (string) $child->email);

        $relationship = ParentChildAccount::query()->where('child_user_id', $child->id)->firstOrFail();
        $this->assertSame(ParentChildAccount::STATUS_PENDING, $relationship->relationship_status);
        $this->assertSame(ParentChildAccount::VERIFICATION_UNDER_REVIEW, $relationship->relationship_verified_status);
        $this->assertSame(config('learner_age_policy.guardian_consent_version'), $relationship->minor_registration_consent_version);
        $this->assertSame($parent->id, $relationship->minor_registration_authorized_by_user_id);
        $this->assertNotNull($relationship->minor_registration_authorized_at);
        $this->assertNull($relationship->verification_document_path);
        $this->assertSame(1, $relationship->verificationDocuments()->count());
    }

    public function test_genuine_proposed_email_and_username_are_optional_and_not_verified(): void
    {
        Storage::fake('local');
        Notification::fake();
        $parent = $this->approvedGuardian();

        $child = $this->completeRegistration($parent, 'teen@example.test', 'proposed-teen', 'email-teen');

        $this->assertSame('teen@example.test', $child->email);
        $this->assertNull($child->email_verified_at);
        $this->assertSame('proposed-teen', $child->learnerProfile?->username);
        $this->assertStringNotContainsString('+', (string) $child->email);
    }

    public function test_guardian_authorization_is_required_and_old_validation_post_is_gone(): void
    {
        $parent = $this->approvedGuardian();
        $session = $this->childWizardSession();
        $session['child_step3'] = ['username' => null, 'email' => null];

        $this->actingAs($parent)
            ->withSession($session)
            ->post(route('parent.create-child.relationship-verification.store'), [
                'documents' => [[
                    'document_type' => 'civil_registry_record',
                    'document_side' => 'not_applicable',
                    'pairing_key' => null,
                    'file' => UploadedFile::fake()->create('evidence.pdf', 10, 'application/pdf'),
                ]],
                'confirm_submission' => '1',
            ])
            ->assertSessionHasErrors('guardian_authorization');

        $this->actingAs($parent)
            ->withSession($session)
            ->post(route('parent.create-child.validation.store'))
            ->assertStatus(410)
            ->assertSee('restart');
    }

    private function completeRegistration(User $parent, ?string $email, ?string $username, string $suffix): User
    {
        $session = $this->childWizardSession();

        $this->actingAs($parent)
            ->withSession($session)
            ->post(route('parent.create-child.credentials.store'), array_filter([
                'email' => $email,
                'username' => $username,
            ], static fn ($value): bool => $value !== null))
            ->assertRedirect(route('parent.create-child.relationship-verification'));

        $payload = [
            'documents' => [[
                'document_type' => 'civil_registry_record',
                'document_side' => 'not_applicable',
                'pairing_key' => null,
                'file' => UploadedFile::fake()->create($suffix.'.pdf', 10, 'application/pdf'),
            ]],
            'relationship_notes' => null,
            'confirm_submission' => '1',
            'guardian_authorization' => '1',
        ];

        $this->actingAs($parent)
            ->post(route('parent.create-child.relationship-verification.store'), $payload)
            ->assertRedirect(route('parent.create-child.support-information'));

        return User::query()->where('first_name', 'Teen')->latest('id')->firstOrFail();
    }

    private function approvedGuardian(): User
    {
        $this->seedLocationRows();

        $guardian = User::factory()->create([
            'first_name' => 'Approved',
            'last_name' => 'Guardian',
            'birthdate' => now()->subYears(35)->toDateString(),
            'email_verified_at' => now(),
            'status' => User::STATUS_ACTIVE,
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');
        $guardian->learnerProfile()->create([
            'username' => 'guardian'.$guardian->id,
            'birthdate' => $guardian->birthdate,
            'gender' => 'female',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'province_code' => '402100000',
            'is_parent_account' => true,
        ]);

        return $guardian;
    }

    private function childInfo(string $birthdate): array
    {
        return [
            'first_name' => 'Teen',
            'middle_initial' => null,
            'last_name' => 'Learner',
            'suffix' => null,
            'birthdate' => $birthdate,
            'gender' => 'prefer_not_to_say',
            'relationship_type' => 'biological_mother',
            'relationship_custom' => null,
        ];
    }

    private function childWizardSession(): array
    {
        return [
            'child_step1' => $this->childInfo(now()->subYears(15)->toDateString()),
            'child_step2' => [
                'city_code' => '402101000',
                'barangay_code' => '402101001',
            ],
        ];
    }

    private function seedLocationRows(): void
    {
        DB::table('provinces')->updateOrInsert(['code' => '402100000'], [
            'name' => 'Sample Province',
            'region_code' => '040000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cities')->updateOrInsert(['code' => '402101000'], [
            'name' => 'Sample City',
            'region_code' => '040000000',
            'province_code' => '402100000',
            'is_city' => true,
            'city_class' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('barangays')->updateOrInsert(['code' => '402101001'], [
            'name' => 'Sample Barangay',
            'city_code' => '402101000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
