<?php

namespace App\Http\Controllers\InstructorApplication;

use App\Http\Controllers\Controller;
use App\Http\Requests\InstructorApplication\StartPersonaInquiryRequest;
use App\Services\Persona\PersonaInquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonaIdentityController extends Controller
{
    public function __construct(private readonly PersonaInquiryService $inquiries) {}

    public function start(StartPersonaInquiryRequest $request): RedirectResponse
    {
        $result = $this->inquiries->startForInstructor(
            $request->user(),
            (string) $request->string('government_id_type'),
        );

        return redirect()->away($this->inquiries->hostedFlowUrlForInstructor(
            $result['attempt'],
            $result['session_token'],
        ));
    }

    public function resume(Request $request): RedirectResponse
    {
        $result = $this->inquiries->resumeForInstructor($request->user());

        return redirect()->away($this->inquiries->hostedFlowUrlForInstructor(
            $result['attempt'],
            $result['session_token'],
        ));
    }

    public function handleReturn(Request $request): RedirectResponse
    {
        $this->inquiries->returnForInstructor($request->user(), (string) $request->query('inquiry-id'));

        return redirect()->route('instructor-application.dashboard')
            ->with('status', 'Persona returned to Conscious Connections. We are checking the result.');
    }

    public function refresh(Request $request): RedirectResponse
    {
        $this->inquiries->refreshForInstructor($request->user());

        return redirect()->route('instructor-application.dashboard')
            ->with('status', 'Persona status refresh requested.');
    }

    public function replace(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'government_id_type' => ['required', 'string', 'max:40', Rule::in(array_keys((array) config('persona.government_id_classes', [])))],
        ]);
        $result = $this->inquiries->replaceForInstructor($request->user(), $data['government_id_type']);

        return redirect()->away($this->inquiries->hostedFlowUrlForInstructor(
            $result['attempt'],
            $result['session_token'],
        ));
    }
}
