<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContentGovernanceService
{
    public function __construct(
        private readonly AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function submitForReview(Module $module, User $actor): ModuleReviewRequest
    {
        return DB::transaction(function () use ($module, $actor) {
            $revision = $this->createRevisionSnapshot($module, $actor);

            $reviewRequest = ModuleReviewRequest::query()->create([
                'module_id' => $module->id,
                'module_revision_id' => $revision->id,
                'status' => 'in_review',
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
            ]);

            $module->forceFill([
                'current_review_status' => 'in_review',
            ])->save();

            return $reviewRequest;
        });
    }

    public function createAdminOwnedModule(array $attributes, User $admin): Module
    {
        return DB::transaction(function () use ($attributes, $admin) {
            $module = Module::query()->create([
                ...$attributes,
                'created_by' => $admin->id,
                'content_owner_type' => 'admin',
                'current_review_status' => 'approved',
                'is_published' => true,
            ]);

            $revision = ModuleRevision::query()->create([
                'module_id' => $module->id,
                'revision_number' => 1,
                'snapshot_payload' => $this->buildSnapshotPayload($module->fresh()),
                'submitted_by' => $admin->id,
                'status' => 'approved',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
            ]);

            $module->forceFill([
                'published_revision_id' => $revision->id,
                'published_by_admin_id' => $admin->id,
            ])->save();

            $this->adminActivityLogService->logModelMutation(
                action: 'admin_modules.publish',
                entity: $module,
                after: $module->fresh()->only([
                    'id',
                    'title',
                    'created_by',
                    'content_owner_type',
                    'published_revision_id',
                    'published_by_admin_id',
                ]),
                adminUserId: $admin->id,
            );

            return $module->fresh();
        });
    }

    public function approveReview(ModuleReviewRequest $reviewRequest, User $admin, ?string $notes = null): ModuleRevision
    {
        return DB::transaction(function () use ($reviewRequest, $admin, $notes) {
            $reviewRequest->loadMissing('module', 'revision');

            $revision = $reviewRequest->revision;
            $module = $reviewRequest->module;

            $revision->forceFill([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'review_feedback' => $notes,
            ])->save();

            $reviewRequest->forceFill([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'feedback' => $notes,
            ])->save();

            $module->forceFill([
                'is_published' => true,
                'published_revision_id' => $revision->id,
                'published_by_admin_id' => $module->content_owner_type === 'instructor' ? $admin->id : null,
                'current_review_status' => 'approved',
            ])->save();

            $this->adminActivityLogService->logModelMutation(
                action: 'content_reviews.approve',
                entity: $reviewRequest,
                after: [
                    'module_id' => $module->id,
                    'module_revision_id' => $revision->id,
                    'status' => 'approved',
                ],
                meta: [
                    'notes' => $notes,
                ],
                adminUserId: $admin->id,
            );

            return $revision->fresh();
        });
    }

    public function rejectReview(ModuleReviewRequest $reviewRequest, User $admin, string $feedback): ModuleRevision
    {
        if (trim($feedback) === '') {
            throw new InvalidArgumentException('Review feedback is required when rejecting a module submission.');
        }

        return DB::transaction(function () use ($reviewRequest, $admin, $feedback) {
            $reviewRequest->loadMissing('module', 'revision');

            $revision = $reviewRequest->revision;
            $module = $reviewRequest->module;

            $revision->forceFill([
                'status' => 'needs_revision',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'review_feedback' => $feedback,
            ])->save();

            $reviewRequest->forceFill([
                'status' => 'needs_revision',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'feedback' => $feedback,
            ])->save();

            $module->forceFill([
                'current_review_status' => 'needs_revision',
            ])->save();

            $this->adminActivityLogService->logModelMutation(
                action: 'content_reviews.reject',
                entity: $reviewRequest,
                after: [
                    'module_id' => $module->id,
                    'module_revision_id' => $revision->id,
                    'status' => 'needs_revision',
                ],
                meta: [
                    'feedback' => $feedback,
                ],
                adminUserId: $admin->id,
            );

            return $revision->fresh();
        });
    }

    public function createRevisionSnapshot(Module $module, User $actor): ModuleRevision
    {
        $module->loadMissing([
            'lessons.topics',
            'quizzes.questions.options',
            'finalQuiz.questions.options',
        ]);

        $nextRevisionNumber = ((int) $module->revisions()->max('revision_number')) + 1;

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => $nextRevisionNumber,
            'snapshot_payload' => $this->buildSnapshotPayload($module),
            'submitted_by' => $actor->id,
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);

        $module->forceFill([
            'current_review_status' => 'in_review',
        ])->save();

        return $revision;
    }

    private function buildSnapshotPayload(Module $module): array
    {
        return [
            'module' => $module->only([
                'id',
                'title',
                'description',
                'thumbnail',
                'min_age',
                'max_age',
                'age_specific_content',
                'order',
                'duration_minutes',
                'is_published',
                'is_premium',
                'enrollment_mode',
                'final_quiz_id',
                'certificate_pass_score',
                'created_by',
                'content_owner_type',
            ]),
            'lessons' => $module->lessons->map(fn ($lesson) => [
                'attributes' => $lesson->only([
                    'id',
                    'module_id',
                    'title',
                    'description',
                    'order',
                    'duration',
                    'is_published',
                    'text_content',
                ]),
                'topics' => $lesson->topics->map(fn ($topic) => $topic->only([
                    'id',
                    'lesson_id',
                    'title',
                    'type',
                    'video_provider',
                    'video_id',
                    'video_file_path',
                    'text_content',
                    'file_path',
                    'quiz_id',
                    'interactive_config',
                    'image_attachments',
                    'slideshow_data',
                    'duration',
                    'is_prerequisite',
                    'order',
                ]))->values()->all(),
            ])->values()->all(),
            'quizzes' => $module->quizzes->map(fn ($quiz) => [
                'attributes' => $quiz->only([
                    'id',
                    'module_id',
                    'lesson_id',
                    'title',
                    'description',
                    'passing_score',
                    'time_limit',
                    'is_active',
                ]),
                'questions' => $quiz->questions->map(fn ($question) => [
                    'attributes' => $question->only([
                        'id',
                        'quiz_id',
                        'question_text',
                        'question_type',
                        'points',
                        'order',
                        'acceptable_answers',
                        'case_sensitive',
                        'word_bank',
                        'image_path',
                    ]),
                    'options' => $question->options->map(fn ($option) => $option->only([
                        'id',
                        'quiz_question_id',
                        'option_text',
                        'is_correct',
                        'order',
                    ]))->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
