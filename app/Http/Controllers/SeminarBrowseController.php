<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seminars\RegisterSeminarRequest;
use App\Models\Seminar;
use App\Services\Seminars\SeminarRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarBrowseController extends Controller
{
    public function __construct(private readonly SeminarRegistrationService $registrations)
    {
    }

    public function index(Request $request): View
    {
        return view('seminars.index', [
            'seminars' => $this->registrations->eligiblePublishedSeminarsFor($request->user()),
        ]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        abort_unless($this->registrations->matchesParticipantEligibility($request->user(), $seminar), 403);

        return view('seminars.show', [
            'seminar' => $seminar->load('connector'),
            'registration' => $this->registrations->activeRegistration($request->user(), $seminar),
            'canRegister' => $this->registrations->canRegister($request->user(), $seminar),
            'registrationError' => $this->registrations->registrationError($request->user(), $seminar),
        ]);
    }

    public function register(RegisterSeminarRequest $request, Seminar $seminar): RedirectResponse
    {
        $this->registrations->register($request->user(), $seminar);

        return redirect()
            ->route('seminars.show', $seminar)
            ->with('success', 'You are registered for this seminar.');
    }

    public function cancelRegistration(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->registrations->cancel($request->user(), $seminar);

        return redirect()
            ->route('seminars.show', $seminar)
            ->with('success', 'Your seminar registration was cancelled.');
    }
}
