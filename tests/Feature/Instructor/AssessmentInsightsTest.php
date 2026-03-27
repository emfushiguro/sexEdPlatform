<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_dashboard_includes_required_metrics(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create([
            'title' => 'Healthy Boundaries',
            'created_by' => $instructor->id,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'title' => 'Boundaries Quiz',
            'is_active' => true,
        ]);

        $learnerLow = User::factory()->create();
        $learnerLow->assignRole('learner');

        $learnerOk = User::factory()->create();
        $learnerOk->assignRole('learner');

        QuizAttempt::query()->create([
            'user_id' => $learnerLow->id,
            'quiz_id' => $quiz->id,
            'answers' => ['q1' => 'A'],
            'score' => 45,
            'passed' => false,
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHours(3),
        ]);

        QuizAttempt::query()->create([
            'user_id' => $learnerOk->id,
            'quiz_id' => $quiz->id,
            'answers' => ['q1' => 'B'],
            'score' => 88,
            'passed' => true,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHours(2),
        ]);

        QuizAttempt::query()->create([
            'user_id' => $learnerOk->id,
            'quiz_id' => $quiz->id,
            'answers' => ['q1' => 'C'],
            'score' => 92,
            'passed' => true,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($instructor)->get(route('instructor.assessments.index', [
            'low_score_threshold' => 60,
            'low_activity_threshold' => 2,
        ]));

        $response->assertOk()
            ->assertViewHasAll([
                'scoreDistributionByModule',
                'attemptCountByLearner',
                'atRiskLearners',
                'assessmentThresholds',
            ])
            ->assertViewHas('atRiskLearners', function ($atRiskLearners) use ($learnerLow): bool {
                return collect($atRiskLearners)->contains(function ($entry) use ($learnerLow) {
                    return (int) ($entry['learner_id'] ?? 0) === $learnerLow->id
                        && (bool) ($entry['is_at_risk'] ?? false) === true;
                });
            })
            ->assertSee('data-testid="assessment-score-distribution"', false)
            ->assertSee('data-testid="assessment-at-risk-table"', false)
            ->assertSee('data-testid="assessment-attempt-count-table"', false);
    }
}
