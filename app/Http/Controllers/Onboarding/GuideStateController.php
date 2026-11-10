<?php

declare(strict_types=1);

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\OnboardingGuideVersion;
use App\Models\UserOnboardingGuideState;
use App\Services\Onboarding\OnboardingGuideAudienceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GuideStateController extends Controller
{
    public function __construct(
        private readonly OnboardingGuideAudienceService $audience,
    ) {}

    public function dismiss(
        Request $request,
        OnboardingGuideVersion $version,
    ): JsonResponse|RedirectResponse {
        $state = $this->audience->dismiss($request->user(), $version);

        return $this->respond($request, $state, 'Your guide preference was saved.');
    }

    public function complete(
        Request $request,
        OnboardingGuideVersion $version,
    ): JsonResponse|RedirectResponse {
        $state = $this->audience->complete($request->user(), $version);

        return $this->respond($request, $state, 'Guide marked complete.');
    }

    private function respond(
        Request $request,
        UserOnboardingGuideState $state,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'dismissed_at' => $state->dismissed_at?->toIso8601String(),
                'completed_at' => $state->completed_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', $message);
    }
}
