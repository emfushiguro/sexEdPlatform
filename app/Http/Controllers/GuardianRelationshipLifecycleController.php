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
                    'authorization' => 'Complete Guardian verification and onboarding before authorizing this Teenager.',
                ]);
            }

            if ($relationship->relationship_status !== ParentChildAccount::STATUS_ACTIVE
                || $relationship->relationship_verified_status !== ParentChildAccount::VERIFICATION_VERIFIED
                || $relationship->relationship_verified_at === null
                || LearnerAgePolicy::classify($child->birthdate) !== LearnerAgePolicy::ELIGIBLE_TEENAGER) {
                throw ValidationException::withMessages([
                    'authorization' => 'Only an active verified relationship for a current Teenager can be authorized.',
                ]);
            }

            $relationship->forceFill([
                'minor_registration_authorized_at' => now(),
                'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
                'minor_registration_authorized_by_user_id' => $guardian->id,
            ])->save();
        });

        return back()->with('success', 'Current Teenager guardian authorization recorded.');
    }
}
