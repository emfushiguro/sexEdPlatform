<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonaInquiry;
use App\Services\Persona\PersonaClient;
use Illuminate\Http\Response;
use RuntimeException;

class PersonaEvidenceController extends Controller
{
    public function __construct(
        private readonly PersonaClient $personaClient,
    ) {}

    public function show(PersonaInquiry $personaInquiry, string $artifact): Response
    {
        abort_unless($this->isReviewable($personaInquiry), 404);

        try {
            $url = match ($artifact) {
                'selfie' => $this->selfieUrl($personaInquiry),
                'selfie-left' => $this->selfieUrl($personaInquiry, 'left'),
                'selfie-right' => $this->selfieUrl($personaInquiry, 'right'),
                'government-id-front' => $this->governmentIdUrl($personaInquiry, 'front'),
                'government-id-back' => $this->governmentIdUrl($personaInquiry, 'back'),
                default => null,
            };
        } catch (RuntimeException) {
            abort(503, 'Persona media is temporarily unavailable.');
        }

        abort_if($url === null, 404);

        try {
            $media = $this->personaClient->downloadMedia($url);
        } catch (RuntimeException) {
            abort(503, 'Persona media is temporarily unavailable.');
        }

        return response($media['bytes'], 200, [
            'Content-Type' => $media['content_type'],
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isReviewable(PersonaInquiry $attempt): bool
    {
        if (! $attempt->is_sandbox || ! $attempt->isCurrent()
            || config('persona.environment', 'sandbox') !== 'sandbox') {
            return false;
        }

        $user = $attempt->user;
        if (! $user) {
            return false;
        }

        if ($attempt->flow_type === PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT) {
            $round = max(1, (int) $user->guardianIdentityAudits()->max('attempt_number'));

            return $user->isParentRegistration() && $attempt->learner_identity_verification_id === null
                && $attempt->target_submission_round === $round;
        }

        if ($attempt->flow_type === PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT) {
            $case = $attempt->instructorIdentityVerification;

            return $case !== null
                && auth()->user()?->hasPermissionTo('review instructor identities')
                && (int) $case->user_id === (int) $user->id
                && $case->superseded_at === null
                && (int) $attempt->instructor_identity_verification_id === (int) $case->id
                && $attempt->target_submission_round === $case->submission_round;
        }

        $case = $attempt->learnerVerification;

        return $user->isLearner() && ! $user->isParentRegistration() && $case
            && (int) $case->user_id === (int) $user->id && $case->superseded_at === null
            && ($attempt->target_submission_round === $case->submission_round
                || ($attempt->flow_type === PersonaInquiry::FLOW_TEEN_SCHOOL
                    && $case->canReusePersonaSelfie()
                    && (int) $case->persona_evidence_attempt_id === (int) $attempt->id));
    }

    private function selfieUrl(PersonaInquiry $attempt, string $pose = 'center'): ?string
    {
        if (! $attempt->selfie_verification_id) {
            return null;
        }

        $response = $this->personaClient->retrieveSelfie($attempt->selfie_verification_id);
        $url = data_get($response, 'data.attributes.'.$pose.'-photo-url');

        return is_string($url) && $url !== '' ? $url : null;
    }

    private function governmentIdUrl(PersonaInquiry $attempt, string $page): ?string
    {
        if ($attempt->flow_type === PersonaInquiry::FLOW_TEEN_SCHOOL || ! $attempt->government_id_verification_id) {
            return null;
        }

        $response = $this->personaClient->retrieveGovernmentId($attempt->government_id_verification_id);
        $photoUrls = data_get($response, 'data.attributes.photo-urls', []);

        foreach (is_array($photoUrls) ? $photoUrls : [] as $photo) {
            if (! is_array($photo) || ($photo['page'] ?? null) !== $page) {
                continue;
            }

            $url = $photo['url'] ?? null;

            return is_string($url) && $url !== '' ? $url : null;
        }

        return null;
    }
}
