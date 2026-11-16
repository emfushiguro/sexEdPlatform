<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdultDependentConsentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $this->ensureEligible($user);

        return $user->hasAcceptedDependentAdultAuthorization()
            ? redirect()->route('learner.identity.create')
            : view('auth.adult-dependent-consent');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['adult_consent' => ['accepted']]);
        $user = $request->user();
        $this->ensureEligible($user);

        DB::transaction(function () use ($user): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureEligible($locked);
            if (DB::table('dependent_adult_authorization_records')->where('dependent_user_id', $locked->id)->exists()) {
                return;
            }

            DB::table('dependent_adult_authorization_records')->insert([
                'dependent_user_id' => $locked->id,
                'parent_child_account_id' => $locked->guardianRelationships()->latest('id')->value('id'),
                'authorization_method' => 'self',
                'authorized_by_user_id' => $locked->id,
                'policy_version' => config('learner_age_policy.adult_dependent_consent_version', 'v1'),
                'privacy_notice_hash' => hash_file('sha256', resource_path('views/legal/privacy.blade.php')),
                'terms_hash' => hash_file('sha256', resource_path('views/legal/terms.blade.php')),
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('learner.identity.create');
    }

    private function ensureEligible(?User $user): void
    {
        abort_unless($user?->isLearner()
            && ! $user->isParentRegistration()
            && $user->minor_account_activated_at !== null
            && LearnerAgePolicy::classify($user->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT, 403);
    }
}
