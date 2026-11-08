<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seminars\ManageSeminarCodeRequest;
use App\Http\Requests\Seminars\SetSeminarAttendanceRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarCodeAttendanceService;
use App\Services\Seminars\SeminarExportService;
use App\Services\Seminars\SeminarManualAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarAttendanceController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarExportService $exports,
        private readonly SeminarCodeAttendanceService $codes,
        private readonly SeminarManualAttendanceService $manualAttendance,
    ) {}

    public function index(Request $request, Connector $connector, Seminar $seminar): Response
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        $this->access->abortUnlessCanManageAttendance($request->user(), $seminar);

        $registrants = $seminar->registrants()
            ->with(['user.learnerProfile', 'user.instructorProfile'])
            ->orderBy('registered_at')
            ->orderBy('id')
            ->paginate(25);
        $attendances = $seminar->attendances()
            ->whereIn('user_id', $registrants->getCollection()->pluck('user_id'))
            ->get()
            ->keyBy('user_id');

        return response()->view('connectors.seminars.attendance', [
            'connector' => $connector,
            'seminar' => $seminar,
            'registrants' => $registrants,
            'attendances' => $attendances,
            'submissionCount' => $seminar->attendances()->where('attendance_method', 'attendance_code')->count(),
            'postedHostCode' => $this->codes->postedCodeForHost($request->user(), $seminar),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function export(Request $request, Connector $connector, Seminar $seminar): StreamedResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return $this->exports->attendanceCsv($seminar);
    }

    public function generate(ManageSeminarCodeRequest $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $data = $request->validated();
        $code = $this->codes->generate($seminar, $request->user(), isset($data['attendance_start_at']) ? Carbon::parse($data['attendance_start_at']) : null, isset($data['attendance_end_at']) ? Carbon::parse($data['attendance_end_at']) : null);

        return back()->with('generated_attendance_code', Crypt::encryptString($code))
            ->with('generated_attendance_seminar_id', $seminar->id)->with('generated_attendance_actor_id', $request->user()->id);
    }

    public function disable(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $this->codes->disable($seminar, $request->user());

        return back()->with('success', 'Attendance code disabled.');
    }

    public function post(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $this->access->abortUnlessCanManageAttendance($request->user(), $seminar);
        $data = $request->validate(['attendance_share_code' => ['required', 'string', 'regex:/^[0-9]{8}$/D']]);
        $this->codes->post($seminar, $request->user(), $data['attendance_share_code']);

        return back()->with('success', 'Code posted. Confirmed eligible registrants can view it during the attendance window.');
    }

    public function unpost(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $this->codes->unpost($seminar, $request->user());

        return back()->with('success', 'Code unposted. Participants who already received it can still submit it.');
    }

    public function manual(SetSeminarAttendanceRequest $request, Connector $connector, Seminar $seminar, SeminarRegistrant $registrant): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $data = $request->validated();
        $this->manualAttendance->set($seminar, $registrant, $request->user(), (bool) $data['attended'], $data['reason'] ?? null);

        return back()->with('success', 'Attendance updated.');
    }
}
