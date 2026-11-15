<?php

namespace App\Http\Controllers;

use App\Models\AdultDependentAuthorityRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Notifications\Admin\AdultAuthorityReviewRequestedNotification;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdultDependentAuthorityController extends Controller
{
    public function store(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        abort_unless((int) $parentChildAccount->parent_user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'authority_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'authority_consent' => ['accepted'],
        ]);
        $file = $data['authority_document'];
        $path = null;

        try {
            DB::transaction(function () use ($request, $parentChildAccount, $file, &$path): void {
                $relationship = ParentChildAccount::query()->lockForUpdate()->findOrFail($parentChildAccount->id);
                $child = User::query()->lockForUpdate()->findOrFail($relationship->child_user_id);
                $guardian = $request->user();
                abort_unless((int) $relationship->parent_user_id === (int) $guardian->id
                    && $guardian->hasVerifiedEmail() && $guardian->isParentVerificationApproved()
                    && $guardian->hasCompletedGuardianOnboarding()
                    && ParentChildAccount::query()->accessEligible()->whereKey($relationship->id)->exists()
                    && LearnerAgePolicy::classify($child->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT
                    && $child->minor_account_activation_required, 403);

                if (AdultDependentAuthorityRequest::query()->where('parent_child_account_id', $relationship->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->where('authorized_at', '>=', $relationship->relationship_verified_at)
                    ->where('dependent_identity_hash', AdultDependentAuthorityRequest::identityHash($child))
                    ->where('policy_version', config('learner_age_policy.adult_dependent_consent_version'))
                    ->where('privacy_notice_hash', hash_file('sha256', resource_path('views/legal/privacy.blade.php')))
                    ->where('terms_hash', hash_file('sha256', resource_path('views/legal/terms.blade.php')))->exists()) {
                    throw ValidationException::withMessages(['authority_document' => 'An authority review is already pending or approved for this relationship.']);
                }

                $path = $file->store('adult-authority/'.$relationship->id, 'local');
                if (! $path || ! Storage::disk('local')->exists($path)) {
                    throw ValidationException::withMessages(['authority_document' => 'The document could not be stored. Please try again.']);
                }
                AdultDependentAuthorityRequest::query()->create([
                    'parent_child_account_id' => $relationship->id,
                    'guardian_user_id' => $guardian->id,
                    'document_path' => $path,
                    'document_mime' => $file->getMimeType(),
                    'document_hash' => hash_file('sha256', $file->getRealPath()),
                    'dependent_identity_hash' => AdultDependentAuthorityRequest::identityHash($child),
                    'status' => 'pending',
                    'policy_version' => config('learner_age_policy.adult_dependent_consent_version'),
                    'privacy_notice_hash' => hash_file('sha256', resource_path('views/legal/privacy.blade.php')),
                    'terms_hash' => hash_file('sha256', resource_path('views/legal/terms.blade.php')),
                    'authorized_at' => now(),
                ]);
                DB::afterCommit(function () use ($relationship): void {
                    try {
                        User::query()->role('admin')->get()->each(fn (User $admin) => $admin->notify(new AdultAuthorityReviewRequestedNotification($relationship)));
                    } catch (Throwable $exception) {
                        report($exception);
                    }
                });
            });
        } catch (Throwable $exception) {
            if (is_string($path) && $path !== '') {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Representative authority was submitted for separate administrative review.');
    }

    public function withdraw(Request $request, ParentChildAccount $parentChildAccount, AdultDependentAuthorityRequest $authority): RedirectResponse
    {
        DB::transaction(function () use ($request, $parentChildAccount, $authority): void {
            $relationship = ParentChildAccount::query()->lockForUpdate()->findOrFail($parentChildAccount->id);
            $locked = AdultDependentAuthorityRequest::query()->lockForUpdate()->findOrFail($authority->id);
            abort_unless((int) $relationship->parent_user_id === (int) $request->user()->id
                && (int) $locked->parent_child_account_id === (int) $relationship->id
                && (int) $locked->guardian_user_id === (int) $request->user()->id, 403);
            abort_unless($relationship->child?->minor_account_activation_required
                && $locked->status === 'pending', 409, 'Only a pending authority request can be withdrawn.');
            $locked->update(['status' => 'withdrawn']);
        });

        return back()->with('success', 'Representative authority was withdrawn.');
    }
}
