<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateGamificationPolicyRequest;
use App\Models\GamificationPolicy;
use App\Models\GamificationPolicyVersion;
use App\Services\Gamification\GamificationPolicyAdminService;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GamificationSettingsController extends Controller
{
    public function __construct(
        private readonly GamificationPolicyAdminService $adminService,
        private readonly GamificationPolicyResolver $resolver,
    ) {
    }

    public function index()
    {
        $this->ensureAuthorized();

        $activePolicy = GamificationPolicy::latestActive();
        $resolvedPolicy = $this->resolver->resolve();
        $versions = GamificationPolicyVersion::query()->latest('id')->limit(50)->get();

        return view('admin.gamification.settings', compact('activePolicy', 'resolvedPolicy', 'versions'));
    }

    public function update(UpdateGamificationPolicyRequest $request)
    {
        $this->ensureAuthorized();

        $this->adminService->updatePolicy(
            payload: $request->payload(),
            adminId: Auth::id(),
            changeSummary: $request->input('change_summary'),
            versionLabel: $request->input('version_label'),
        );

        return redirect()
            ->route('admin.gamification-settings.index')
            ->with('success', 'Gamification settings updated successfully.');
    }

    public function history()
    {
        $this->ensureAuthorized();

        $versions = GamificationPolicyVersion::query()->latest('id')->paginate(25);

        return response()->json([
            'data' => $versions->items(),
            'pagination' => [
                'current_page' => $versions->currentPage(),
                'last_page' => $versions->lastPage(),
                'total' => $versions->total(),
            ],
        ]);
    }

    public function restore(int $version)
    {
        $this->ensureAuthorized();

        $this->adminService->restoreVersion(
            versionId: $version,
            adminId: Auth::id(),
            changeSummary: 'Restored via admin settings.',
        );

        return redirect()
            ->route('admin.gamification-settings.index')
            ->with('success', 'Gamification settings restored from selected version.');
    }

    private function ensureAuthorized(): void
    {
        $user = Auth::user();
        abort_unless($user !== null && Gate::forUser($user)->allows('manage system settings'), 403);
    }
}
