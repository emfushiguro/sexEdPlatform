<?php

namespace App\Services;

use App\Enums\ContentReportAction;
use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Models\ContentReport;
use App\Models\ContentReportActivity;
use App\Models\InstructorModerationProfile;
use App\Models\Module;
use App\Models\User;
use App\Notifications\Admin\LearnerReportSubmittedNotification;
use App\Notifications\Instructor\InstructorReportOutcomeNotification;
use App\Notifications\Learner\ContentReportStatusNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContentReportService
{
    public function submitOrUpdateActive(
        User $reporter,
        string $targetType,
        int $targetId,
        string $reasonCode,
        ?string $detailsHtml = null,
    ): ContentReport {
        [$resolvedType, $targetModel] = $this->resolveTarget($targetType, $targetId);

        $sanitizedDetails = $this->sanitizeHtml((string) ($detailsHtml ?? ''));

        return DB::transaction(function () use ($reporter, $resolvedType, $targetModel, $reasonCode, $sanitizedDetails) {
            $activeReport = ContentReport::query()
                ->activeForTarget($reporter->id, $resolvedType->value, (int) $targetModel->id)
                ->latest('id')
                ->first();

            if ($activeReport) {
                $activeReport->update([
                    'reason_code' => $reasonCode,
                    'details_html' => $sanitizedDetails !== '' ? $sanitizedDetails : $activeReport->details_html,
                ]);

                $this->logActivity($activeReport, $reporter, 'report_updated', [
                    'reason_code' => $reasonCode,
                ]);

                return $activeReport->fresh(['reporter']);
            }

            $report = ContentReport::query()->create([
                'reporter_id' => $reporter->id,
                'target_type' => $resolvedType->value,
                'target_id' => (int) $targetModel->id,
                'reason_code' => $reasonCode,
                'status' => ContentReportStatus::Submitted->value,
                'details_html' => $sanitizedDetails !== '' ? $sanitizedDetails : null,
            ]);

            $this->logActivity($report, $reporter, 'report_submitted', [
                'reason_code' => $reasonCode,
            ]);

            User::query()
                ->where('role', 'admin')
                ->orWhereHas('roles', fn ($query) => $query->where('name', 'admin'))
                ->get()
                ->each(fn (User $admin) => $admin->notify(new LearnerReportSubmittedNotification($report)));

            return $report->fresh(['reporter']);
        });
    }

    public function applyAdminAction(
        ContentReport $report,
        User $admin,
        ContentReportStatus $newStatus,
        ContentReportAction $action,
        ?string $moderationNotes = null,
    ): ContentReport {
        $oldStatus = $report->status instanceof ContentReportStatus
            ? $report->status->value
            : (string) $report->status;

        return DB::transaction(function () use ($report, $admin, $newStatus, $action, $moderationNotes, $oldStatus) {
            $updatePayload = [
                'status' => $newStatus->value,
                'assigned_admin_id' => $report->assigned_admin_id ?: $admin->id,
                'latest_outcome_message' => $this->outcomeMessageForStatus($newStatus, $action),
            ];

            if ($newStatus === ContentReportStatus::Resolved) {
                $updatePayload['resolved_by'] = $admin->id;
                $updatePayload['resolved_at'] = now();
                $updatePayload['dismissed_at'] = null;
            }

            if ($newStatus === ContentReportStatus::Dismissed) {
                $updatePayload['dismissed_at'] = now();
                $updatePayload['resolved_by'] = $admin->id;
                $updatePayload['resolved_at'] = now();
            }

            $report->update($updatePayload);

            $this->applyTargetAction($report, $action);

            $this->logActivity($report, $admin, 'moderation_action', [
                'from_status' => $oldStatus,
                'to_status' => $newStatus->value,
                'action_code' => $action->value,
                'notes' => $moderationNotes,
            ]);

            $report->loadMissing('reporter');
            if ($report->reporter) {
                $report->reporter->notify(new ContentReportStatusNotification($report));
            }

            if ($this->resolveReportTargetType($report) === ContentReportTargetType::Instructor) {
                $instructor = User::query()->find((int) $report->target_id);
                if ($instructor) {
                    $instructor->notify(new InstructorReportOutcomeNotification($report));
                }
            }

            return $report->fresh(['reporter', 'assignedAdmin', 'resolvedBy', 'activities.actor']);
        });
    }

    public function sanitizeHtml(string $html): string
    {
        return trim((string) strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><blockquote>'));
    }

    private function resolveTarget(string $targetType, int $targetId): array
    {
        $resolvedType = ContentReportTargetType::tryFrom($targetType);
        if (!$resolvedType) {
            throw new RuntimeException('Unsupported report target type.');
        }

        if ($resolvedType === ContentReportTargetType::Module) {
            $module = Module::query()->find($targetId);
            if (!$module) {
                throw new RuntimeException('Reported module was not found.');
            }

            return [$resolvedType, $module];
        }

        $instructor = User::query()->find($targetId);
        if (!$instructor || !$instructor->isInstructor()) {
            throw new RuntimeException('Reported instructor was not found.');
        }

        return [$resolvedType, $instructor];
    }

    private function applyTargetAction(ContentReport $report, ContentReportAction $action): void
    {
        $targetType = $this->resolveReportTargetType($report);

        if ($targetType === ContentReportTargetType::Module && $action === ContentReportAction::TakeDownModule) {
            $module = Module::query()->find((int) $report->target_id);
            if ($module) {
                $module->update([
                    'is_published' => false,
                    'current_review_status' => 'needs_revision',
                ]);
            }

            return;
        }

        if ($targetType !== ContentReportTargetType::Instructor) {
            return;
        }

        $instructor = User::query()->find((int) $report->target_id);
        if (!$instructor) {
            return;
        }

        $profile = InstructorModerationProfile::query()->firstOrCreate(
            ['user_id' => $instructor->id],
            [
                'warning_count' => 0,
                'escalation_level' => 0,
            ]
        );

        if ($action === ContentReportAction::WarningInstructor) {
            $profile->warning_count = (int) $profile->warning_count + 1;
            $profile->last_violation_at = now();
            $profile->escalation_level = min((int) $profile->warning_count, 4);
            $profile->save();
            return;
        }

        if ($action === ContentReportAction::RestrictInstructorAccount) {
            $profile->current_restriction_status = 'restricted';
            $profile->restriction_starts_at = now();
            $profile->restriction_ends_at = now()->addDays(14);
            $profile->last_violation_at = now();
            $profile->save();
            return;
        }

        if ($action === ContentReportAction::BanInstructor) {
            $profile->current_restriction_status = 'banned';
            $profile->restriction_starts_at = now();
            $profile->restriction_ends_at = null;
            $profile->last_violation_at = now();
            $profile->save();

            $instructor->update([
                'status' => 'archived',
            ]);
            return;
        }
    }

    private function outcomeMessageForStatus(ContentReportStatus $status, ContentReportAction $action): string
    {
        if ($status === ContentReportStatus::UnderReview) {
            return 'Your report is currently under review by the moderation team.';
        }

        if ($status === ContentReportStatus::Dismissed || $action === ContentReportAction::DismissReport) {
            return 'Your report was reviewed and dismissed because no policy violation was confirmed.';
        }

        return 'Your report was reviewed and resolved. Appropriate moderation action has been applied when necessary.';
    }

    private function resolveReportTargetType(ContentReport $report): ContentReportTargetType
    {
        return $report->target_type instanceof ContentReportTargetType
            ? $report->target_type
            : ContentReportTargetType::from((string) $report->target_type);
    }

    private function logActivity(ContentReport $report, ?User $actor, string $activityType, array $data = []): void
    {
        ContentReportActivity::query()->create([
            'content_report_id' => $report->id,
            'actor_id' => $actor?->id,
            'activity_type' => $activityType,
            'from_status' => $data['from_status'] ?? null,
            'to_status' => $data['to_status'] ?? null,
            'action_code' => $data['action_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data,
        ]);
    }
}
