<?php

namespace App\Support;

use App\Models\InstructorIdentityVerification;
use App\Models\InstructorApplication;
use App\Models\User;

class NotificationDeepLinkResolver
{
    public function __construct(
        private readonly NotificationPayloadNormalizer $payloadNormalizer,
    ) {}

    public function resolve(array $payload, string $fallbackUrl, ?User $recipient = null): string
    {
        $type = (string) ($payload['type'] ?? '');

        $adminTarget = match ($type) {
            'learner_identity_submitted' => ['verification_id', 'admin.parent-verifications.learners.show'],
            'parent_verification_request_submitted' => ['parent_user_id', 'admin.parent-verifications.parents.show'],
            'child_verification_request_submitted' => ['parent_child_account_id', 'admin.parent-verifications.index'],
            'guardian_relationship_verification_submitted' => ['parent_child_account_id', 'admin.parent-verifications.relationships.show'],
            'instructor_identity_submitted' => ['instructor_identity_verification_id', 'admin.instructor-applications.identities.show'],
            'instructor_application_submitted' => ['application_id', 'admin.instructor-applications.review'],
            default => null,
        };
        if ($recipient?->isAdmin() && $adminTarget !== null) {
            if ($type === 'instructor_identity_submitted' && ! $recipient->checkPermissionTo('review instructor identities')) {
                return $fallbackUrl;
            }
            if ($type === 'instructor_application_submitted' && ! $recipient->checkPermissionTo('review instructor applications')) {
                return $fallbackUrl;
            }

            [$key, $route] = $adminTarget;
            $id = filter_var($payload[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                return $fallbackUrl;
            }

            if ($type === 'instructor_identity_submitted'
                && ! InstructorIdentityVerification::query()->current()->whereKey($id)->exists()) {
                return $fallbackUrl;
            }
            if ($type === 'instructor_application_submitted'
                && ! InstructorApplication::query()->whereKey($id)->exists()) {
                return $fallbackUrl;
            }

            $parameters = $type === 'child_verification_request_submitted'
                ? ['type' => 'children', 'status' => 'pending', 'focus' => $id]
                : [$id];

            return route($route, $parameters, false);
        }

        if ($recipient !== null && str_starts_with($type, 'instructor_identity_')) {
            $id = filter_var($payload['instructor_identity_verification_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($id === false
                || $recipient->isAdmin()
                || $recipient->isParentRegistration()
                || ! InstructorIdentityVerification::query()
                    ->current()
                    ->whereKey($id)
                    ->where('user_id', $recipient->id)
                    ->exists()) {
                return $fallbackUrl;
            }

            return route('instructor-application.dashboard', [], false);
        }

        if ($recipient?->isParentRegistration()) {
            if (in_array($type, ['parent_verification_submitted', 'parent_verification_rejected'], true)) {
                return route('guardian.verification.status', [], false);
            }
            if (in_array($type, ['child_verification_approved', 'child_verification_rejected'], true)) {
                return route('parent.children.index', [], false);
            }
        }

        if (in_array($type, ['learner_identity_submitted', 'learner_identity_approved', 'learner_identity_rejected'], true)
            && $recipient !== null && ! $recipient->isParentRegistration() && $recipient->isLearner()) {
            return route('learner.identity.status', [], false);
        }

        if ($recipient !== null && str_starts_with($type, 'guardian_relationship_verification_')) {
            $relationshipId = filter_var($payload['parent_child_account_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($relationshipId === false) {
                return $fallbackUrl;
            }

            if ((int) $recipient->id === (int) ($payload['child_user_id'] ?? 0)) {
                return route('learner.parent.index', [], false);
            }

            return $recipient->isParentRegistration()
                ? route('parent.relationship-verifications.show', $relationshipId, false)
                : $fallbackUrl;
        }

        $candidate = $this->payloadNormalizer->resolveActionUrl($payload);

        if ($candidate !== null && $this->isSafeInternalTarget($candidate)) {
            return $candidate;
        }

        // Signed approval URLs include the original host; use the guarded continuation instead of rewriting them.
        if ($type === 'parent_verification_approved' && $recipient?->isParentRegistration()) {
            return route('guardian.onboarding.show', [], false);
        }

        return $fallbackUrl;
    }

    private function isSafeInternalTarget(string $target): bool
    {
        if (preg_match('/[\\\\\x00-\x20\x7f]/', $target)) {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return ! str_starts_with($target, '//');
        }

        if (! filter_var($target, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($target, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        $targetHost = parse_url($target, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($targetHost) || ! is_string($appHost)) {
            return false;
        }

        return strcasecmp($targetHost, $appHost) === 0;
    }
}
