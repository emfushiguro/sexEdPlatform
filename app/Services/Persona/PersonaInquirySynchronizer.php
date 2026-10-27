<?php

namespace App\Services\Persona;

use App\Models\PersonaInquiry;
use App\Notifications\PersonaActionRequiredNotification;
use App\Notifications\PersonaReadyForLocalEvidenceNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PersonaInquirySynchronizer
{
    public function __construct(
        private readonly PersonaClient $client,
        private readonly PersonaPolicyEvaluator $evaluator,
        private readonly PersonaReviewSubmission $reviewSubmission,
    ) {}

    public function sync(PersonaInquiry $attempt, ?CarbonImmutable $eventCreatedAt = null): PersonaInquiry
    {
        if (! $attempt->persona_inquiry_id) {
            throw new RuntimeException('Persona attempt is not associated with an inquiry.', 422);
        }

        $inquiry = $this->client->retrieveInquiry($attempt->persona_inquiry_id);
        $this->includeTypedVerifications($inquiry);
        $result = $this->evaluator->evaluate($attempt, $inquiry);
        $sourceAt = $this->sourceTimestamp($inquiry, $eventCreatedAt);

        return DB::transaction(function () use ($attempt, $result, $sourceAt): PersonaInquiry {
            // Match start/replacement lock order so webhook and browser reconciliation serialize.
            \App\Models\User::query()->whereKey($attempt->user_id)->lockForUpdate()->firstOrFail();
            if ($attempt->learner_identity_verification_id) {
                \App\Models\LearnerIdentityVerification::query()->whereKey($attempt->learner_identity_verification_id)->lockForUpdate()->first();
            }
            $current = PersonaInquiry::query()->whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();
            if (! $current->isCurrent()
                || ($sourceAt && $current->persona_event_at && $sourceAt->lessThan($current->persona_event_at))) {
                return $current;
            }

            $previousOutcome = $current->verification_outcome;
            $becameEligible = $previousOutcome !== PersonaInquiry::OUTCOME_ELIGIBLE
                && $result['verification_outcome'] === PersonaInquiry::OUTCOME_ELIGIBLE;
            $needsAction = $previousOutcome !== PersonaInquiry::OUTCOME_RETRYABLE
                && $result['verification_outcome'] === PersonaInquiry::OUTCOME_RETRYABLE;

            $current->forceFill([
                ...$result,
                'persona_event_at' => $sourceAt,
                'last_synced_at' => now(),
            ])->save();

            $this->reviewSubmission->submit($current);

            if (($becameEligible && $current->flow_type === PersonaInquiry::FLOW_TEEN_SCHOOL) || $needsAction) {
                $user = $current->user;
                $actionUrl = $user?->isParentRegistration()
                    ? route('guardian.verification.status')
                    : route('learner.identity.status');
                $notification = $becameEligible
                    ? new PersonaReadyForLocalEvidenceNotification($actionUrl)
                    : new PersonaActionRequiredNotification($actionUrl);

                DB::afterCommit(function () use ($user, $notification, $current): void {
                    if (! $user) {
                        return;
                    }

                    try {
                        $user->notify($notification);
                    } catch (\Throwable) {
                        Log::warning('Persona transition notification failed.', [
                            'user_id' => $user->id,
                            'attempt_id' => $current->id,
                            'notification' => $notification::class,
                        ]);
                    }
                });
            }

            return $current->fresh();
        });
    }

    private function includeTypedVerifications(array &$inquiry): void
    {
        $references = (array) data_get($inquiry, 'data.relationships.verifications.data', []);
        $included = (array) ($inquiry['included'] ?? []);

        foreach ($references as $reference) {
            if (! is_array($reference) || ! is_string($reference['id'] ?? null)) {
                continue;
            }

            $resource = match ($reference['type'] ?? null) {
                'verification/government-id' => $this->client->retrieveGovernmentId($reference['id']),
                'verification/selfie' => $this->client->retrieveSelfie($reference['id']),
                default => null,
            };
            if (is_array($resource) && is_array($resource['data'] ?? null)) {
                $included[] = $resource['data'];
            }
        }

        $inquiry['included'] = $included;
    }

    private function sourceTimestamp(array $inquiry, ?CarbonImmutable $eventCreatedAt): ?CarbonImmutable
    {
        $updatedAt = data_get($inquiry, 'data.attributes.updated-at');
        if (is_string($updatedAt) && trim($updatedAt) !== '') {
            try {
                return CarbonImmutable::parse($updatedAt);
            } catch (\Throwable) {
                // Use the signed webhook event timestamp when the provider timestamp is unusable.
            }
        }

        return $eventCreatedAt;
    }
}
