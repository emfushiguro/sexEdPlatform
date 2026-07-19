<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreSeminarSpeakerRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarSpeaker;
use App\Models\User;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarSpeakerEligibilityService;
use App\Services\Seminars\SeminarSpeakerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SeminarSpeakerController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarSpeakerService $speakers,
        private readonly SeminarSpeakerEligibilityService $eligibility,
    ) {}

    public function search(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $results = $this->eligibility
            ->searchEligibleInstructors($seminar, trim((string) $request->query('search', '')))
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->instructorProfile?->profile_photo_path,
                'organization' => $user->instructorProfile?->professional_background,
            ])
            ->values();

        return response()->json($results);
    }

    public function store(StoreSeminarSpeakerRequest $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $validated = $request->validated();
        foreach (User::query()->whereIn('id', $validated['user_ids'])->get() as $speaker) {
            if (! $this->eligibility->isEligible($seminar, $speaker)) {
                throw ValidationException::withMessages(['user_id' => 'Select active approved instructors.']);
            }

            $this->speakers->addPlatformSpeaker($seminar, $speaker, $validated);
        }

        return back()->with('success', 'Speaker invitation sent.');
    }

    public function destroy(Request $request, Connector $connector, Seminar $seminar, SeminarSpeaker $speaker): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $this->speakers->cancelInvitation($seminar, $speaker);

        return back()->with('success', 'Speaker invitation cancelled.');
    }

    public function approve(Request $request, Connector $connector, Seminar $seminar, SeminarSpeaker $speaker): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless((int) $speaker->seminar_id === (int) $seminar->id && $speaker->status === 'applied', 404);

        $this->speakers->approveApplication($speaker, $request->user());

        return back()->with('success', 'Speaker application approved.');
    }

    public function reject(Request $request, Connector $connector, Seminar $seminar, SeminarSpeaker $speaker): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless((int) $speaker->seminar_id === (int) $seminar->id && $speaker->status === 'applied', 404);

        $validated = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);
        $this->speakers->rejectApplication($speaker, $request->user(), $validated['review_note'] ?? null);

        return back()->with('success', 'Speaker application rejected.');
    }
}
