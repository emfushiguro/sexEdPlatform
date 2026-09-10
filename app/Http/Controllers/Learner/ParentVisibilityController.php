<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\UpdateGuardianSupportAccessRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ParentVisibilityController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $profileSelectColumns = [
            'id',
            'user_id',
            'username',
            'avatar_path',
            'birthdate',
            'gender',
            'city_code',
            'barangay_code',
            'bio',
        ];

        if (Schema::hasColumn('learner_profiles', 'about')) {
            $profileSelectColumns[] = 'about';
        }

        $parentLinks = $user->accessibleGuardianLinks()
            ->with([
                'parent:id,name,email,birthdate,created_at',
                'parent.learnerProfile:'.implode(',', $profileSelectColumns),
                'parent.learnerProfile.city:code,name',
                'parent.learnerProfile.barangay:code,name',
            ])
            ->orderByDesc('relationship_verified_at')
            ->get()
            ->filter(fn ($link) => $link->parent)
            ->values();

        if ($parentLinks->isEmpty()) {
            return redirect()->route('learner.dashboard')
                ->with('info', 'No linked guardian account found.');
        }

        return view('learner.parent.index', [
            'parentLinks' => $parentLinks,
        ]);
    }

    public function updateSupportInformationAccess(
        UpdateGuardianSupportAccessRequest $request,
        ParentChildAccount $parentChildAccount,
        DependentSupportInformationService $supportInformation,
    ): RedirectResponse {
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);

        $supportInformation->setGuardianAccess(
            $parentChildAccount,
            $dependent,
            $request->enabled(),
        );

        return redirect()->route('learner.parent.index')->with(
            'success',
            $request->enabled()
                ? 'Guardian support-information access enabled.'
                : 'Guardian support-information access disabled.',
        );
    }
}
