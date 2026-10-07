<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveConnectorRequest;
use App\Http\Requests\Admin\RejectConnectorRequest;
use App\Http\Requests\Admin\RequestConnectorResubmissionRequest;
use App\Http\Requests\Admin\SuspendConnectorRequest;
use App\Models\Connector;
use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConnectorController extends Controller
{
    public function index(Request $request): View
    {
        $connectors = Connector::query()
            ->with(['primaryRepresentative', 'creator', 'reviewer'])
            ->withCount(['memberships', 'invitations'])
            ->latest()
            ->get();

        return view('admin.connectors.index', [
            'connectors' => $connectors,
            'counts' => [
                'total' => Connector::count(),
                'pending' => Connector::where('status', 'pending')->count(),
                'verified' => Connector::where('status', 'verified')->count(),
                'rejected' => Connector::where('status', 'rejected')->count(),
                'suspended' => Connector::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function show(Connector $connector): View
    {
        return view('admin.connectors.show', [
            'connector' => $connector->load([
                'primaryRepresentative',
                'creator',
                'reviewer',
                'memberships.user',
                'memberships.role',
                'reviews.reviewer',
            ]),
        ]);
    }

    public function approve(
        ApproveConnectorRequest $request,
        Connector $connector,
        ConnectorVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validated();

        $verification->approve(
            $connector,
            $request->user(),
            (int) $data['submission_round'],
            $data['checklist'],
            $data['internal_note'] ?? $data['reason'] ?? null,
        );

        return back()->with('success', 'Connector approved.');
    }

    public function reject(
        RejectConnectorRequest $request,
        Connector $connector,
        ConnectorVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validated();

        $verification->permanentlyReject(
            $connector,
            $request->user(),
            (int) $data['submission_round'],
            $data['reason_code'],
            $data['applicant_reason'],
            $data['internal_note'] ?? null,
        );

        return back()->with('success', 'Connector rejected.');
    }

    public function requestResubmission(
        RequestConnectorResubmissionRequest $request,
        Connector $connector,
        ConnectorVerificationService $verification,
    ): RedirectResponse {
        $data = $request->validated();

        $verification->requestResubmission(
            $connector,
            $request->user(),
            (int) $data['submission_round'],
            $data['applicant_reason'],
            $data['internal_note'] ?? null,
        );

        return redirect()->route('admin.connectors.show', $connector)
            ->with('success', 'Connector resubmission requested.');
    }

    public function suspend(
        SuspendConnectorRequest $request,
        Connector $connector,
        ConnectorVerificationService $verification,
    ): RedirectResponse {
        $verification->suspend($connector, $request->user(), $request->string('reason')->toString());

        return back()->with('success', 'Connector suspended.');
    }
}
