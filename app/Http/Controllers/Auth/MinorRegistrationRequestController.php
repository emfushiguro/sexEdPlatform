<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreMinorRegistrationRequest;
use App\Models\MinorRegistrationRequest;
use App\Models\User;
use App\Notifications\GuardianMinorRegistrationInvitation;
use App\Services\Auth\MinorAccountHandoffService;
use App\Support\AccountEmail;
use App\Support\LearnerAgePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

final class MinorRegistrationRequestController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $child = null;
        $existingTeenager = false;

        if ($request->user() !== null) {
            if (! $request->user()->isLearner()
                || ! in_array(LearnerAgePolicy::classify($request->user()->birthdate), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
                return redirect()->route('learner.dashboard');
            }

            $child = $request->user();
            $existingTeenager = true;
        }

        $pendingMinorRegistration = (array) session('pending_minor_registration', []);

        return view('auth.minor-registration-request', compact('child', 'existingTeenager', 'pendingMinorRegistration'));
    }

    public function store(
        StoreMinorRegistrationRequest $request,
        MinorAccountHandoffService $handoffs,
    ): RedirectResponse {
        $validated = $request->validated();
        $existingTeenager = $request->user();

        if ($existingTeenager !== null) {
            if (! $existingTeenager->isLearner()
                || ! in_array(LearnerAgePolicy::classify($existingTeenager->birthdate), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
                throw ValidationException::withMessages(['guardian_email' => 'This account cannot start a Guardian handoff.']);
            }
        } elseif (! in_array(LearnerAgePolicy::classify($validated['birthdate'] ?? null), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
            throw ValidationException::withMessages(['birthdate' => 'Enter a valid birthdate to continue.']);
        }

        $guardianEmail = AccountEmail::normalize((string) $validated['guardian_email']);
        $rawToken = $handoffs->generateCode();
        $registrationRequest = DB::transaction(function () use ($existingTeenager, $validated, $guardianEmail, $rawToken, $handoffs): MinorRegistrationRequest {
            $existingQuery = MinorRegistrationRequest::query()
                ->where('status', MinorRegistrationRequest::STATUS_PENDING);

            if ($existingTeenager !== null) {
                $existingQuery->where('existing_child_user_id', $existingTeenager->id);
            } else {
                $existingQuery->where('guardian_email_fingerprint', AccountEmail::fingerprint($guardianEmail));
            }

            $existingQuery->update(['status' => MinorRegistrationRequest::STATUS_EXPIRED]);

            return MinorRegistrationRequest::query()->create([
                'existing_child_user_id' => $existingTeenager?->id,
                'guardian_email' => $guardianEmail,
                'guardian_email_fingerprint' => AccountEmail::fingerprint($guardianEmail),
                'minor_payload' => $existingTeenager === null
                    ? Arr::only($validated, ['first_name', 'last_name', 'birthdate'])
                    : null,
                'token_hash' => $handoffs->digestForStorage($rawToken),
                'status' => MinorRegistrationRequest::STATUS_PENDING,
                'expires_at' => now()->addDays((int) config('learner_age_policy.minor_registration_request_days')),
            ]);
        });

        DB::afterCommit(function () use ($registrationRequest, $rawToken, $guardianEmail): void {
            try {
                Notification::route('mail', $guardianEmail)
                    ->notify(new GuardianMinorRegistrationInvitation($registrationRequest, $rawToken));
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        $request->session()->forget('pending_minor_registration');

        return redirect()->route('minor.registration.create')
            ->with('success', 'If that guardian email can receive mail, an invitation is on its way.');
    }

    public function review(string $token, MinorAccountHandoffService $handoffs): View|RedirectResponse
    {
        $registrationRequest = $this->findPending($token, $handoffs);

        if ($registrationRequest === null) {
            return redirect()->route('minor.registration.create')
                ->withErrors(['guardian_email' => 'This invitation is invalid or expired. Ask the dependent to send a new request.']);
        }

        return view('auth.guardian-invitation-review', ['token' => $token]);
    }

    public function accept(
        Request $request,
        string $token,
        MinorAccountHandoffService $handoffs,
    ): View|RedirectResponse {
        $registrationRequest = $this->findPending($token, $handoffs);
        if ($registrationRequest === null) {
            $this->clearInvitationSession($token);

            return redirect()->route('minor.registration.create')
                ->withErrors(['guardian_email' => 'This invitation is invalid or expired.']);
        }

        session([
            'minor_registration_request_id' => $registrationRequest->id,
            'minor_registration_token' => $token,
        ]);

        if (! $request->user()) {
            if ($request->query('action') === 'register') {
                session(['url.intended' => route('minor.registration.accept', ['token' => $token])]);
                return redirect()->route('parent.register');
            }

            return redirect()->guest(route('login'));
        }

        $guardian = $request->user();
        if ($registrationRequest->accepted_by_user_id !== null
            && $registrationRequest->accepted_by_user_id !== $guardian->id) {
            $this->clearInvitationSession($token);

            return redirect()->route('minor.registration.create')
                ->withErrors(['guardian_email' => 'This invitation is not available for this account.']);
        }

        if (! $guardian->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $guardian->isParentRegistration()) {
            return redirect()->route('parent.register')
                ->with('info', 'Register or sign in with a guardian account using an email you control.');
        }

        if (! $guardian->isParentVerificationApproved()) {
            return redirect()->route('guardian.verification.status');
        }

        if (! $guardian->hasCompletedGuardianOnboarding()) {
            return redirect()->route('guardian.onboarding.show');
        }

        if ($registrationRequest->accepted_by_user_id === null) {
            return view('auth.guardian-invitation-claim', compact('token', 'guardian'));
        }

        $pendingChild = $registrationRequest->existing_child_user_id
            ? $this->existingChildPrefill($registrationRequest->existingChild)
            : ($registrationRequest->minor_payload ?? []);

        session(['pending_child_registration' => $pendingChild]);

        return redirect()->route('parent.create-child');
    }

    public function claim(Request $request, string $token, MinorAccountHandoffService $handoffs): RedirectResponse
    {
        $request->validate(['confirm_claim' => ['accepted']]);
        $guardian = $request->user();
        abort_unless($guardian && $guardian->status === User::STATUS_ACTIVE
            && $guardian->hasVerifiedEmail() && $guardian->isParentRegistration()
            && $guardian->isParentVerificationApproved() && $guardian->hasCompletedGuardianOnboarding(), 403);

        DB::transaction(function () use ($token, $handoffs, $guardian): void {
            $invitation = MinorRegistrationRequest::query()
                ->where('token_hash', $handoffs->digestForStorage($token))->lockForUpdate()->first();
            if (! $invitation || $invitation->status !== MinorRegistrationRequest::STATUS_PENDING
                || ! $invitation->expires_at || $invitation->expires_at->lte(now())
                || ($invitation->accepted_by_user_id !== null && $invitation->accepted_by_user_id !== $guardian->id)) {
                $this->clearInvitationSession($token);

                throw ValidationException::withMessages(['guardian_email' => 'This invitation is invalid, expired, or already claimed.']);
            }
            if ($invitation->accepted_by_user_id === null) {
                $invitation->update(['accepted_by_user_id' => $guardian->id, 'accepted_at' => now()]);
            }
        });

        return redirect()->route('minor.registration.accept', $token);
    }

    private function clearInvitationSession(string $token): void
    {
        if (session('minor_registration_token') === $token) {
            session()->forget(['minor_registration_request_id', 'minor_registration_token', 'pending_child_registration']);
        }
    }

    private function findPending(string $token, MinorAccountHandoffService $handoffs): ?MinorRegistrationRequest
    {
        return MinorRegistrationRequest::query()
            ->where('token_hash', $handoffs->digestForStorage($token))
            ->where('status', MinorRegistrationRequest::STATUS_PENDING)
            ->where('expires_at', '>', now())
            ->with('existingChild.learnerProfile')
            ->first();
    }

    private function existingChildPrefill(?User $child): array
    {
        if ($child === null || ! in_array(LearnerAgePolicy::classify($child->birthdate), [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT], true)) {
            throw ValidationException::withMessages(['guardian_email' => 'This invitation is invalid or expired.']);
        }

        return [
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'middle_initial' => $child->middle_initial,
            'suffix' => $child->suffix,
            'birthdate' => $child->birthdate?->toDateString(),
            'gender' => $child->learnerProfile?->gender,
        ];
    }
}
