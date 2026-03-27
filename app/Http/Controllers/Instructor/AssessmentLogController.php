<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\InstructorAssessmentInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AssessmentLogController extends Controller
{
    public function __construct(private readonly InstructorAssessmentInsightsService $insightsService)
    {
    }

    public function index(Request $request)
    {
        $lowScoreThreshold = (int) $request->integer('low_score_threshold', 60);
        $lowActivityThreshold = (int) $request->integer('low_activity_threshold', 2);

        $insights = $this->insightsService->buildInsights(
            Auth::user(),
            $lowScoreThreshold,
            $lowActivityThreshold
        );

        return view('instructor.assessments.index', [
            'scoreDistributionByModule' => $insights['scoreDistributionByModule'],
            'attemptCountByLearner' => $insights['attemptCountByLearner'],
            'atRiskLearners' => $insights['atRiskLearners'],
            'assessmentThresholds' => [
                'low_score_threshold' => $lowScoreThreshold,
                'low_activity_threshold' => $lowActivityThreshold,
            ],
        ]);
    }
}
