<?php

namespace App\Http\Controllers\Parent;

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

    public function edit(Request $request, User $child): Response
    {
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);
        $profile = $child->dependentSupportProfile()->first();

        $profile
            ? Gate::forUser($guardian)->authorize('view', $profile)
            : Gate::forUser($guardian)->authorize('create', [DependentSupportProfile::class, $child]);

        return response()->view('dependent-support-information.edit', [
            'dependent' => $child,
            'profile' => $profile,
            'saveRoute' => route('parent.children.support-information.save', $child),
            'deleteRoute' => route('parent.children.support-information.destroy', $child),
            'backRoute' => route('parent.children.index'),
            'viewerContext' => 'guardian',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(StoreDependentSupportInformationRequest $request, User $child): RedirectResponse
    {
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);

        if (! $request->boolean('has_relevant_support_information')) {
            return back()->with('info', 'No support information was saved.');
        }

        $this->supportInformation->save(
            $child,
            $guardian,
            $request->supportPayload(),
            $request->validated('expected_updated_at'),
        );

        return redirect()->route('parent.children.support-information.edit', $child)
            ->with('success', 'Support information was saved.');
    }

    public function destroy(Request $request, User $child): RedirectResponse
    {
        $validated = $request->validate([
            'expected_updated_at' => StoreDependentSupportInformationRequest::expectedUpdatedAtRules(true),
            'confirm_removal' => ['accepted'],
        ]);
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);
        $profile = $child->dependentSupportProfile()->firstOrFail();

        $this->supportInformation->remove($profile, $guardian, $validated['expected_updated_at']);

        return redirect()->route('parent.children.support-information.edit', $child)
            ->with('success', 'Support information was removed.');
    }
}
