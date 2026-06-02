<?php

namespace App\Http\Controllers\Connector;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreSeminarRequest;
use App\Http\Requests\Connector\UpdateSeminarRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarAttendanceService;
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
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);

        $seminars = $connector->seminars()
            ->latest('starts_at')
            ->paginate(15);

        return view('connectors.seminars.index', compact('connector', 'seminars'));
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
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return view('connectors.seminars.show', [
            'connector' => $connector,
            'seminar' => $seminar->load(['registrants.user', 'speakers.user', 'comments.user', 'questions.user']),
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

    public function publish(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        abort_unless($connector->status === 'verified', 403);
        abort_if($seminar->status === SeminarStatus::Cancelled->value, 422, 'Cancelled seminars cannot be published.');
        abort_if($seminar->status === SeminarStatus::Completed->value, 422, 'Completed seminars cannot be published.');

        $seminar->update(['status' => SeminarStatus::Published->value]);

        return back()->with('success', 'Seminar published.');
    }

    public function cancel(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        $seminar->update([
            'status' => SeminarStatus::Cancelled->value,
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return back()->with('success', 'Seminar cancelled.');
    }

    public function complete(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $seminar->update([
            'status' => SeminarStatus::Completed->value,
            'completed_at' => now(),
            'completed_by' => $request->user()->id,
        ]);
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
                'description',
                'purpose',
                'type',
                'category',
                'starts_at',
                'ends_at',
                'capacity',
                'target_participants',
                'location',
            ]),
            'learner_age_categories' => array_values((array) ($validated['learner_age_categories'] ?? [])),
            'location' => $type === SeminarType::Physical->value ? ($validated['location'] ?? null) : ($validated['location'] ?? null),
            'schedule' => $startsAt,
            'is_premium' => false,
        ];
    }
}
