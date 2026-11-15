<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdultDependentAuthorityRequest;
use App\Models\ParentChildAccount;
use App\Notifications\AdultAuthorityReviewStatusNotification;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AdultDependentAuthorityReviewController extends Controller
{
    public function document(Request $request, ParentChildAccount $parentChildAccount, AdultDependentAuthorityRequest $authority): BinaryFileResponse
    {
        abort_unless($request->user()->hasRole('admin') && (int) $authority->parent_child_account_id === (int) $parentChildAccount->id, 404);
        abort_unless(in_array($authority->document_mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)
            && Storage::disk('local')->exists($authority->document_path), 404);

        return response()->file(Storage::disk('local')->path($authority->document_path), [
            'Content-Type' => $authority->document_mime,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function decide(Request $request, ParentChildAccount $parentChildAccount, AdultDependentAuthorityRequest $authority): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'authority_scope_confirmed' => [$request->input('decision') === 'approved' ? 'accepted' : 'prohibited'],
        ]);

        DB::transaction(function () use ($request, $parentChildAccount, $authority, $data): void {
            $relationship = ParentChildAccount::query()->lockForUpdate()->findOrFail($parentChildAccount->id);
            $locked = AdultDependentAuthorityRequest::query()->lockForUpdate()->findOrFail($authority->id);
            abort_unless((int) $locked->parent_child_account_id === (int) $relationship->id && $locked->status === 'pending', 409);
            abort_if(in_array((int) $request->user()->id, [(int) $relationship->parent_user_id, (int) $relationship->child_user_id], true), 403);
            if ($data['decision'] === 'approved') {
                abort_unless(ParentChildAccount::query()->accessEligible()->whereKey($relationship->id)->exists()
                    && LearnerAgePolicy::classify($relationship->child?->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT
                    && $relationship->child?->minor_account_activation_required
                    && hash_equals($locked->dependent_identity_hash, AdultDependentAuthorityRequest::identityHash($relationship->child))
                    && $locked->authorized_at->gte($relationship->relationship_verified_at), 409);
                $documentHash = Storage::disk('local')->exists($locked->document_path)
                    ? hash_file('sha256', Storage::disk('local')->path($locked->document_path)) : false;
                $privacyHash = hash_file('sha256', resource_path('views/legal/privacy.blade.php'));
                $termsHash = hash_file('sha256', resource_path('views/legal/terms.blade.php'));
                abort_unless(is_string($documentHash) && is_string($privacyHash) && is_string($termsHash)
                    && hash_equals($locked->document_hash, $documentHash)
                    && hash_equals($locked->privacy_notice_hash, $privacyHash)
                    && hash_equals($locked->terms_hash, $termsHash)
                    && $locked->policy_version === config('learner_age_policy.adult_dependent_consent_version'), 409);
            }

            $locked->update(['status' => $data['decision'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            DB::afterCommit(function () use ($relationship, $data): void {
                try {
                    $relationship->parent?->notify(new AdultAuthorityReviewStatusNotification($relationship, $data['decision']));
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        });

        return redirect()->route('admin.parent-verifications.relationships.show', $parentChildAccount)
            ->withFragment('adult-authority-'.$authority->id)
            ->with('success', $data['decision'] === 'approved'
                ? 'Authority approved. The Guardian has been notified to create an activation code; the dependent chooses their own password.'
                : 'Authority not approved. The Guardian has been notified and may submit new evidence.');
    }
}
