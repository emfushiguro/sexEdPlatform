<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learner\StoreContentReportRequest;
use App\Services\ContentReportService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class ContentReportController extends Controller
{
    public function __construct(
        private readonly ContentReportService $contentReportService,
    ) {
    }

    public function store(StoreContentReportRequest $request): RedirectResponse
    {
        try {
            $this->contentReportService->submitOrUpdateActive(
                $request->user(),
                (string) $request->string('target_type'),
                (int) $request->integer('target_id'),
                (string) $request->string('reason_code'),
                $request->filled('details') ? (string) $request->string('details') : null,
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Report submitted. Thank you for helping keep the platform safe.');
    }
}
