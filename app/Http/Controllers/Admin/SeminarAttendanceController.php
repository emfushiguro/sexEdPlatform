<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Connector;
use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Services\Seminars\SeminarExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarAttendanceController extends Controller
{
    public function __construct(private readonly SeminarExportService $exports) {}

    public function monitor(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'event' => ['nullable', 'string', 'max:255'],
            'connector_id' => ['nullable', 'integer', 'exists:connectors,id'],
            'event_date' => ['nullable', 'date_format:Y-m-d'],
            'attendance_status' => ['nullable', Rule::in(['not_submitted', 'submitted', 'attended', 'not_present', 'joined', 'left', 'registered'])],
        ]);

        $registrants = SeminarRegistrant::query()
            ->with(['user', 'seminar.connector'])
            ->leftJoin('seminar_attendances as attendance', function ($join): void {
                $join->on('attendance.seminar_id', '=', 'seminar_registrants.seminar_id')
                    ->on('attendance.user_id', '=', 'seminar_registrants.user_id');
            })
            ->select('seminar_registrants.*', 'attendance.status as attendance_status', 'attendance.attendance_method', 'attendance.attended_at as submission_at')
            ->whereHas('seminar', function ($query) use ($filters): void {
                $query->when(filled($filters['event'] ?? null), fn ($query) => $query->where('title', 'like', '%'.$filters['event'].'%'))
                    ->when(filled($filters['connector_id'] ?? null), fn ($query) => $query->where('connector_id', $filters['connector_id']));
                if (filled($filters['event_date'] ?? null)) {
                    $day = Carbon::parse($filters['event_date'], config('app.display_timezone'));
                    $query->whereRaw('COALESCE(starts_at, schedule) >= ? AND COALESCE(starts_at, schedule) < ?', [
                        $day->copy()->utc(), $day->copy()->addDay()->utc(),
                    ]);
                }
            });
        $status = $filters['attendance_status'] ?? null;
        if ($status === 'not_submitted') {
            $registrants->whereNull('attendance.id');
        } elseif ($status === 'submitted') {
            $registrants->where('attendance.attendance_method', 'attendance_code');
        } elseif (filled($status)) {
            $registrants->where('attendance.status', $status);
        }

        return view('admin.seminars.attendance-monitor', [
            'registrants' => $registrants->orderByDesc('seminar_registrants.id')->paginate(25)->withQueryString(),
            'connectors' => Connector::query()->whereHas('seminars')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function index(Request $request, Seminar $seminar): View
    {
        $this->authorizeAdmin($request);
        $seminar->load('connector');
        $registrants = $seminar->registrants()
            ->with(['user.learnerProfile', 'user.instructorProfile'])
            ->orderBy('registered_at')
            ->orderBy('id')
            ->paginate(25);
        $attendances = $seminar->attendances()
            ->whereIn('user_id', $registrants->getCollection()->pluck('user_id'))
            ->get()
            ->keyBy('user_id');
        $audits = ActivityLog::query()->with('user')
            ->whereIn('activity_type', [
                'seminar_attendance_code_generated', 'seminar_attendance_code_regenerated',
                'seminar_attendance_code_closed', 'seminar_attendance_submitted', 'seminar_attendance_corrected',
            ])
            ->where('metadata->seminar_id', $seminar->id)
            ->latest('id')->paginate(20, ['*'], 'audit_page')->withQueryString();

        return view('admin.seminars.attendance', compact('seminar', 'registrants', 'attendances', 'audits'));
    }

    public function export(Request $request, Seminar $seminar): StreamedResponse
    {
        $this->authorizeAdmin($request);

        return $this->exports->attendanceCsv($seminar);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->hasRole('admin'), 403);
    }
}
