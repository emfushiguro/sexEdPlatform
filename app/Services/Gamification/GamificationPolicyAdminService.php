<?php

namespace App\Services\Gamification;

use App\Models\GamificationPolicy;
use App\Models\GamificationPolicyVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GamificationPolicyAdminService
{
    public function __construct(
        private readonly GamificationPolicyNormalizer $normalizer,
        private readonly GamificationPolicyValidator $validator,
        private readonly GamificationPolicyResolver $resolver,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function updatePolicy(
        array $payload,
        ?int $adminId = null,
        ?string $changeSummary = null,
        ?string $versionLabel = null,
    ): GamificationPolicy {
        $normalizedPayload = $this->normalizer->normalize($payload);
        $errors = $this->validator->validate($normalizedPayload);

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        /** @var GamificationPolicy $newActivePolicy */
        $newActivePolicy = DB::transaction(function () use ($normalizedPayload, $adminId, $changeSummary, $versionLabel) {
            GamificationPolicy::query()->active()->update(['is_active' => false]);

            $newPolicy = GamificationPolicy::query()->create([
                'is_active' => true,
                'policy_payload' => $normalizedPayload,
                'version_label' => $versionLabel,
                'change_summary' => $changeSummary,
                'updated_by' => $adminId,
            ]);

            $newPolicy->versions()->create([
                'policy_payload' => $normalizedPayload,
                'version_label' => $versionLabel,
                'change_summary' => $changeSummary,
                'changed_by' => $adminId,
            ]);

            return $newPolicy;
        });

        $this->resolver->clearCache();

        return $newActivePolicy;
    }

    /**
     * @throws ValidationException
     */
    public function restoreVersion(
        int $versionId,
        ?int $adminId = null,
        ?string $changeSummary = null,
    ): GamificationPolicy {
        $version = GamificationPolicyVersion::query()->findOrFail($versionId);

        return $this->updatePolicy(
            payload: (array) $version->policy_payload,
            adminId: $adminId,
            changeSummary: $changeSummary ?? 'Restored policy from historical version #' . $version->id,
            versionLabel: $version->version_label,
        );
    }
}
