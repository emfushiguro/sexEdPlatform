<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Models\DependentSupportProfile;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DependentSupportInformationController extends Controller
{
    public function __construct(
        private readonly DependentSupportInformationService $supportInformation,
    ) {}

    public function edit(Request $request): Response
    {
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);
        $profile = $dependent->dependentSupportProfile()->first();

        $profile
            ? Gate::forUser($dependent)->authorize('view', $profile)
            : Gate::forUser($dependent)->authorize('create', [DependentSupportProfile::class, $dependent]);

        return response()->view('dependent-support-information.edit', [
            'dependent' => $dependent,
            'profile' => $profile,
            'saveRoute' => route('learner.support-information.save'),
            'deleteRoute' => route('learner.support-information.destroy'),
            'backRoute' => route('learner.dashboard'),
            'viewerContext' => 'dependent',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(StoreDependentSupportInformationRequest $request): RedirectResponse
    {
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);

        if (! $request->boolean('has_relevant_support_information')) {
            return back()->with('info', 'No support information was saved.');
        }

        $this->supportInformation->save(
            $dependent,
            $dependent,
            $request->supportPayload(),
            $request->validated('expected_updated_at'),
        );

        return redirect()->route('learner.support-information.edit')
            ->with('success', 'Your support information was saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expected_updated_at' => StoreDependentSupportInformationRequest::expectedUpdatedAtRules(true),
            'confirm_removal' => ['accepted'],
        ]);
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);
        $profile = $dependent->dependentSupportProfile()->firstOrFail();

        $this->supportInformation->remove($profile, $dependent, $validated['expected_updated_at']);

        return redirect()->route('learner.support-information.edit')
            ->with('success', 'Your support information was removed.');
    }
}
