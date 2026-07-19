<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Notifications\Seminars\SeminarRegistrationConfirmedNotification;
use App\Notifications\Seminars\SeminarRegistrationRejectedNotification;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarRegistrantController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarExportService $exports,
    ) {
    }

    public function index(Request $request, Connector $connector, Seminar $seminar): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $registrants = $seminar->registrants()
            ->with(['user.learnerProfile', 'user.instructorProfile'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->whereHas('user', fn ($user) => $user
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest('registered_at')
            ->paginate(15)
            ->withQueryString();

        return view('connectors.seminars.registrants', compact('connector', 'seminar', 'registrants', 'search', 'status'));
    }

    public function export(Request $request, Connector $connector, Seminar $seminar): StreamedResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return $this->exports->registrantsCsv($seminar);
    }

    public function approve(Request $request, Connector $connector, Seminar $seminar, SeminarRegistrant $registrant): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless($registrant->seminar_id === $seminar->id, 404);
        abort_unless($registrant->status === 'pending', 422, 'Only pending registrants can be approved.');
        abort_if($seminar->capacity !== null && $seminar->registrants()->active()->count() >= (int) $seminar->capacity, 422, 'Seminar capacity is full.');

        $registrant->update([
            'status' => 'registered',
            'rejection_reason' => null,
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
        ]);

        $registrant->user?->notify(new SeminarRegistrationConfirmedNotification($seminar->loadMissing('connector')));

        return back()->with('success', 'Registrant approved.');
    }

    public function reject(Request $request, Connector $connector, Seminar $seminar, SeminarRegistrant $registrant): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless($registrant->seminar_id === $seminar->id, 404);
        abort_unless($registrant->status === 'pending', 422, 'Only pending registrants can be rejected.');

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $registrant->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'decided_at' => now(),
            'decided_by' => $request->user()->id,
        ]);

        $registrant->user?->notify(new SeminarRegistrationRejectedNotification($seminar, $validated['rejection_reason']));

        return back()->with('success', 'Registrant rejected.');
    }

    public function destroy(Request $request, Connector $connector, Seminar $seminar, SeminarRegistrant $registrant): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless($registrant->seminar_id === $seminar->id, 404);
        abort_unless(in_array($registrant->status, ['registered', 'rejected'], true), 422, 'Only approved or rejected records can be deleted.');

        $registrant->delete();

        return back()->with('success', 'Registration record deleted.');
    }
}
