<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StartPersonaInquiryRequest;
use App\Services\Persona\PersonaInquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonaIdentityController extends Controller
{
    public function __construct(private readonly PersonaInquiryService $inquiries) {}

    public function start(StartPersonaInquiryRequest $request): RedirectResponse
    {
        $result = $this->inquiries->start(
            $request->user(),
            (string) $request->string('document_type'),
            $request->filled('government_id_type') ? (string) $request->string('government_id_type') : null,
        );

        return redirect()->away($this->inquiries->hostedFlowUrl($result['attempt'], $result['session_token']));
    }

    public function resume(Request $request): RedirectResponse
    {
        $result = $this->inquiries->resume($request->user());

        return redirect()->away($this->inquiries->hostedFlowUrl($result['attempt'], $result['session_token']));
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $this->inquiries->returnFromPersona($request->user(), (string) $request->query('inquiry-id'));

        return redirect()->route($this->statusRoute($request->user()))
            ->with('status', 'Persona returned to Conscious Connections. We are checking the result.');
    }

    public function refresh(Request $request): RedirectResponse
    {
        $this->inquiries->refresh($request->user());

        return redirect()->route($this->statusRoute($request->user()))
            ->with('status', 'Persona status refresh requested.');
    }

    public function replace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['sometimes', 'required', 'string', 'in:school_id,institution_id,government_id'],
            'government_id_type' => ['nullable', 'string', 'max:40', 'required_if:document_type,government_id'],
        ]);
        $result = $this->inquiries->replace(
            $request->user(), $data['document_type'] ?? null, $data['government_id_type'] ?? null,
        );

        return redirect()->away($this->inquiries->hostedFlowUrl($result['attempt'], $result['session_token']));
    }

    private function statusRoute($user): string
    {
        if ($user->isParentRegistration()) {
            return 'guardian.verification.status';
        }
        $attempt = $this->inquiries->currentAttempt($user);

        return $attempt?->flow_type === \App\Models\PersonaInquiry::FLOW_TEEN_SCHOOL
            ? 'learner.identity.selfie.create' : 'learner.identity.status';
    }
}
