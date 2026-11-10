<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\OnboardingGuide;
use App\Services\Onboarding\OnboardingGuideAudienceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class GuideController extends Controller
{
    public function __construct(
        private readonly OnboardingGuideAudienceService $audience,
    ) {}

    public function learner(Request $request): View
    {
        return $this->render($request, OnboardingGuide::ROLE_LEARNER, 'learner.guide');
    }

    public function instructor(Request $request): View
    {
        return $this->render($request, OnboardingGuide::ROLE_INSTRUCTOR, 'instructor.guide');
    }

    private function render(Request $request, string $role, string $view): View
    {
        $version = $this->audience->currentFor($request->user(), $role);

        return view($view, [
            'guideVersion' => $version,
            'guideStatus' => $version
                ? $this->audience->statusFor($request->user(), $version)
                : null,
        ]);
    }
}
