<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreSeminarSpeakerRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarSpeaker;
use App\Models\User;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarSpeakerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeminarSpeakerController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarSpeakerService $speakers,
    ) {
    }

    public function store(StoreSeminarSpeakerRequest $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $validated = $request->validated();

        if ($validated['speaker_type'] === 'platform') {
            $this->speakers->addPlatformSpeaker(
                $seminar,
                User::query()->findOrFail($validated['user_id']),
                $validated
            );
        } else {
            $this->speakers->addExternalSpeaker($seminar, $validated);
        }

        return back()->with('success', 'Speaker assigned.');
    }

    public function destroy(Request $request, Connector $connector, Seminar $seminar, SeminarSpeaker $speaker): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $this->speakers->removeSpeaker($seminar, $speaker);

        return back()->with('success', 'Speaker removed.');
    }
}
