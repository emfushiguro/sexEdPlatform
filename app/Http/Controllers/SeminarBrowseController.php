<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seminars\RegisterSeminarRequest;
use App\Models\Seminar;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarDiscoveryService;
use App\Services\Seminars\SeminarRegistrationService;
use App\Services\Seminars\SeminarSpeakerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarBrowseController extends Controller
{
    public function __construct(
        private readonly SeminarRegistrationService $registrations,
        private readonly AgoraTokenService $tokens,
        private readonly SeminarDiscoveryService $discovery,
    ) {}

    public function index(Request $request): View
    {
        return view('seminars.index', [
            'seminars' => $this->discovery->visibleSeminarsFor($request->user(), $request->only(['search', 'type', 'category', 'upcoming'])),
        ]);
    }

    public function show(Request $request, Seminar $seminar): View
    {
        abort_unless($this->discovery->canView($request->user(), $seminar), 403);

        return view('seminars.show', [
            'seminar' => $seminar->load(['connector', 'speakers.user']),
            'registration' => $this->registrations->activeRegistration($request->user(), $seminar),
            'speakerApplication' => $seminar->speakers()->where('user_id', $request->user()->id)->whereIn('status', ['applied', 'accepted', 'rejected'])->first(),
            'canRegister' => $this->registrations->canRegister($request->user(), $seminar),
            'registrationError' => $this->registrations->registrationError($request->user(), $seminar),
            'canJoinLivestream' => $this->tokens->canJoinLivestream($request->user(), $seminar),
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

    public function applyAsSpeaker(Request $request, Seminar $seminar): RedirectResponse
    {
        abort_unless($request->user()->isInstructor(), 403);
        abort_unless($this->discovery->canView($request->user(), $seminar), 403);

        $validated = $request->validate([
            'motivation' => ['required', 'string', 'max:2000'],
            'expertise' => ['required', 'string', 'max:1500'],
            'experience' => ['required', 'string', 'max:1500'],
            'supporting_info' => ['nullable', 'string', 'max:2000'],
        ]);

        app(SeminarSpeakerService::class)->apply($seminar, $request->user(), $validated);

        return back()->with('success', 'Speaker application submitted.');
    }

    public function join(Request $request, Seminar $seminar): View
    {
        $role = $this->tokens->roleFor($request->user(), $seminar);
        $canPublish = in_array($role, ['host', 'speaker'], true);

        abort_unless($role !== null && $this->tokens->canJoinLivestream($request->user(), $seminar), 403);

        return view('seminars.join', [
            'seminar' => $seminar->load('connector'),
            'canPublish' => $canPublish,
            'livestreamRole' => $role,
        ]);
    }

    public function agoraToken(Request $request, Seminar $seminar): JsonResponse
    {
        $role = $this->tokens->roleFor($request->user(), $seminar);
        abort_unless($role !== null, 403);

        return response()->json($this->tokens->tokenFor($request->user(), $seminar, $role));
    }
}
