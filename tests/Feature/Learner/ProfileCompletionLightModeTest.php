<?php

namespace Tests\Feature\Learner;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileCompletionLightModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('learner', 'web');
        $this->seedLocationRows();
    }

    public function test_adult_profile_completion_sets_light_mode_flag_once(): void
    {
        $user = User::factory()->create([
            'birthdate' => now()->subYears(21)->toDateString(),
            'role' => 'learner',
            'age_bracket_cached' => 'adults',
        ]);
        /** @var User $user */
        $user->assignRole('learner');

        $response = $this->actingAs($user)->post(route('profile.store'), [
            'username' => 'adultlearner'.$user->id,
            'gender' => 'female',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'bio' => 'Adult learner profile.',
        ]);

        $response->assertRedirect(route('learner.dashboard'));
        $response->assertSessionHas('force_light_mode_once', true);
    }

    public function test_teen_profile_completion_does_not_set_light_mode_flag(): void
    {
        $user = User::factory()->create([
            'birthdate' => now()->subYears(16)->toDateString(),
            'role' => 'learner',
            'age_bracket_cached' => 'teens',
        ]);
        /** @var User $user */
        $user->assignRole('learner');

        $response = $this->actingAs($user)->post(route('profile.store'), [
            'username' => 'teenlearner'.$user->id,
            'gender' => 'male',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'bio' => 'Teen learner profile.',
        ]);

        $response->assertRedirect(route('learner.dashboard'));
        $response->assertSessionMissing('force_light_mode_once');
    }

    private function seedLocationRows(): void
    {
        DB::table('provinces')->updateOrInsert(
            ['code' => '402100000'],
            [
                'name' => 'Sample Province',
                'region_code' => '040000000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            [
                'name' => 'Sample City',
                'region_code' => '040000000',
                'province_code' => '402100000',
                'is_city' => true,
                'city_class' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            [
                'name' => 'Sample Barangay',
                'city_code' => '402101000',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
