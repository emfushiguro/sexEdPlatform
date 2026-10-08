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
        $status = $request->string('status')->toString();
        $verificationStatus = $request->string('verification_status')->toString();
        $organizationType = $request->string('organization_type')->toString();
        $allowedStatuses = ['pending', 'verified', 'rejected', 'suspended', 'withdrawn'];
        $allowedVerificationStatuses = [
            Connector::VERIFICATION_UNDER_REVIEW,
            Connector::VERIFICATION_RESUBMISSION_REQUIRED,
            Connector::VERIFICATION_VERIFIED,
            Connector::VERIFICATION_LEGACY_VERIFIED,
            Connector::VERIFICATION_REVERIFICATION_REQUIRED,
            Connector::VERIFICATION_REJECTED,
        ];
        $organizationTypes = config('connector_verification.organization_types', []);

        $connectors = Connector::query()
            ->with(['primaryRepresentative', 'creator', 'reviewer'])
            ->withCount(['memberships', 'invitations'])
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->when(in_array($verificationStatus, $allowedVerificationStatuses, true), fn ($query) => $query->where('verification_status', $verificationStatus))
            ->when(array_key_exists($organizationType, $organizationTypes), fn ($query) => $query->where('organization_type', $organizationType))
            ->latest()
            ->get();

        $counts = [
            'total' => Connector::count(),
            'pending' => Connector::where('status', 'pending')->count(),
            'verified' => Connector::where('status', 'verified')->count(),
            'rejected' => Connector::where('status', 'rejected')->count(),
            'suspended' => Connector::where('status', 'suspended')->count(),
        ];
        $verificationCounts = collect($allowedVerificationStatuses)
            ->mapWithKeys(fn (string $value): array => [$value => Connector::where('verification_status', $value)->count()])
            ->all();

        return view('admin.connectors.index', [
            'connectors' => $connectors,
            'counts' => $counts,
            'verificationCounts' => $verificationCounts,
            'filters' => [
                'status' => in_array($status, $allowedStatuses, true) ? $status : '',
                'verification_status' => in_array($verificationStatus, $allowedVerificationStatuses, true) ? $verificationStatus : '',
                'organization_type' => array_key_exists($organizationType, $organizationTypes) ? $organizationType : '',
            ],
            'organizationTypes' => $organizationTypes,
        ]);
    }

    public function show(Connector $connector): View
    {
        $connector->load([
            'primaryRepresentative.identityVerifications' => fn ($query) => $query
                ->whereNull('superseded_at')
                ->latest('approved_at'),
            'creator',
            'reviewer',
            'memberships.user',
            'memberships.role',
            'reviews' => fn ($query) => $query
                ->with('reviewer')
                ->orderBy('reviewed_at'),
            'verificationRequirements' => fn ($query) => $query
                ->with([
                    'learnerIdentityVerification',
                    'documents' => fn ($documents) => $documents
                        ->with(['uploader', 'reviewer'])
                        ->orderByDesc('submission_round')
                        ->orderBy('created_at'),
                ])
                ->orderBy('requirement_key')
                ->orderByDesc('created_at'),
            'verificationAudits' => fn ($query) => $query
                ->with(['actor', 'requirement'])
                ->orderBy('created_at'),
        ]);

        return view('admin.connectors.show', [
            'connector' => $connector,
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
