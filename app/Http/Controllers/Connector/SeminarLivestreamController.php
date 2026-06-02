<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarLivestreamController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly AgoraTokenService $tokens,
    ) {
    }

    public function show(Request $request, Connector $connector, Seminar $seminar): View
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        abort_unless($this->tokens->canPublish($request->user(), $seminar), 403);

        return view('connectors.seminars.livestream', [
            'connector' => $connector,
            'seminar' => $seminar,
            'canPublish' => true,
            'joinOpen' => $this->tokens->isInJoinWindow($seminar),
        ]);
    }

    public function token(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return response()->json($this->tokens->tokenFor($request->user(), $seminar, 'host'));
    }
}
