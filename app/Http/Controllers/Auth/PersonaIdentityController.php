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
        $result = $this->inquiries->replace($request->user());

        return redirect()->away($this->inquiries->hostedFlowUrl($result['attempt'], $result['session_token']));
    }

    private function statusRoute($user): string
    {
        return $user->isParentRegistration()
            ? 'guardian.verification.status'
            : 'learner.identity.status';
    }
}
