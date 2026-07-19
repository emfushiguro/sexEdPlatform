<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarLivestreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeminarLivestreamController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly AgoraTokenService $tokens,
        private readonly SeminarLivestreamService $livestreams,
    ) {}

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
            'livestreamStatus' => $this->livestreams->status($seminar),
        ]);
    }

    public function token(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);

        return response()->json($this->tokens->tokenFor($request->user(), $seminar, 'host'));
    }

    public function prepare(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->authorizeHost($request, $connector, $seminar);

        return response()->json($this->livestreams->status($this->livestreams->prepare($seminar)));
    }

    public function start(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->authorizeHost($request, $connector, $seminar);
        $result = $this->livestreams->start($seminar);

        return response()->json([
            ...$this->livestreams->status($result['seminar']),
            'started' => $result['started'],
        ]);
    }

    public function end(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->authorizeHost($request, $connector, $seminar);

        return response()->json($this->livestreams->status($this->livestreams->end($seminar, $request->user())));
    }

    public function status(Request $request, Connector $connector, Seminar $seminar): JsonResponse
    {
        $this->authorizeHost($request, $connector, $seminar);

        return response()->json($this->livestreams->status($seminar->fresh()));
    }

    private function authorizeHost(Request $request, Connector $connector, Seminar $seminar): void
    {
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        abort_unless($this->tokens->canHost($request->user(), $seminar), 403);
    }
}
