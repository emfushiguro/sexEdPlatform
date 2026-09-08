<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use App\Models\ParentChildAccount;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_returns_all_required_view_data(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
             ->get(route('instructor.dashboard'))
             ->assertOk()
             ->assertViewHas([
                 'stats',
                 'recentActivities',
                 'pendingEnrollments',
                 'moduleStats',
                 'quizStats',
                 'instructorModules',
                 'calendarDates',
                 'statCards',
                 'avgQuizScoreScopes',
                 'dashboardHero',
             ])
             ->assertViewHas('statCards', fn ($cards) => count($cards) === 6)
             ->assertViewHas('avgQuizScoreScopes', fn ($scopes) => isset($scopes['defaultScope'], $scopes['all_time'], $scopes['last_30_days']));
    }

    public function test_instructor_dashboard_renders_phase_one_markers(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('data-testid="instructor-hero"', false)
            ->assertSee('data-testid="stats-grid"', false)
            ->assertSee('data-testid="avg-score-scope-toggle"', false)
            ->assertSee('data-testid="quick-actions-section"', false)
            ->assertSee('data-testid="modules-carousel-section"', false);
    }

    public function test_guest_cannot_access_instructor_dashboard(): void
    {
        $this->get(route('instructor.dashboard'))
             ->assertRedirect();
    }

    public function test_learner_cannot_access_instructor_dashboard(): void
    {
        /** @var User $learner */
        $learner = User::factory()->create();
        $learner->assignRole('learner');

        $this->actingAs($learner)
             ->get(route('instructor.dashboard'))
             ->assertStatus(403);
    }

    public function test_guardian_classifications_exclude_pending_and_revoked_links(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $guardian = User::factory()->create([
            'name' => 'Pending Guardian',
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $dependent = User::factory()->create([
            'name' => 'Visible Dependent',
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
        ]);
        ModuleEnrollment::query()->create([
            'user_id' => $dependent->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'verification_status' => 'approved',
            'relationship_verified_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.users.show', $dependent))
            ->assertOk()
            ->assertDontSeeText($guardian->name)
            ->assertSeeText($dependent->name);
    }
}
