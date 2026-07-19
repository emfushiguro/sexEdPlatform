<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\SeminarSpeaker;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarSpeakerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarSpeakerInvitationController extends Controller
{
    public function __construct(
        private readonly SeminarSpeakerService $speakers,
        private readonly AgoraTokenService $tokens,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        return view('instructor.speaker-invitations.index', [
            'invitations' => SeminarSpeaker::query()
                ->with(['seminar.connector'])
                ->where('user_id', $request->user()->id)
                ->when($search !== '', fn ($query) => $query->whereHas('seminar', fn ($seminar) => $seminar
                    ->where('title', 'like', "%{$search}%")
                    ->orWhereHas('connector', fn ($connector) => $connector->where('name', 'like', "%{$search}%"))))
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Request $request, SeminarSpeaker $speaker): View
    {
        abort_unless((int) $speaker->user_id === (int) $request->user()->id, 403);

        return view('instructor.speaker-invitations.show', [
            'speaker' => $speaker->load(['seminar.connector', 'seminar.speakers.user', 'seminar.registrants.user']),
            'joinOpen' => $speaker->seminar !== null && $this->tokens->canJoinLivestream($request->user(), $speaker->seminar),
        ]);
    }

    public function accept(Request $request, SeminarSpeaker $speaker): RedirectResponse
    {
        abort_unless((int) $speaker->user_id === (int) $request->user()->id, 403);

        $this->speakers->acceptInvitation($speaker);

        return redirect()->route('instructor.speaker-invitations.show', $speaker)->with('success', 'Speaker invitation accepted.');
    }

    public function decline(Request $request, SeminarSpeaker $speaker): RedirectResponse
    {
        abort_unless((int) $speaker->user_id === (int) $request->user()->id, 403);

        $validated = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:255'],
            'custom_decline_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->speakers->declineInvitation(
            $speaker,
            ($validated['decline_reason'] ?? null) === 'Other'
                ? ($validated['custom_decline_reason'] ?? null)
                : ($validated['decline_reason'] ?? null)
        );

        return redirect()->route('instructor.speaker-invitations.index')->with('success', 'Speaker invitation declined.');
    }
}
