<?php

namespace Tests\Feature\Learner;

use App\Enums\InteractiveActivityType;
use App\Models\Certificate;
use App\Models\ConnectorModuleSeatAssignment;
use App\Models\InteractiveActivity;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\Connectors\ConnectorModulePurchaseService;
use App\Services\Modules\ModuleAccessService;
use Illuminate\Support\Str;
use Tests\Feature\Connectors\ConnectorTestHelpers;

trait ConnectorLearnerAccessHelpers
{
    use ConnectorTestHelpers;

    /** @return array<string, mixed> */
    protected function connectorLearnerFixture(): array
    {
        [$manager, $connector, $module, $listing, $purchase, $payment] = $this->createConnectorModulePaymentState([
            'is_published' => true,
            'current_review_status' => 'approved',
            'min_age' => 18,
            'max_age' => 100,
        ]);
        app(ConnectorModulePurchaseService::class)->completePayment($payment, 'gcash', 'pay_learner_access');

        $learner = $this->createAdultConnectorMember($connector);
        $learner->gamification()->create([
            'level' => 1,
            'xp' => 0,
            'score' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'text_content' => '<p>Protected learning text.</p>',
        ]);
        $activity = InteractiveActivity::create([
            'lesson_topic_id' => $topic->id,
            'placement' => 'inside_topic',
            'block_uuid' => (string) Str::uuid(),
            'activity_type' => InteractiveActivityType::MATCHING,
            'title' => 'Protected activity',
            'instructions' => 'Match the items.',
            'explanation' => 'Activity explanation.',
            'configuration' => [
                'schema_version' => 1,
                'pairs' => [
                    ['id' => 'pair-1', 'left' => ['id' => 'left-1', 'kind' => 'text', 'value' => 'One'], 'right' => ['id' => 'right-1', 'kind' => 'text', 'value' => 'First']],
                ],
            ],
            'revision' => 1,
        ]);
        $checkpointTopic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive_checkpoint',
            'order' => 2,
        ]);
        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'is_active' => true,
            'attempt_limit' => 3,
        ]);
        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Choose the safe answer.',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);
        $checkpoint = QuizQuestion::query()->create([
            'checkpoint_topic_id' => $checkpointTopic->id,
            'checkpoint_block_uuid' => (string) Str::uuid(),
            'question_text' => 'Checkpoint question.',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);
        $certificate = Certificate::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'learner_name_snapshot' => $learner->name,
            'module_title_snapshot' => $module->title,
            'issued_at' => now()->subDay(),
        ]);

        $membership = $connector->memberships()->where('user_id', $learner->id)->firstOrFail();
        $entitlement = $purchase->fresh()->entitlement;
        $assignment = ConnectorModuleSeatAssignment::query()->create([
            'connector_module_entitlement_id' => $entitlement->id,
            'user_id' => $learner->id,
            'connector_membership_id' => $membership->id,
            'source_type' => ConnectorModuleSeatAssignment::SOURCE_DIRECT,
            'status' => ConnectorModuleSeatAssignment::STATUS_ACTIVE,
            'consumes_seat' => true,
            'assigned_at' => now(),
        ]);
        app(ModuleAccessService::class)->grantConnectorAssignment($assignment);

        return compact(
            'manager',
            'connector',
            'module',
            'lesson',
            'topic',
            'checkpointTopic',
            'activity',
            'quiz',
            'question',
            'checkpoint',
            'learner',
            'entitlement',
            'assignment',
            'certificate',
        );
    }

    protected function revokeConnectorLearnerAccess(array $fixture): void
    {
        app(ModuleAccessService::class)->deactivateSource(
            \App\Models\ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT,
            $fixture['assignment']->id,
            'membership_removed',
        );
    }
}
