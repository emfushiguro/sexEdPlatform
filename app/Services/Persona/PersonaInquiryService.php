<?php

namespace App\Services\Persona;

use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PersonaInquiryService
{
    public function __construct(
        private readonly PersonaClient $client,
        private readonly PersonaTemplateSelector $selector,
        private readonly LearnerIdentityRequirement $requirement,
        private readonly PersonaInquirySynchronizer $synchronizer,
    ) {}

    /** @return array{attempt: PersonaInquiry, session_token: string} */
    public function start(User $user, string $documentType, ?string $governmentIdType): array
    {
        $state = DB::transaction(function () use ($user, $documentType, $governmentIdType): array {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            [$case, $targetRound] = $this->lockedContext($lockedUser);
            $selection = $this->selector->select($lockedUser, $case, $documentType, $governmentIdType);
            $attempt = $this->findAttempt($lockedUser, $case, $targetRound, true);

            if (! $attempt) {
                $this->supersedeStaleAttempts($lockedUser, $case, $targetRound);
                $attempt = PersonaInquiry::query()->create([
                    'user_id' => $lockedUser->id,
                    'learner_identity_verification_id' => $case?->id,
                    'flow_type' => $selection['flow_type'],
                    'target_submission_round' => $targetRound,
                    'attempt_number' => ((int) PersonaInquiry::query()
                        ->where('user_id', $lockedUser->id)
                        ->max('attempt_number')) + 1,
                    'local_document_type' => $selection['local_document_type'],
                    'local_government_id_type' => $selection['local_government_id_type'],
                    'template_key' => $selection['template_key'],
                    'template_id' => $selection['template_id'],
                    'reference_id' => (string) Str::uuid(),
                    'idempotency_key' => (string) Str::uuid(),
                    'verification_outcome' => PersonaInquiry::OUTCOME_NOT_READY,
                    'is_sandbox' => true,
                ]);
            } elseif ($attempt->local_document_type !== $selection['local_document_type']
                || $attempt->local_government_id_type !== $selection['local_government_id_type']) {
                throw ValidationException::withMessages([
                    'persona' => 'A new Persona attempt is required to change the identity document choice.',
                ]);
            }

            return ['attempt' => $attempt, 'selection' => $selection];
        });

        return $this->openAttempt($state['attempt']);
    }

    /** @return array{attempt: PersonaInquiry, session_token: string} */
    public function resume(User $user): array
    {
        $attempt = $this->ownedCurrentAttempt($user);

        return $this->openAttempt($attempt);
    }

    public function returnFromPersona(User $user, string $personaInquiryId): PersonaInquiry
    {
        if ($personaInquiryId === '') {
            abort(404);
        }

        $attempt = $this->ownedCurrentAttempt($user);
        if ($attempt->persona_inquiry_id !== $personaInquiryId) {
            abort(404);
        }

        $this->synchronizer->sync($attempt);

        return $attempt->fresh();
    }

    public function refresh(User $user): PersonaInquiry
    {
        $attempt = $this->ownedCurrentAttempt($user);
        $this->synchronizer->sync($attempt);

        return $attempt->fresh();
    }

    /** @return array{attempt: PersonaInquiry, session_token: string} */
    public function replace(User $user, ?string $documentType = null, ?string $governmentIdType = null): array
    {
        $state = DB::transaction(function () use ($user, $documentType, $governmentIdType): PersonaInquiry {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            [$case, $targetRound] = $this->lockedContext($lockedUser);
            $current = $this->findAttempt($lockedUser, $case, $targetRound, true);

            if (! $current || ! $this->isReplaceable($current)) {
                throw ValidationException::withMessages([
                    'persona' => 'Only an unsuccessful or expired Persona attempt can be replaced.',
                ]);
            }

            $selection = $this->selector->select(
                $lockedUser,
                $case,
                $documentType ?? $current->local_document_type,
                $documentType === null ? $current->local_government_id_type : $governmentIdType,
            );
            $current->forceFill(['superseded_at' => now()])->save();

            return PersonaInquiry::query()->create([
                'user_id' => $lockedUser->id,
                'learner_identity_verification_id' => $case?->id,
                'flow_type' => $selection['flow_type'],
                'target_submission_round' => $targetRound,
                'attempt_number' => ((int) PersonaInquiry::query()
                    ->where('user_id', $lockedUser->id)
                    ->max('attempt_number')) + 1,
                'local_document_type' => $selection['local_document_type'],
                'local_government_id_type' => $selection['local_government_id_type'],
                'template_key' => $selection['template_key'],
                'template_id' => $selection['template_id'],
                'reference_id' => (string) Str::uuid(),
                'idempotency_key' => (string) Str::uuid(),
                'verification_outcome' => PersonaInquiry::OUTCOME_NOT_READY,
                'is_sandbox' => true,
            ]);
        });

        return $this->openAttempt($state);
    }

    public function currentAttempt(User $user): ?PersonaInquiry
    {
        try {
            [$case, $targetRound] = $this->context($user);
        } catch (DomainException|ValidationException) {
            return null;
        }

        return $this->findAttempt($user, $case, $targetRound);
    }

    public function hostedFlowUrl(PersonaInquiry $attempt, string $sessionToken): string
    {
        return rtrim((string) config('persona.hosted_flow_url'), '/').'?'.http_build_query([
            'inquiry-id' => $attempt->persona_inquiry_id,
            'session-token' => $sessionToken,
            'redirect-uri' => route('persona.identity.return'),
        ]);
    }

    public function isRequired(): bool
    {
        return (bool) config('persona.enabled', false);
    }

    public function assertLearnerMaySubmit(
        User $learner,
        LearnerIdentityVerification $case,
        string $documentType,
        ?string $governmentIdType,
    ): PersonaInquiry {
        if (! $learner->isLearner() || $learner->isParentRegistration()
            || (int) $case->user_id !== (int) $learner->id
            || $case->superseded_at !== null
            || ! in_array($case->status, [null, 'rejected'], true)) {
            throw new DomainException('Persona verification is required before local identity submission.');
        }

        $targetRound = (int) $case->submission_round + 1;
        $attempt = $this->findAttempt($learner, $case, $targetRound, true);
        if (config('persona.environment', 'sandbox') !== 'sandbox'
            || ! $attempt || ! $attempt->isPolicyEligible()
            || $attempt->local_document_type !== $documentType
            || $attempt->local_government_id_type !== $governmentIdType) {
            throw new DomainException('Persona verification is required before local identity submission.');
        }

        $expectedFlow = match ([$case->pathway, $documentType]) {
            ['teen', 'school_id'], ['teen', 'institution_id'] => PersonaInquiry::FLOW_TEEN_SCHOOL,
            ['teen', 'government_id'] => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
            ['adult', 'government_id'] => PersonaInquiry::FLOW_ADULT_GOVERNMENT,
            default => null,
        };
        if ($attempt->flow_type !== $expectedFlow) {
            throw new DomainException('Persona verification is required before local identity submission.');
        }

        return $attempt;
    }

    public function assertGuardianMaySubmit(User $guardian, string $governmentIdType): PersonaInquiry
    {
        $lockedGuardian = User::query()->whereKey($guardian->getKey())->lockForUpdate()->firstOrFail();
        if (! $lockedGuardian->isParentRegistration()
            || ! $lockedGuardian->hasVerifiedEmail()
            || ! in_array($lockedGuardian->parent_verification_status, [null, 'rejected'], true)) {
            throw new DomainException('Persona verification is required before guardian submission.');
        }

        $targetRound = $this->guardianTargetRound($lockedGuardian);
        $attempt = $this->findAttempt($lockedGuardian, null, $targetRound, true);
        if (config('persona.environment', 'sandbox') !== 'sandbox'
            || ! $attempt || ! $attempt->isPolicyEligible()
            || $attempt->flow_type !== PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT
            || $attempt->local_government_id_type !== $governmentIdType) {
            throw new DomainException('Persona verification is required before guardian submission.');
        }

        return $attempt;
    }

    public function assertLearnerMayApprove(User $learner, LearnerIdentityVerification $case): ?PersonaInquiry
    {
        if (! $this->isRequired() && $case->evidence_source !== 'persona' && ! $case->usesPersonaSelfie()) {
            return null;
        }

        $attempt = PersonaInquiry::query()
            ->where('user_id', $learner->id)
            ->where('learner_identity_verification_id', $case->id)
            ->where('target_submission_round', $case->submission_round)
            ->whereNull('superseded_at')
            ->latest('attempt_number')
            ->lockForUpdate()
            ->first();

        if (! $attempt && $case->evidence_source !== 'persona' && ! $case->usesPersonaSelfie() && $this->isGrandfathered($case->submitted_at)) {
            return null;
        }

        $expectedFlow = match ([$case->pathway, $case->document_type]) {
            ['teen', 'school_id'], ['teen', 'institution_id'] => PersonaInquiry::FLOW_TEEN_SCHOOL,
            ['teen', 'government_id'] => PersonaInquiry::FLOW_TEEN_GOVERNMENT,
            ['adult', 'government_id'] => PersonaInquiry::FLOW_ADULT_GOVERNMENT,
            default => null,
        };
        $expectedIdType = $case->document_type === 'government_id' ? $case->government_id_type : null;

        if (! $attempt
            || ! $attempt->is_sandbox
            || config('persona.environment', 'sandbox') !== 'sandbox'
            || $attempt->user_id !== $learner->id
            || $attempt->flow_type !== $expectedFlow
            || $attempt->local_document_type !== $case->document_type
            || $attempt->local_government_id_type !== $expectedIdType
            || ($case->evidence_source === 'persona' && (! $attempt->government_id_verification_id || ! $attempt->selfie_verification_id))
            || ($case->usesPersonaSelfie() && ! $attempt->selfie_verification_id)
            || ! in_array($attempt->persona_status, ['completed', 'approved'], true)
            || ! $attempt->isPolicyEligible()) {
            throw new DomainException('Persona verification is required before learner approval.');
        }

        return $attempt;
    }

    public function assertGuardianMayApprove(User $guardian): ?PersonaInquiry
    {
        if (! $this->isRequired() && $guardian->parent_identity_evidence_source !== 'persona') {
            return null;
        }

        $round = max(
            1,
            (int) $guardian->guardianIdentityAudits()->max('attempt_number'),
            (int) $guardian->personaInquiries()->max('target_submission_round'),
        );
        $attempt = PersonaInquiry::query()
            ->where('user_id', $guardian->id)
            ->whereNull('learner_identity_verification_id')
            ->where('target_submission_round', $round)
            ->whereNull('superseded_at')
            ->latest('attempt_number')
            ->lockForUpdate()
            ->first();

        if (! $attempt && $guardian->parent_identity_evidence_source !== 'persona' && $this->isGrandfathered($guardian->parent_verification_submitted_at)) {
            return null;
        }

        if (! $attempt
            || ! $attempt->is_sandbox
            || config('persona.environment', 'sandbox') !== 'sandbox'
            || $attempt->user_id !== $guardian->id
            || $attempt->flow_type !== PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT
            || $attempt->local_document_type !== 'government_id'
            || $attempt->local_government_id_type !== $guardian->parent_id_type
            || ($guardian->parent_identity_evidence_source === 'persona' && (! $attempt->government_id_verification_id || ! $attempt->selfie_verification_id))
            || ! in_array($attempt->persona_status, ['completed', 'approved'], true)
            || ! $attempt->isPolicyEligible()) {
            throw new DomainException('Persona verification is required before guardian approval.');
        }

        return $attempt;
    }

    /** @return array{0: ?LearnerIdentityVerification, 1: int} */
    private function context(User $user): array
    {
        if ($user->isParentRegistration()) {
            if (in_array($user->parent_verification_status, ['pending', 'approved'], true)) {
                return [null, max(1, (int) $user->guardianIdentityAudits()->max('attempt_number'))];
            }

            return [null, $this->guardianTargetRound($user)];
        }

        if (! $user->isLearner()) {
            $this->invalid('This account is not eligible for Persona verification.');
        }

        $case = $this->requirement->current($user);
        if (! $case) {
            $this->invalid('This account is not eligible for Persona verification.');
        }

        return [$case, (int) $case->submission_round + (in_array($case->status, ['pending', 'approved'], true) ? 0 : 1)];
    }

    /** @return array{0: ?LearnerIdentityVerification, 1: int} */
    private function lockedContext(User $user): array
    {
        [$case, $targetRound] = $this->context($user);

        if (($user->isParentRegistration() && ! in_array($user->parent_verification_status, [null, 'rejected'], true))
            || ($case && ! in_array($case->status, [null, 'rejected'], true))) {
            $this->invalid('This application is already submitted for review.');
        }

        if ($case) {
            $case = LearnerIdentityVerification::query()->whereKey($case->getKey())->lockForUpdate()->firstOrFail();
        }

        return [$case, $targetRound];
    }

    private function ownedCurrentAttempt(User $user): PersonaInquiry
    {
        [$case, $targetRound] = $this->context($user);
        $attempt = $this->findAttempt($user, $case, $targetRound);

        if (! $attempt) {
            abort(404);
        }

        return $attempt;
    }

    private function findAttempt(
        User $user,
        ?LearnerIdentityVerification $case,
        int $targetRound,
        bool $lock = false,
    ): ?PersonaInquiry {
        $query = PersonaInquiry::query()
            ->where('user_id', $user->id)
            ->where('target_submission_round', $targetRound)
            ->whereNull('superseded_at')
            ->when($case, fn ($query) => $query->where('learner_identity_verification_id', $case->id))
            ->when(! $case, fn ($query) => $query->whereNull('learner_identity_verification_id'))
            ->latest('attempt_number');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function supersedeStaleAttempts(User $user, ?LearnerIdentityVerification $case, int $targetRound): void
    {
        PersonaInquiry::query()
            ->where('user_id', $user->id)
            ->whereNull('superseded_at')
            ->when($case, fn ($query) => $query->where('learner_identity_verification_id', $case->id))
            ->when(! $case, fn ($query) => $query->whereNull('learner_identity_verification_id'))
            ->where('target_submission_round', '!=', $targetRound)
            ->update(['superseded_at' => now()]);
    }

    private function guardianTargetRound(User $guardian): int
    {
        $latestAudit = (int) $guardian->guardianIdentityAudits()->max('attempt_number');
        $latestPersonaRound = (int) $guardian->personaInquiries()->max('target_submission_round');

        if ($guardian->parent_verification_status !== 'rejected') {
            return max(1, $latestPersonaRound);
        }

        $nextRound = max(1, $latestAudit + 1);

        return max($nextRound, $latestPersonaRound);
    }

    private function isReplaceable(PersonaInquiry $attempt): bool
    {
        return $attempt->isCurrent()
            && ($attempt->expired_at !== null
                || in_array($attempt->persona_status, ['failed', 'expired'], true)
                || (in_array($attempt->persona_status, ['completed', 'approved', 'declined', 'needs-review'], true)
                    && in_array($attempt->verification_outcome, [PersonaInquiry::OUTCOME_INELIGIBLE, PersonaInquiry::OUTCOME_INCONCLUSIVE], true))
                || $attempt->verification_outcome === PersonaInquiry::OUTCOME_RETRYABLE);
    }

    private function isGrandfathered(mixed $submittedAt): bool
    {
        $cutover = config('persona.enforced_from');
        if (! $cutover || ! $submittedAt) {
            return false;
        }

        try {
            return CarbonImmutable::parse($submittedAt)->lessThan(CarbonImmutable::parse($cutover));
        } catch (Throwable) {
            return false;
        }
    }

    /** @return array{attempt: PersonaInquiry, session_token: string} */
    private function openAttempt(PersonaInquiry $attempt): array
    {
        if (! $attempt->persona_inquiry_id) {
            try {
                $created = $this->client->createInquiry(
                    $attempt->template_id,
                    $attempt->idempotency_key,
                    $attempt->reference_id,
                );
                $personaId = (string) ($created['id'] ?? '');
                if ($personaId === '') {
                    throw new RuntimeException('Persona returned an invalid inquiry.', 502);
                }

                DB::transaction(function () use ($attempt, $personaId, $created): void {
                    $locked = PersonaInquiry::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
                    if ($locked->persona_inquiry_id && $locked->persona_inquiry_id !== $personaId) {
                        throw new RuntimeException('Persona returned a conflicting inquiry.', 502);
                    }
                    if (! $locked->persona_inquiry_id) {
                        $locked->forceFill([
                            'persona_inquiry_id' => $personaId,
                            'persona_status' => $created['status'] ?? null,
                            'failure_category' => null,
                        ])->save();
                    }
                });
                $attempt->refresh();
            } catch (Throwable $exception) {
                $this->recordFailure($attempt, $exception);
                throw $exception;
            }
        }

        try {
            $resumed = $this->client->resumeInquiry(
                (string) $attempt->persona_inquiry_id,
                hash('sha256', $attempt->idempotency_key.'|resume'),
            );
        } catch (Throwable $exception) {
            $this->recordFailure($attempt, $exception);
            throw $exception;
        }

        $sessionToken = (string) ($resumed['session_token'] ?? '');
        if ($sessionToken === '') {
            $this->recordFailure($attempt, new RuntimeException('Persona returned no session token.', 502));
            throw new RuntimeException('Persona returned no session token.', 502);
        }

        return ['attempt' => $attempt->fresh(), 'session_token' => $sessionToken];
    }

    private function recordFailure(PersonaInquiry $attempt, Throwable $exception): void
    {
        $category = match (true) {
            $exception->getCode() === 401 || $exception->getCode() === 403 => 'authentication_failure',
            $exception->getCode() === 503 => 'provider_unavailable',
            default => 'provider_rejected',
        };

        PersonaInquiry::query()->whereKey($attempt->getKey())->update(['failure_category' => $category]);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['persona' => $message]);
    }
}
