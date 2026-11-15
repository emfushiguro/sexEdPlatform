<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeactivateGuardianRelationshipRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardianRelationshipLifecycleController extends Controller
{
    public function __construct(private readonly GuardianRelationshipVerificationService $service) {}

    public function deactivate(
        DeactivateGuardianRelationshipRequest $request,
        ParentChildAccount $parentChildAccount,
    ): RedirectResponse {
        $this->service->deactivate(
            $parentChildAccount,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'Guardian relationship deactivated.');
    }

    public function reactivate(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        abort_unless(
            in_array((int) $request->user()->id, [
                (int) $parentChildAccount->parent_user_id,
                (int) $parentChildAccount->child_user_id,
            ], true) || $request->user()->hasRole('admin'),
            403,
        );

        $this->service->requestReactivation($parentChildAccount, $request->user());

        return back()->with('success', 'Relationship reactivation submitted for administrative review.');
    }

    public function authorizeMinor(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        abort_unless((int) $parentChildAccount->parent_user_id === (int) $request->user()->id, 403);
        $request->validate(['authorization' => ['accepted']]);

        DB::transaction(function () use ($request, $parentChildAccount): void {
            $relationship = ParentChildAccount::query()->lockForUpdate()->findOrFail($parentChildAccount->id);
            $guardian = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $child = User::query()->lockForUpdate()->findOrFail($relationship->child_user_id);

            if ((int) $relationship->parent_user_id !== (int) $guardian->id) {
                abort(403);
            }

            if (! $guardian->isParentRegistration()
                || ! $guardian->hasVerifiedEmail()
                || ! $guardian->isParentVerificationApproved()
                || ! $guardian->hasCompletedGuardianOnboarding()) {
                throw ValidationException::withMessages([
                    'authorization' => 'Complete Guardian verification and onboarding before authorizing this dependent.',
                ]);
            }

            if ($relationship->relationship_status !== ParentChildAccount::STATUS_ACTIVE
                || $relationship->relationship_verified_status !== ParentChildAccount::VERIFICATION_VERIFIED
                || $relationship->relationship_verified_at === null
                || LearnerAgePolicy::classify($child->birthdate) !== LearnerAgePolicy::ELIGIBLE_TEENAGER) {
                throw ValidationException::withMessages([
                    'authorization' => 'Only an active verified relationship for a minor can be authorized here.',
                ]);
            }

            $relationship->forceFill([
                'minor_registration_authorized_at' => now(),
                'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
                'minor_registration_authorized_by_user_id' => $guardian->id,
            ])->save();

            DB::table('minor_authorization_records')->insert([
                'parent_child_account_id' => $relationship->id,
                'guardian_user_id' => $guardian->id,
                'consent_version' => config('learner_age_policy.guardian_consent_version'),
                'privacy_notice_hash' => hash_file('sha256', resource_path('views/legal/privacy.blade.php')),
                'terms_hash' => hash_file('sha256', resource_path('views/legal/terms.blade.php')),
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Guardian authorization for this dependent was recorded.');
    }
}
