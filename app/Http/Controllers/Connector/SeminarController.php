<?php

namespace App\Http\Controllers\Connector;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreSeminarRequest;
use App\Http\Requests\Connector\UpdateSeminarRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Notifications\Seminars\SeminarCancelledNotification;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarAttendanceService;
use App\Services\Seminars\SeminarCategoryService;
use App\Services\Seminars\SeminarLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeminarController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarAttendanceService $attendance,
        private readonly SeminarCategoryService $categories,
        private readonly SeminarLifecycleService $lifecycle,
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $canManageSeminars = $this->access->canManageConnectorSeminars($request->user(), $connector);

        $seminars = $connector->seminars()
            ->when(! $canManageSeminars, fn ($query) => $query->published())
            ->latest('starts_at')
            ->paginate(15);

        return view('connectors.seminars.index', compact('connector', 'seminars', 'canManageSeminars'));
    }

    public function create(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);

        return view('connectors.seminars.create', [
            'connector' => $connector,
            'seminar' => new Seminar([
                'type' => SeminarType::Webinar->value,
                'category' => 'education',
                'target_participants' => 'learners_and_instructors',
                'learner_age_categories' => array_keys(config('seminars.learner_age_categories')),
            ]),
        ]);
    }

    public function store(StoreSeminarRequest $request, Connector $connector): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);

        $seminar = $connector->seminars()->create([
            ...$this->payload($request->validated()),
            'status' => SeminarStatus::Draft->value,
        ]);

        if ($seminar->type === SeminarType::Webinar->value && blank($seminar->livestream_channel)) {
            $seminar->forceFill(['livestream_channel' => 'seminar-'.$seminar->id.'-'.Str::lower(Str::random(8))])->save();
        }

        return redirect()
            ->route('connector.seminars.show', [$connector, $seminar])
            ->with('success', 'Seminar draft created.');
    }

    public function show(Request $request, Connector $connector, Seminar $seminar): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        if (! $this->access->canManageConnectorSeminars($request->user(), $connector)) {
            abort_unless($seminar->status === SeminarStatus::Published->value, 403);

            return view('seminars.show', [
                'seminar' => $seminar->load(['connector', 'speakers.user']),
                'registration' => app(\App\Services\Seminars\SeminarRegistrationService::class)->activeRegistration($request->user(), $seminar),
                'canRegister' => app(\App\Services\Seminars\SeminarRegistrationService::class)->canRegister($request->user(), $seminar),
                'registrationError' => app(\App\Services\Seminars\SeminarRegistrationService::class)->registrationError($request->user(), $seminar),
                'canJoinLivestream' => false,
            ]);
        }

        return view('connectors.seminars.show', [
            'connector' => $connector,
            'seminar' => $seminar->load(['registrants.user.learnerProfile', 'registrants.user.instructorProfile', 'speakers.user.instructorProfile', 'speakers.user.learnerProfile', 'comments.user', 'questions.user']),
            'activeRegistrantCount' => $this->access->activeRegistrantCount($seminar),
        ]);
    }

    public function edit(Request $request, Connector $connector, Seminar $seminar): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return view('connectors.seminars.edit', compact('connector', 'seminar'));
    }

    public function update(UpdateSeminarRequest $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $data = $this->payload($request->validated());
        $activeRegistrantCount = $this->access->activeRegistrantCount($seminar);

        if ($activeRegistrantCount > 0) {
            abort_if(($data['type'] ?? $seminar->type) !== $seminar->type, 422, 'Seminar type cannot change after registration exists.');
            abort_if(($data['capacity'] ?? null) !== null && (int) $data['capacity'] < $activeRegistrantCount, 422, 'Capacity cannot be lower than active registrations.');
        }

        $seminar->update($data);

        return redirect()
            ->route('connector.seminars.show', [$connector, $seminar])
            ->with('success', 'Seminar updated.');
    }

    public function submitForReview(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $this->lifecycle->submitForReview($seminar, $request->user());

        return back()->with('success', 'Seminar submitted for review.');
    }

    public function publish(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        abort_unless($connector->status === 'verified', 403);

        $this->lifecycle->publishApproved($seminar, $request->user());

        return back()->with('success', 'Seminar published.');
    }

    public function archive(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $this->lifecycle->archive($seminar, $request->user());

        return back()->with('success', 'Seminar archived.');
    }

    public function destroy(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        abort_if($seminar->published_at !== null || in_array($seminar->status, ['published', 'completed', 'cancelled', 'archived'], true), 422, 'Published seminars cannot be deleted.');

        $seminar->delete();

        return redirect()
            ->route('connector.seminars.index', $connector)
            ->with('success', 'Seminar deleted.');
    }

    public function cancel(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $seminar = $this->lifecycle->cancel($seminar, $request->user(), $validated['cancellation_reason']);

        $this->notifyActiveRegistrantsAboutCancellation($seminar->fresh('connector'), $validated['cancellation_reason']);

        return back()->with('success', 'Seminar cancelled.');
    }

    public function complete(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $seminar = $this->lifecycle->complete($seminar, $request->user());
        $this->attendance->finalize($seminar);

        return back()->with('success', 'Seminar marked completed.');
    }

    private function payload(array $validated): array
    {
        $startsAt = $validated['starts_at'];
        $type = $validated['type'];

        return [
            ...Arr::only($validated, [
                'title',
                'purpose',
                'type',
                'category',
                'custom_category',
                'starts_at',
                'ends_at',
                'capacity',
                'registration_approval_mode',
                'target_participants',
                'location',
            ]),
            'custom_category' => $this->categories->normalizeCustomCategory($validated['category'] ?? null, $validated['custom_category'] ?? null),
            'learner_age_categories' => array_values((array) ($validated['learner_age_categories'] ?? [])),
            'location' => $type === SeminarType::Physical->value ? ($validated['location'] ?? null) : ($validated['location'] ?? null),
            'schedule' => $startsAt,
            'is_premium' => false,
        ];
    }

    private function notifyActiveRegistrantsAboutCancellation(Seminar $seminar, string $reason): void
    {
        $seminar->registrants()
            ->active()
            ->with('user')
            ->chunkById(100, function ($registrants) use ($seminar, $reason): void {
                foreach ($registrants as $registrant) {
                    $registrant->user?->notify(new SeminarCancelledNotification($seminar, $reason));
                }
            });
    }
}
