<?php

namespace App\Http\Controllers;

use App\Models\Seminar;
use App\Services\Seminars\SeminarAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeminarAttendanceController extends Controller
{
    public function __construct(private readonly SeminarAttendanceService $attendance)
    {
    }

    public function join(Request $request, Seminar $seminar): JsonResponse
    {
        return response()->json(['attendance' => $this->attendance->recordJoin($request->user(), $seminar)]);
    }

    public function heartbeat(Request $request, Seminar $seminar): JsonResponse
    {
        return response()->json(['attendance' => $this->attendance->heartbeat($request->user(), $seminar)]);
    }

    public function leave(Request $request, Seminar $seminar): JsonResponse
    {
        return response()->json(['attendance' => $this->attendance->recordLeave($request->user(), $seminar)]);
    }
}
