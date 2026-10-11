<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RequestConnectorVerificationEvidenceRequest;
use App\Http\Requests\Admin\ResolveConnectorVerificationRequirementRequest;
use App\Http\Requests\Admin\ReviewConnectorVerificationDocumentRequest;
use App\Models\Connector;
use App\Models\ConnectorVerificationDocument;
use App\Models\ConnectorVerificationRequirement;
use App\Services\Connectors\ConnectorVerificationService;
use Illuminate\Http\RedirectResponse;

final class ConnectorVerificationController extends Controller
{
    public function __construct(
        private readonly ConnectorVerificationService $verification,
    ) {}

    public function reviewDocument(
        ReviewConnectorVerificationDocumentRequest $request,
        Connector $connector,
        ConnectorVerificationDocument $document,
    ): RedirectResponse {
        $data = $request->validated();

        $this->verification->reviewDocument(
            $connector,
            $document,
            $request->user(),
            (string) $data['decision'],
            (int) $data['submission_round'],
            $data['applicant_reason'] ?? null,
            $data['internal_note'] ?? null,
        );

        return redirect()->route('admin.connectors.show', $connector)
            ->with('success', 'Verification document review recorded.');
    }

    public function resolveRequirement(
        ResolveConnectorVerificationRequirementRequest $request,
        Connector $connector,
        ConnectorVerificationRequirement $requirement,
    ): RedirectResponse {
        $data = $request->validated();

        $this->verification->markRequirementNotApplicable(
            $connector,
            $requirement,
            $request->user(),
            (int) $data['submission_round'],
            (string) $data['reason'],
            $data['internal_note'] ?? null,
        );

        return redirect()->route('admin.connectors.show', $connector)
            ->with('success', 'Verification requirement marked not applicable.');
    }

    public function requestEvidence(
        RequestConnectorVerificationEvidenceRequest $request,
        Connector $connector,
    ): RedirectResponse {
        $data = $request->validated();

        $this->verification->requestSupplementalRequirement(
            $connector,
            $request->user(),
            $data,
            (int) $data['submission_round'],
        );

        return redirect()->route('admin.connectors.show', $connector)
            ->with('success', 'Supplemental verification evidence requested.');
    }
}
