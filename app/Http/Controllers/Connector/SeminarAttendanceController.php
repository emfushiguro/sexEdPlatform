<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\SeminarAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarAttendanceController extends Controller
{
    public function __construct(private readonly SeminarAccessService $access)
    {
    }

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
}
