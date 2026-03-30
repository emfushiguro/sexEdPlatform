<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentReviewPreviewRequest;
use App\Models\ModuleReviewRequest;
use App\Services\AdminModuleReviewWorkspaceService;
use Illuminate\Http\JsonResponse;

class ContentReviewPreviewController extends Controller
{
    public function __construct(
        private readonly AdminModuleReviewWorkspaceService $workspaceService,
    ) {
    }

    public function __invoke(ContentReviewPreviewRequest $request, ModuleReviewRequest $reviewRequest): JsonResponse
    {
        $node = $this->workspaceService->resolvePreviewNode(
            $reviewRequest,
            $request->string('node_type')->toString(),
            (int) $request->input('node_id'),
        );

        if (!$node) {
            return response()->json([
                'message' => 'Preview node not found.',
            ], 404);
        }

        return response()->json([
            'node' => $node,
        ]);
    }
}
