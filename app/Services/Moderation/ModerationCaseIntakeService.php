<?php

namespace App\Services\Moderation;

use App\Enums\ModerationCaseSource;
use App\Enums\ModerationCaseStatus;
use App\Models\ModerationCase;
use Illuminate\Support\Str;

class ModerationCaseIntakeService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function upsertFromSource(
        ModerationCaseSource $source,
        string $contentType,
        int $contentId,
        int $reportedUserId,
        ?int $reporterId = null,
        ModerationCaseStatus $status = ModerationCaseStatus::Reported,
        ?string $decision = null,
        ?string $severityLevel = null,
        array $metadata = [],
    ): ModerationCase {
        $moderationCase = ModerationCase::query()
            ->where('case_source', $source->value)
            ->where('content_type', $contentType)
            ->where('content_id', $contentId)
            ->first();

        $mergedMetadata = $this->mergeMetadata($moderationCase?->metadata, $metadata);

        $payload = [
            'reporter_id' => $reporterId ?? $moderationCase?->reporter_id,
            'reported_user_id' => $reportedUserId,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'case_source' => $source,
            'status' => $status,
            'severity_level' => $severityLevel ?? $moderationCase?->severity_level,
            'decision' => $decision ?? $moderationCase?->decision,
            'metadata' => $mergedMetadata,
        ];

        if ($moderationCase) {
            $moderationCase->forceFill($payload)->save();

            return $moderationCase->fresh();
        }

        return ModerationCase::query()->create([
            ...$payload,
            'case_reference_code' => $this->generateCaseReferenceCode(),
        ]);
    }

    /**
     * @param  mixed  $existingMetadata
     * @param  array<string, mixed>  $incomingMetadata
     * @return array<string, mixed>
     */
    private function mergeMetadata(mixed $existingMetadata, array $incomingMetadata): array
    {
        $existing = is_array($existingMetadata) ? $existingMetadata : [];

        return array_replace_recursive($existing, $incomingMetadata);
    }

    private function generateCaseReferenceCode(): string
    {
        do {
            $code = sprintf(
                'MC-%s-%s',
                now()->format('Ymd'),
                strtoupper(Str::random(6)),
            );
        } while (ModerationCase::query()->where('case_reference_code', $code)->exists());

        return $code;
    }
}
