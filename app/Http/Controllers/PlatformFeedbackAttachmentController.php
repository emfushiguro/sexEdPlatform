<?php

namespace App\Http\Controllers;

use App\Models\Connector;
use App\Models\PlatformFeedback;
use App\Services\Connectors\ConnectorAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlatformFeedbackAttachmentController extends Controller
{
    public function __construct(private readonly ConnectorAccessService $connectorAccess) {}

    public function show(Request $request, PlatformFeedback $platformFeedback, ?Connector $connector = null)
    {
        if ($connector !== null && ! $request->user()->hasRole('admin')) {
            $this->connectorAccess->abortUnlessWorkspace($request->user(), $connector);
        }
        $this->authorize('view', $platformFeedback);
        abort_unless($platformFeedback->attachment_path && Storage::disk('local')->exists($platformFeedback->attachment_path), 404);

        return response()->file(Storage::disk('local')->path($platformFeedback->attachment_path), ['Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff']);
    }
}
