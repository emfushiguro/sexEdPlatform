<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seminars\RegisterSeminarRequest;
use App\Models\Seminar;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarBrowseController extends Controller
{
    public function __construct(
        private readonly SeminarRegistrationService $registrations,
        private readonly AgoraTokenService $tokens,
    ) {
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

    public function join(Request $request, Seminar $seminar): View
    {
        $canPublish = $this->tokens->canPublish($request->user(), $seminar);
        $canJoinAudience = $this->tokens->canJoinAsAudience($request->user(), $seminar);

        abort_unless($canPublish || $canJoinAudience, 403);
        abort_unless($this->tokens->isInJoinWindow($seminar), 403);

        return view('seminars.join', [
            'seminar' => $seminar->load('connector'),
            'canPublish' => $canPublish,
        ]);
    }

    public function agoraToken(Request $request, Seminar $seminar): JsonResponse
    {
        $role = $this->tokens->canPublish($request->user(), $seminar) ? 'speaker' : 'audience';

        return response()->json($this->tokens->tokenFor($request->user(), $seminar, $role));
    }
}
