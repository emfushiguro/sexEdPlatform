<?php

namespace App\Services\Admin;

use App\Enums\ContentReportTargetType;
use App\Enums\ModerationCaseSource;
use App\Models\ContentReport;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\Module;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ModerationSuspensionDashboardService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array{stats: array<string, int>, suspensions: LengthAwarePaginator, reportCases: LengthAwarePaginator}
     */
    public function buildIndexPayload(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $role = (string) ($filters['role'] ?? '');
        $severity = (string) ($filters['severity'] ?? '');
        $trigger = (string) ($filters['trigger'] ?? '');
        $status = (string) ($filters['status'] ?? '');
        $appealStatus = (string) ($filters['appeal_status'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'latest');
        $perPage = max(5, min(100, (int) ($filters['per_page'] ?? 15)));

        $query = UserSuspension::query()
            ->with([
                'user:id,name,email,role',
                'enforcementAction:id,severity_level,trigger_type,action_type',
                'moderationCase:id,case_reference_code',
                'appeals:id,user_suspension_id,status,submitted_at',
            ])
            ->when($role !== '', function ($builder) use ($role): void {
                $builder->whereHas('user', fn ($userQuery) => $userQuery->where('role', $role));
            })
            ->when($severity !== '', function ($builder) use ($severity): void {
                $builder->whereHas('enforcementAction', fn ($actionQuery) => $actionQuery->where('severity_level', $severity));
            })
            ->when($trigger !== '', function ($builder) use ($trigger): void {
                $builder->whereHas('enforcementAction', fn ($actionQuery) => $actionQuery->where('trigger_type', $trigger));
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status))
            ->when($appealStatus !== '', fn ($builder) => $builder->where('appeal_status', $appealStatus))
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('moderationCase', fn ($caseQuery) => $caseQuery->where('case_reference_code', 'like', '%' . $search . '%'));
                });
            });

        match ($sort) {
            'oldest' => $query->oldest('created_at'),
            'ending_soon' => $query->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')->orderBy('ends_at'),
            default => $query->latest('id'),
        };

        $suspensions = $query->paginate($perPage)->withQueryString();

        $reportCases = ModerationCase::query()
            ->with([
                'reporter:id,name,email,role',
                'reportedUser:id,name,email,role',
            ])
            ->whereIn('case_source', [
                ModerationCaseSource::ChatReport->value,
                ModerationCaseSource::LearnerReport->value,
            ])
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($nested) use ($search): void {
                    $nested->where('case_reference_code', 'like', '%' . $search . '%')
                        ->orWhere('content_type', 'like', '%' . $search . '%')
                        ->orWhereHas('reporter', function ($reporterQuery) use ($search): void {
                            $reporterQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('reportedUser', function ($reportedQuery) use ($search): void {
                            $reportedQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(10, ['*'], 'reports_page')
            ->withQueryString();

        $this->enrichReportCaseSummaries($reportCases->getCollection());

        return [
            'stats' => [
                'total' => UserSuspension::query()->count(),
                'active' => UserSuspension::query()->where('status', 'active')->count(),
                'appeals_pending' => UserSuspension::query()->where('appeal_status', 'appeal_pending')->count(),
                'permanent' => UserSuspension::query()->where('status', 'active')->whereNull('ends_at')->count(),
                'report_queue' => ModerationCase::query()
                    ->whereIn('case_source', [
                        ModerationCaseSource::ChatReport->value,
                        ModerationCaseSource::LearnerReport->value,
                    ])
                    ->whereIn('status', ['reported', 'triaged', 'investigating'])
                    ->count(),
            ],
            'suspensions' => $suspensions,
            'reportCases' => $reportCases,
        ];
    }

    /**
     * @return array{suspension: UserSuspension, appeals: \Illuminate\Database\Eloquent\Collection<int, SuspensionAppeal>}
     */
    public function buildShowPayload(UserSuspension $suspension): array
    {
        $suspension->loadMissing([
            'user:id,name,email,role',
            'enforcementAction:id,user_id,moderation_case_id,severity_level,trigger_type,action_type,starts_at,ends_at,notes',
            'moderationCase:id,case_reference_code,status,decision,notes',
            'createdByAdmin:id,name',
            'revokedByAdmin:id,name',
        ]);

        $appeals = SuspensionAppeal::query()
            ->where('user_suspension_id', $suspension->id)
            ->with([
                'reviewedByAdmin:id,name',
                'threadMessages:id,suspension_appeal_id,sender_id,sender_role,message,created_at',
            ])
            ->latest('id')
            ->get();

        return [
            'suspension' => $suspension,
            'appeals' => $appeals,
        ];
    }

    /**
     * @return array{case: ModerationCase, sourceType: string, sourceReport: MessageReport|ContentReport|null, conversationMessages: Collection<int, Message>, targetModel: Module|User|null}
     */
    public function buildReportPayload(ModerationCase $moderationCase): array
    {
        $moderationCase->loadMissing([
            'reporter:id,name,email,role',
            'reportedUser:id,name,email,role',
            'reviewedByAdmin:id,name,email,role',
        ]);

        if ($this->caseSourceValue($moderationCase) === ModerationCaseSource::ChatReport->value) {
            $sourceReport = MessageReport::query()
                ->with([
                    'reporter:id,name,email,role',
                    'reviewedByAdmin:id,name,email,role',
                    'message.sender:id,name,email,role',
                    'conversation.participantOne:id,name,email,role',
                    'conversation.participantTwo:id,name,email,role',
                ])
                ->find($moderationCase->content_id);

            $conversationMessages = $sourceReport?->conversation
                ? $sourceReport->conversation->messages()
                    ->with('sender:id,name,email,role')
                    ->oldest('id')
                    ->get()
                : new Collection();

            return [
                'case' => $moderationCase,
                'sourceType' => 'chat',
                'sourceReport' => $sourceReport,
                'conversationMessages' => $conversationMessages,
                'targetModel' => null,
            ];
        }

        $sourceReport = ContentReport::query()
            ->with([
                'reporter:id,name,email,role',
                'assignedAdmin:id,name,email,role',
                'resolvedBy:id,name,email,role',
                'activities.actor:id,name,email,role',
            ])
            ->find($moderationCase->content_id);

        $targetModel = $sourceReport ? $this->resolveLearnerReportTarget($sourceReport) : null;

        return [
            'case' => $moderationCase,
            'sourceType' => 'learner',
            'sourceReport' => $sourceReport,
            'conversationMessages' => new Collection(),
            'targetModel' => $targetModel,
        ];
    }

    /**
     * @param  Collection<int, ModerationCase>  $cases
     */
    private function enrichReportCaseSummaries(Collection $cases): void
    {
        $cases->each(function (ModerationCase $case): void {
            if ($this->caseSourceValue($case) === ModerationCaseSource::ChatReport->value) {
                $report = MessageReport::query()
                    ->with('message:id,message_body,sender_id')
                    ->find($case->content_id);

                $case->setAttribute('dashboard_report_summary', [
                    'title' => $report?->message?->message_body
                        ? str($report->message->message_body)->limit(90)->toString()
                        : 'Reported chat message',
                    'detail' => $report?->reason ?: (string) ($report?->reason_code ?? 'No reason recorded'),
                    'status' => (string) ($report?->status ?? $case->status->value),
                ]);

                return;
            }

            $report = ContentReport::query()->find($case->content_id);
            $target = $report ? $this->resolveLearnerReportTarget($report) : null;
            $targetName = $target instanceof Module
                ? $target->title
                : ($target instanceof User ? $target->name : 'Reported learner content');

            $case->setAttribute('dashboard_report_summary', [
                'title' => $targetName,
                'detail' => (string) ($report?->reason_code ?? 'No reason recorded'),
                'status' => (string) ($report?->status?->value ?? $case->status->value),
            ]);
        });
    }

    private function resolveLearnerReportTarget(ContentReport $report): Module|User|null
    {
        $targetType = $report->target_type instanceof ContentReportTargetType
            ? $report->target_type
            : ContentReportTargetType::from((string) $report->target_type);

        if ($targetType === ContentReportTargetType::Module) {
            return Module::query()->with('creator:id,name,email,role')->find((int) $report->target_id);
        }

        return User::query()->find((int) $report->target_id);
    }

    private function caseSourceValue(ModerationCase $case): string
    {
        return $case->case_source instanceof ModerationCaseSource
            ? $case->case_source->value
            : (string) $case->case_source;
    }
}
