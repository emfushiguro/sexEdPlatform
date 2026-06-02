<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarRegistrantController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarExportService $exports,
    ) {
    }

    public function export(Request $request, Connector $connector, Seminar $seminar): StreamedResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return $this->exports->registrantsCsv($seminar);
    }
}
