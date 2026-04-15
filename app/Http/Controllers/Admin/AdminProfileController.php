<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminCreatorProfileRequest;
use App\Services\Admin\AdminCreatorProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function __construct(
        private readonly AdminCreatorProfileService $profileService,
    ) {
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateForUser($user);

        return view('admin.profile.show', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateForUser($user);

        return view('admin.profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    public function update(UpdateAdminCreatorProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateForUser($user);

        $this->authorize('update', $profile);

        $validated = $request->validated();
        $validated['show_individual_attribution'] = $request->boolean('show_individual_attribution');

        $this->profileService->updateFromValidatedPayload(
            $user,
            $validated,
            $request->file('avatar')
        );

        return redirect()->route('admin.profile.show')
            ->with('success', 'Admin profile updated successfully.');
    }
}
