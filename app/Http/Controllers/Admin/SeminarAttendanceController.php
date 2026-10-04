<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seminars\ManageSeminarCodeRequest;
use App\Http\Requests\Seminars\SetSeminarAttendanceRequest;
use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Services\Seminars\SeminarCodeAttendanceService;
use App\Services\Seminars\SeminarManualAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class SeminarAttendanceController extends Controller
{
    public function __construct(
        private readonly SeminarCodeAttendanceService $codes,
        private readonly SeminarManualAttendanceService $manualAttendance,
    ) {}

    public function generate(ManageSeminarCodeRequest $request, Seminar $seminar): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validated();
        $code = $this->codes->generate($seminar, isset($data['attendance_start_at']) ? Carbon::parse($data['attendance_start_at']) : null, isset($data['attendance_end_at']) ? Carbon::parse($data['attendance_end_at']) : null);

        return back()->with('generated_attendance_code', Crypt::encryptString($code))->with('generated_attendance_seminar_id', $seminar->id);
    }

    public function disable(Request $request, Seminar $seminar): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $this->codes->disable($seminar);

        return back()->with('success', 'Attendance code disabled.');
    }

    public function manual(SetSeminarAttendanceRequest $request, Seminar $seminar, SeminarRegistrant $registrant): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validated();
        $this->manualAttendance->set($seminar, $registrant, $request->user(), (bool) $data['attended'], $data['reason'] ?? null);

        return back()->with('success', 'Attendance updated.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->hasRole('admin'), 403);
    }
}
