<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminQuizReviewOverviewModeTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_quiz_review_renders_all_questions_with_correct_answer_indicators(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequestWithQuizQuestions();

        $this->actingAs($admin)
            ->get(route('admin.content-reviews.show', $reviewRequest))
            ->assertOk()
            ->assertSee('Quiz: Module Readiness Quiz', false)
            ->assertSee('What is 2 + 2?', false)
            ->assertSee('Four', false)
            ->assertSee('Correct Answer', false);
    }

    private function createPendingReviewRequestWithQuizQuestions(): ModuleReviewRequest
    {
        $instructor = $this->createUserWithRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'in_review',
        ]);

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => ['id' => $module->id, 'title' => $module->title],
                'lessons' => [],
                'quizzes' => [
                    [
                        'attributes' => [
                            'id' => 991,
                            'title' => 'Module Readiness Quiz',
                            'description' => 'Review quiz',
                            'passing_score' => 75,
                        ],
                        'questions' => [
                            [
                                'attributes' => [
                                    'id' => 1,
                                    'question_text' => 'What is 2 + 2?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                ],
                                'options' => [
                                    ['option_text' => 'Three', 'is_correct' => false],
                                    ['option_text' => 'Four', 'is_correct' => true],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'submitted_by' => $instructor->id,
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);

        return ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'in_review',
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
