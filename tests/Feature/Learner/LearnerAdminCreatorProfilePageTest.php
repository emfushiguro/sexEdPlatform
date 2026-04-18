<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\AdminCreatorProfile;
use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LearnerAdminCreatorProfilePageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_learner_can_view_admin_creator_public_page(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('instructor', 'web');
        Role::findOrCreate('admin', 'web');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'admin_creator_learner_' . $learner->id,
            'birthdate' => now()->subYears(16)->toDateString(),
            'age_range' => 'teens_13_17',
            'gender' => 'female',
            'barangay' => 'Barangay 1',
            'bio' => 'Learner profile',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        AdminCreatorProfile::query()->create([
            'user_id' => $admin->id,
            'public_display_name' => 'Creator Admin',
            'bio' => 'Builds official learning modules.',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.admin-creators.show', $admin))
            ->assertOk()
            ->assertSee('View Full Information page', false)
            ->assertSee('Platform Developer', false)
            ->assertSee('Creator Admin', false);
    }

    public function test_non_admin_target_returns_404_for_admin_creator_public_page(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('instructor', 'web');
        Role::findOrCreate('admin', 'web');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');

        $notAdmin = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $notAdmin->assignRole('instructor');

        $this->actingAs($learner)
            ->get(route('learner.admin-creators.show', $notAdmin))
            ->assertNotFound();
    }
}
