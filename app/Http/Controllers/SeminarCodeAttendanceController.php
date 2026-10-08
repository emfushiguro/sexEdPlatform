<?php

namespace App\Http\Controllers;

use App\Http\Requests\Seminars\SubmitSeminarCodeRequest;
use App\Models\Seminar;
use App\Services\Seminars\SeminarCodeAttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class SeminarCodeAttendanceController extends Controller
{
    public function __construct(private readonly SeminarCodeAttendanceService $codes) {}

    public function submit(SubmitSeminarCodeRequest $request, Seminar $seminar): RedirectResponse
    {
        try {
            $this->codes->submit($request->user(), $seminar, $request->validated('code'), $request->ip());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('success', 'Attendance submitted. A code confirms submission, not full participation.');
    }
}
