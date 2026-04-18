<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\AdminCreatorProfile;
use App\Models\LearnerProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCreatorPublicViewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_admin_creator_view_shows_identity_and_contribution_sections(): void
    {
        $this->withoutMiddleware(EnsureProfileCompleted::class);
        Role::findOrCreate('learner', 'web');
        Role::findOrCreate('admin', 'web');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        /** @var User $learner */
        $learner->assignRole('learner');

        LearnerProfile::query()->create([
            'user_id' => $learner->id,
            'username' => 'public_admin_view_' . $learner->id,
            'birthdate' => now()->subYears(15)->toDateString(),
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
            'public_display_name' => 'Transparency Admin',
            'bio' => 'Maintains public learning content quality.',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
        ]);

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
            'is_published' => true,
            'current_review_status' => null,
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('learner.admin-creators.show', $admin))
            ->assertOk()
            ->assertSee('View Full Information page', false)
            ->assertSee('Transparency Admin', false)
            ->assertSee('Conscious Connections Team', false)
            ->assertSee('Modules Published', false)
            ->assertSee('Learners Reached', false)
            ->assertSee('Latest Updated Module', false);
    }
}
