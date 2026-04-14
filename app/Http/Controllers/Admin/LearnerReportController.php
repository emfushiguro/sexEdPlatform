<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentReportAction;
use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContentReportRequest;
use App\Models\ContentReport;
use App\Models\Module;
use App\Models\User;
use App\Services\ContentReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnerReportController extends Controller
{
    public function __construct(
        private readonly ContentReportService $contentReportService,
    ) {
    }

    public function index(Request $request): View
    {
        $status = (string) $request->string('status');
        $targetType = (string) $request->string('target_type');
        $search = trim((string) $request->string('search'));

        $reports = ContentReport::query()
            ->with(['reporter', 'assignedAdmin'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($targetType !== '', fn ($query) => $query->where('target_type', $targetType))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('reason_code', 'like', '%' . $search . '%')
                        ->orWhereHas('reporter', function ($reporterQuery) use ($search) {
                            $reporterQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $moduleIds = $reports->getCollection()
            ->where('target_type', 'module')
            ->pluck('target_id')
            ->unique()
            ->values();
        $instructorIds = $reports->getCollection()
            ->where('target_type', 'instructor')
            ->pluck('target_id')
            ->unique()
            ->values();

        $moduleTitles = Module::query()
            ->whereIn('id', $moduleIds)
            ->pluck('title', 'id');

        $instructorNames = User::query()
            ->whereIn('id', $instructorIds)
            ->pluck('name', 'id');

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status,
            'targetType' => $targetType,
            'search' => $search,
            'statuses' => ContentReportStatus::cases(),
            'moduleTitles' => $moduleTitles,
            'instructorNames' => $instructorNames,
        ]);
    }

    public function show(ContentReport $report): View
    {
        $report->load([
            'reporter',
            'assignedAdmin',
            'resolvedBy',
            'activities.actor',
        ]);

        $targetModel = null;
        $targetType = $report->target_type instanceof ContentReportTargetType
            ? $report->target_type
            : ContentReportTargetType::from((string) $report->target_type);

        if ($targetType === ContentReportTargetType::Module) {
            $targetModel = Module::query()->find((int) $report->target_id);
        }

        if ($targetType === ContentReportTargetType::Instructor) {
            $targetModel = User::query()->find((int) $report->target_id);
        }

        return view('admin.reports.show', [
            'report' => $report,
            'statuses' => ContentReportStatus::cases(),
            'actions' => ContentReportAction::cases(),
            'targetModel' => $targetModel,
        ]);
    }

    public function update(UpdateContentReportRequest $request, ContentReport $report): RedirectResponse
    {
        $status = ContentReportStatus::from((string) $request->string('status'));
        $action = ContentReportAction::from((string) $request->string('action'));

        $this->contentReportService->applyAdminAction(
            $report,
            $request->user(),
            $status,
            $action,
            $request->filled('moderation_notes') ? (string) $request->string('moderation_notes') : null,
        );

        return redirect()->route('admin.learner-reports.show', $report)
            ->with('success', 'Report moderation action recorded successfully.');
    }
}
