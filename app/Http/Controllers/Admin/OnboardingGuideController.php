<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublishOnboardingGuideRequest;
use App\Http\Requests\Admin\StoreOnboardingGuideRequest;
use App\Http\Requests\Admin\UpdateOnboardingGuideRequest;
use App\Models\OnboardingGuide;
use App\Services\Onboarding\OnboardingGuideAudienceService;
use App\Services\Onboarding\OnboardingGuideLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class OnboardingGuideController extends Controller
{
    public function index(OnboardingGuideAudienceService $audience): View
    {
        $guides = OnboardingGuide::query()
            ->with([
                'currentVersion',
                'versions' => fn ($query) => $query
                    ->whereNull('published_at')
                    ->latest('version_number'),
            ])
            ->withCount('versions')
            ->latest('id')
            ->get()
            ->each(function (OnboardingGuide $guide) use ($audience): void {
                $guide->setAttribute('draft_version', $guide->versions->first());
                $guide->setAttribute(
                    'analytics',
                    $guide->status === OnboardingGuide::STATUS_PUBLISHED
                        ? $audience->analyticsFor($guide)
                        : null,
                );
            });

        return view('admin.onboarding-guides.index', compact('guides'));
    }

    public function create(): View
    {
        return view('admin.onboarding-guides.create', [
            'guide' => new OnboardingGuide([
                'guide_key' => OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION,
                'target_role' => OnboardingGuide::ROLE_LEARNER,
            ]),
            'version' => null,
        ]);
    }

    public function store(StoreOnboardingGuideRequest $request, OnboardingGuideLifecycleService $lifecycle): View|RedirectResponse|JsonResponse
    {
        $guide = $lifecycle->createDraft(
            $request->user(),
            $request->validated(),
            $request->file('video'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('admin.onboarding-guides.edit', $guide),
            ]);
        }

        return redirect()
            ->route('admin.onboarding-guides.edit', $guide)
            ->with('success', 'Onboarding guide draft created.');
    }

    public function edit(OnboardingGuide $guide, OnboardingGuideLifecycleService $lifecycle): View
    {
        $guide->load('currentVersion');
        $draft = $lifecycle->draftVersion($guide);

        return view('admin.onboarding-guides.edit', [
            'guide' => $guide,
            'version' => $draft ?? $guide->currentVersion,
            'draft' => $draft,
            'otherPublished' => $this->hasOtherPublishedGuide($guide),
        ]);
    }

    public function update(
        UpdateOnboardingGuideRequest $request,
        OnboardingGuide $guide,
        OnboardingGuideLifecycleService $lifecycle,
    ): View|RedirectResponse|JsonResponse {
        $guide = $lifecycle->updateDraft(
            $request->user(),
            $guide,
            $request->validated(),
            $request->file('video'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('admin.onboarding-guides.edit', $guide),
            ]);
        }

        return redirect()
            ->route('admin.onboarding-guides.edit', $guide)
            ->with('success', 'Onboarding guide draft saved.');
    }

    public function history(OnboardingGuide $guide): View
    {
        $guide->load([
            'versions' => fn ($query) => $query
                ->withCount([
                    'states as direct_completion_count' => fn ($states) => $states->whereNotNull('completed_at'),
                    'states as direct_dismissal_count' => fn ($states) => $states
                        ->whereNotNull('dismissed_at')
                        ->whereNull('completed_at'),
                ])
                ->latest('version_number'),
        ]);

        return view('admin.onboarding-guides.history', compact('guide'));
    }

    public function publish(
        PublishOnboardingGuideRequest $request,
        OnboardingGuide $guide,
        OnboardingGuideLifecycleService $lifecycle,
    ): RedirectResponse {
        $data = $request->validated();
        $lifecycle->publish(
            $request->user(),
            $guide,
            (bool) ($data['requires_recompletion'] ?? false),
            (bool) ($data['confirm_supersede'] ?? false),
        );

        return redirect()
            ->route('admin.onboarding-guides.edit', $guide)
            ->with('success', 'Onboarding guide published.');
    }

    public function unpublish(OnboardingGuide $guide, OnboardingGuideLifecycleService $lifecycle): RedirectResponse
    {
        $lifecycle->unpublish($guide);

        return redirect()
            ->route('admin.onboarding-guides.edit', $guide)
            ->with('success', 'Onboarding guide unpublished.');
    }

    public function archive(OnboardingGuide $guide, OnboardingGuideLifecycleService $lifecycle): RedirectResponse
    {
        $lifecycle->archive($guide);

        return redirect()
            ->route('admin.onboarding-guides.index')
            ->with('success', 'Onboarding guide archived.');
    }

    private function hasOtherPublishedGuide(OnboardingGuide $guide): bool
    {
        return OnboardingGuide::query()
            ->where('guide_key', $guide->guide_key)
            ->where('target_role', $guide->target_role)
            ->where('status', OnboardingGuide::STATUS_PUBLISHED)
            ->whereKeyNot($guide->getKey())
            ->exists();
    }
}
