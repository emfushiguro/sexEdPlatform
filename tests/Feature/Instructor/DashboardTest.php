<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_instructor_dashboard_returns_all_required_view_data(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
             ->get(route('instructor.dashboard'))
             ->assertOk()
             ->assertViewHas(['stats', 'recentActivities', 'pendingEnrollments', 'moduleStats', 'quizStats', 'instructorModules', 'calendarDates']);
    }

    public function test_guest_cannot_access_instructor_dashboard(): void
    {
        $this->get(route('instructor.dashboard'))
             ->assertRedirect();
    }

    public function test_learner_cannot_access_instructor_dashboard(): void
    {
        $learner = User::factory()->create();
        $learner->assignRole('learner');

        $this->actingAs($learner)
             ->get(route('instructor.dashboard'))
             ->assertStatus(403);
    }
}
