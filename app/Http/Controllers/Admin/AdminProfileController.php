<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminCreatorProfileRequest;
use App\Services\Admin\AdminCreatorProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    private const EDITABLE_TABS = ['public', 'credentials'];

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
            'forceOpenEditModal' => false,
            'editModalTab' => 'public',
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateForUser($user);

        $requestedTab = (string) $request->query('tab', 'public');
        $activeTab = in_array($requestedTab, self::EDITABLE_TABS, true)
            ? $requestedTab
            : 'public';

        return view('admin.profile.show', [
            'user' => $user,
            'profile' => $profile,
            'forceOpenEditModal' => true,
            'editModalTab' => $activeTab,
        ]);
    }

    public function update(UpdateAdminCreatorProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $this->profileService->getOrCreateForUser($user);

        $this->authorize('update', $profile);

        $validated = $request->validated();
        $activeTab = (string) ($validated['profile_tab'] ?? 'public');

        if ($activeTab === 'credentials') {
            if ((string) $user->email !== (string) $validated['email']) {
                $user->email = (string) $validated['email'];
            }

            if (!empty($validated['new_password'])) {
                $user->password = Hash::make((string) $validated['new_password']);
            }

            if ($user->isDirty()) {
                $user->save();
            }

            return redirect()->route('admin.profile.show')
                ->with('success', 'Account credentials updated successfully.');
        }

        $this->profileService->updateFromValidatedPayload(
            $user,
            [
                'public_display_name' => $validated['public_display_name'],
                'bio' => $validated['bio'] ?? null,
                'affiliation' => $validated['affiliation'],
                'show_individual_attribution' => $request->boolean('show_individual_attribution'),
            ],
            $request->file('avatar')
        );

        return redirect()->route('admin.profile.show')
            ->with('success', 'Admin profile updated successfully.');
    }
}
