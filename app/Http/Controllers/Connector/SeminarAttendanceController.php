<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seminars\ManageSeminarCodeRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarCodeAttendanceService;
use App\Services\Seminars\SeminarExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarAttendanceController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarExportService $exports,
        private readonly SeminarCodeAttendanceService $codes,
    ) {}

    public function index(Request $request, Connector $connector, Seminar $seminar): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return view('connectors.seminars.attendance', [
            'connector' => $connector,
            'seminar' => $seminar,
            'attendances' => $seminar->attendances()->with('user')->latest('updated_at')->paginate(25),
        ]);
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
        $code = $this->codes->generate($seminar, isset($data['attendance_start_at']) ? Carbon::parse($data['attendance_start_at']) : null, isset($data['attendance_end_at']) ? Carbon::parse($data['attendance_end_at']) : null);

        return back()->with('generated_attendance_code', Crypt::encryptString($code))->with('generated_attendance_seminar_id', $seminar->id);
    }

    public function disable(Request $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $this->codes->disable($seminar);

        return back()->with('success', 'Attendance code disabled.');
    }
}
