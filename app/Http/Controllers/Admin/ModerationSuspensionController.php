<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentReportAction;
use App\Enums\ContentReportStatus;
use App\Enums\EnforcementActionType;
use App\Enums\MessageReportAction;
use App\Enums\ModerationCaseSource;
use App\Enums\ViolationSeverity;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterModerationSuspensionsRequest;
use App\Models\ContentReport;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\User;
use App\Models\UserSuspension;
use App\Services\ContentReportService;
use App\Services\Admin\ModerationSuspensionDashboardService;
use App\Services\Moderation\EnforcementActionService;
use App\Services\Moderation\SourceAdapters\ChatReportModerationAdapter;
use App\Services\Moderation\SuspensionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModerationSuspensionController extends Controller
{
    public function __construct(
        private readonly ModerationSuspensionDashboardService $dashboardService,
        private readonly ChatReportModerationAdapter $chatReportModerationAdapter,
        private readonly ContentReportService $contentReportService,
        private readonly EnforcementActionService $enforcementActionService,
        private readonly SuspensionService $suspensionService,
    ) {
    }

    public function index(FilterModerationSuspensionsRequest $request): View
    {
        $filters = $request->validated();
        $payload = $this->dashboardService->buildIndexPayload($filters);

        return view('admin.moderation.suspensions.index', [
            'suspensions' => $payload['suspensions'],
            'reportCases' => $payload['reportCases'],
            'stats' => $payload['stats'],
            'filters' => [
                'search' => (string) ($filters['search'] ?? ''),
                'role' => (string) ($filters['role'] ?? ''),
                'severity' => (string) ($filters['severity'] ?? ''),
                'trigger' => (string) ($filters['trigger'] ?? ''),
                'status' => (string) ($filters['status'] ?? ''),
                'appeal_status' => (string) ($filters['appeal_status'] ?? ''),
                'sort' => (string) ($filters['sort'] ?? 'latest'),
                'per_page' => (int) ($filters['per_page'] ?? 15),
            ],
        ]);
    }

    public function show(UserSuspension $userSuspension): View
    {
        $payload = $this->dashboardService->buildShowPayload($userSuspension);

        return view('admin.moderation.suspensions.show', $payload);
    }

    public function showReport(ModerationCase $moderationCase): View
    {
        $this->guardReportCase($moderationCase);

        return view('admin.moderation.suspensions.report-show', [
            ...$this->dashboardService->buildReportPayload($moderationCase),
            'messageActions' => MessageReportAction::cases(),
            'messageStatuses' => ['open', 'under_review', 'resolved', 'dismissed'],
            'contentActions' => ContentReportAction::cases(),
            'contentStatuses' => ContentReportStatus::cases(),
        ]);
    }

    public function updateReport(Request $request, ModerationCase $moderationCase): RedirectResponse
    {
        $this->guardReportCase($moderationCase);

        if ($this->caseSourceValue($moderationCase) === ModerationCaseSource::ChatReport->value) {
            $this->updateMessageReport($request, $moderationCase);
        } else {
            $this->updateLearnerReport($request, $moderationCase);
        }

        return redirect()
            ->route('admin.moderation-suspensions.reports.show', $moderationCase)
            ->with('success', 'Centralized moderation report decision recorded.');
    }

    private function updateMessageReport(Request $request, ModerationCase $moderationCase): void
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'under_review', 'resolved', 'dismissed'])],
            'action_taken' => ['required', Rule::in(MessageReportAction::values())],
            'moderation_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var MessageReport|null $messageReport */
        $messageReport = MessageReport::query()
            ->with('message.sender')
            ->find($moderationCase->content_id);

        abort_unless($messageReport, 404);

        $previousAction = (string) ($messageReport->action_taken ?? '');

        $messageReport->forceFill([
            'status' => $validated['status'],
            'action_taken' => $validated['action_taken'],
            'moderation_notes' => trim((string) ($validated['moderation_notes'] ?? '')) ?: null,
            'reviewed_by_admin_id' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $messageReport = $messageReport->fresh(['message.sender']);
        $this->chatReportModerationAdapter->syncReport($messageReport);

        if ($previousAction !== $validated['action_taken']) {
            $this->issueEnforcementForMessageAction(
                $messageReport,
                $request->user(),
                MessageReportAction::from($validated['action_taken']),
            );
        }

        $this->markCaseReviewed($moderationCase, $request->user(), (string) ($validated['moderation_notes'] ?? ''));
    }

    private function updateLearnerReport(Request $request, ModerationCase $moderationCase): void
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ContentReportStatus::values())],
            'action' => ['required', Rule::in(ContentReportAction::values())],
            'moderation_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        /** @var ContentReport|null $contentReport */
        $contentReport = ContentReport::query()->find($moderationCase->content_id);

        abort_unless($contentReport, 404);

        $this->contentReportService->applyAdminAction(
            $contentReport,
            $request->user(),
            ContentReportStatus::from($validated['status']),
            ContentReportAction::from($validated['action']),
            trim((string) ($validated['moderation_notes'] ?? '')) ?: null,
        );

        $this->markCaseReviewed($moderationCase, $request->user(), (string) ($validated['moderation_notes'] ?? ''));
    }

    private function issueEnforcementForMessageAction(
        MessageReport $messageReport,
        ?User $admin,
        MessageReportAction $action,
    ): void {
        $reportedUser = $messageReport->message?->sender;

        if (!$reportedUser) {
            return;
        }

        $enforcementType = match ($action) {
            MessageReportAction::Warning => EnforcementActionType::Warning,
            MessageReportAction::ChatRestriction => EnforcementActionType::ChatRestriction,
            MessageReportAction::TemporarySuspension => EnforcementActionType::TemporarySuspension,
            default => null,
        };

        if (!$enforcementType) {
            return;
        }

        $moderationCase = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::ChatReport->value)
            ->where('content_type', 'message_report')
            ->where('content_id', $messageReport->id)
            ->first();

        $enforcementAction = $this->enforcementActionService->issueAction(
            user: $reportedUser,
            actionType: $enforcementType,
            severity: $action === MessageReportAction::TemporarySuspension ? ViolationSeverity::Major : ViolationSeverity::Moderate,
            triggerType: 'manual',
            moderationCase: $moderationCase,
            issuedByAdmin: $admin,
            notes: $messageReport->moderation_notes,
        );

        if ($enforcementType === EnforcementActionType::TemporarySuspension) {
            $this->suspensionService->createFromEnforcementAction($enforcementAction, $admin);
        }
    }

    private function markCaseReviewed(ModerationCase $moderationCase, ?User $admin, string $notes): void
    {
        $freshCase = $moderationCase->fresh();

        if (!$freshCase) {
            return;
        }

        $freshCase->forceFill([
            'reviewed_by_admin_id' => $admin?->id,
            'reviewed_at' => now(),
            'notes' => trim($notes) !== '' ? trim($notes) : $freshCase->notes,
        ])->save();
    }

    private function guardReportCase(ModerationCase $moderationCase): void
    {
        abort_unless(
            in_array($this->caseSourceValue($moderationCase), [
                ModerationCaseSource::ChatReport->value,
                ModerationCaseSource::LearnerReport->value,
            ], true),
            404,
        );
    }

    private function caseSourceValue(ModerationCase $moderationCase): string
    {
        return $moderationCase->case_source instanceof ModerationCaseSource
            ? $moderationCase->case_source->value
            : (string) $moderationCase->case_source;
    }
}
