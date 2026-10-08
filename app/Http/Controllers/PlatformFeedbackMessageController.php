<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlatformFeedbackMessageRequest;
use App\Models\Connector;
use App\Models\PlatformFeedback;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Support\PlatformFeedbackConversationService;
use Illuminate\Http\RedirectResponse;

class PlatformFeedbackMessageController extends Controller
{
    public function __construct(private readonly ConnectorAccessService $connectorAccess) {}

    public function store(
        StorePlatformFeedbackMessageRequest $request,
        PlatformFeedback $platformFeedback,
        PlatformFeedbackConversationService $conversation,
    ): RedirectResponse {
        $this->authorize('view', $platformFeedback);
        $conversation->send($platformFeedback, $request->user(), $request->validated('body'));

        $route = $request->user()->hasRole('admin') ? 'admin.feedback.show' : 'feedback.show';

        return redirect()->route($route, $platformFeedback)->with('success', 'Message sent.');
    }

    public function connectorStore(
        StorePlatformFeedbackMessageRequest $request,
        Connector $connector,
        PlatformFeedback $platformFeedback,
        PlatformFeedbackConversationService $conversation,
    ): RedirectResponse {
        $this->connectorAccess->abortUnlessWorkspace($request->user(), $connector);
        $this->authorize('view', $platformFeedback);
        $conversation->send($platformFeedback, $request->user(), $request->validated('body'));

        return redirect()->route('connector.feedback.show', [$connector, $platformFeedback])->with('success', 'Message sent.');
    }
}
