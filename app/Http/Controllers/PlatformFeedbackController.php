<?php

namespace App\Http\Controllers;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Http\Requests\StorePlatformFeedbackRequest;
use App\Models\Connector;
use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackHistory;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Support\PlatformFeedbackSubmissionService;
use App\Services\Support\SupportLayoutResolver;
use App\Services\Support\SupportRouteContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformFeedbackController extends Controller
{
    public function __construct(
        private readonly PlatformFeedbackSubmissionService $service,
        private readonly SupportLayoutResolver $layouts,
        private readonly ConnectorAccessService $connectorAccess,
        private readonly SupportRouteContext $routeContext,
    ) {}

    public function create(Request $request, ?Connector $connector = null): View
    {
        $this->authorizeConnector($request, $connector);
        $requestedType = (string) $request->query('type', '');
        $selectedType = in_array($requestedType, PlatformFeedbackType::values(), true)
            ? $requestedType
            : PlatformFeedbackType::General->value;

        return view('feedback.create', [
            'supportLayout' => $this->layouts->resolve($request->user(), $connector),
            'connector' => $connector,
            'feedbackTypes' => PlatformFeedbackType::cases(),
            'selectedType' => $selectedType,
            'supportRoutes' => $this->routeContext->for($connector),
            'submissionToken' => old('submission_token', (string) Str::uuid()),
        ]);
    }

    public function store(StorePlatformFeedbackRequest $request, ?Connector $connector = null): RedirectResponse
    {
        $this->authorizeConnector($request, $connector);
        $feedback = $this->service->submit($request->user(), $request->validated(), $request->file('attachment'), (string) $request->userAgent());
        $routes = $this->routeContext->for($connector);

        return redirect()->route($routes['feedback']['show']['name'], array_merge($routes['feedback']['show']['parameters'], ['platformFeedback' => $feedback]))->with('success', "Thanks for contacting Help & Support. Your ticket reference is {$feedback->reference_number}.");
    }

    public function index(Request $request, ?Connector $connector = null): View
    {
        $this->authorizeConnector($request, $connector);
        $viewFilter = in_array($request->query('view'), ['active', 'resolved', 'closed'], true)
            ? (string) $request->query('view')
            : 'all';
        $feedback = $request->user()->platformFeedbackSubmissions()
            ->when($viewFilter === 'active', fn ($query) => $query->whereIn('status', [
                PlatformFeedbackStatus::New->value,
                PlatformFeedbackStatus::Reviewed->value,
            ]))
            ->when($viewFilter === 'resolved', fn ($query) => $query->where('status', PlatformFeedbackStatus::Resolved->value))
            ->when($viewFilter === 'closed', fn ($query) => $query->whereIn('status', [
                PlatformFeedbackStatus::Closed->value,
                PlatformFeedbackStatus::Withdrawn->value,
            ]))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('feedback.index', [
            'supportLayout' => $this->layouts->resolve($request->user(), $connector),
            'connector' => $connector,
            'feedback' => $feedback,
            'viewFilter' => $viewFilter,
            'supportRoutes' => $this->routeContext->for($connector),
        ]);
    }

    public function show(Request $request, PlatformFeedback $platformFeedback, ?Connector $connector = null): View
    {
        $this->authorizeConnector($request, $connector);
        $this->authorize('view', $platformFeedback);

        return view('feedback.show', [
            'supportLayout' => $this->layouts->resolve($request->user(), $connector),
            'connector' => $connector,
            'feedback' => $platformFeedback->load(['histories', 'messages.sender']),
            'supportRoutes' => $this->routeContext->for($connector),
        ]);
    }

    public function connectorShow(Request $request, Connector $connector, PlatformFeedback $platformFeedback): View
    {
        return $this->show($request, $platformFeedback, $connector);
    }

    public function withdraw(Request $request, PlatformFeedback $platformFeedback, ?Connector $connector = null): RedirectResponse
    {
        $this->authorizeConnector($request, $connector);
        abort_if($request->user()->hasRole('admin'), 403);
        $this->authorize('withdraw', $platformFeedback);

        abort_unless($platformFeedback->status === PlatformFeedbackStatus::New, 422, 'This ticket can no longer be withdrawn.');

        DB::transaction(function () use ($platformFeedback, $request): void {
            $fromStatus = $platformFeedback->status?->value ?? (string) $platformFeedback->status;
            $platformFeedback->update(['status' => PlatformFeedbackStatus::Withdrawn]);
            PlatformFeedbackHistory::create([
                'platform_feedback_id' => $platformFeedback->id,
                'actor_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => PlatformFeedbackStatus::Withdrawn->value,
            ]);
        });

        return back()->with('success', 'Ticket withdrawn.');
    }

    private function authorizeConnector(Request $request, ?Connector $connector): void
    {
        if ($connector !== null && ! $request->user()->hasRole('admin')) {
            $this->connectorAccess->abortUnlessWorkspace($request->user(), $connector);
        }
    }
}
