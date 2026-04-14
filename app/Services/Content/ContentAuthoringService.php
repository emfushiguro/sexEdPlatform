<?php

namespace App\Services\Content;

use App\Models\Module;

class ContentAuthoringService
{
    /**
     * Build normalized module payload from form data.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function toInstructorDraftPayload(array $validated, int $authorId, ?Module $existing = null): array
    {
        $payload = $this->normalizeCommonPayload($validated, $existing);

        return $payload + [
            'created_by' => $authorId,
            'content_owner_type' => 'instructor',
            'current_review_status' => 'draft',
            'is_published' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function toAdminPayload(array $validated, ?Module $existing = null): array
    {
        $payload = $this->normalizeCommonPayload($validated, $existing);

        return $payload + [
            'is_premium' => false,
            'price_amount' => null,
            'price_currency' => 'PHP',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeCommonPayload(array $validated, ?Module $existing = null): array
    {
        $ageBracket = (string) ($validated['age_bracket'] ?? 'teens');
        [$minAge, $maxAge] = $this->ageRangeForBracket($ageBracket);

        $accessType = (string) ($validated['access_type'] ?? ($existing?->access_type ?? 'free'));
        $priceCurrency = strtoupper((string) ($validated['price_currency'] ?? ($existing?->price_currency ?? 'PHP')));
        $priceAmount = $accessType === 'paid'
            ? ($validated['price_amount'] ?? $existing?->price_amount)
            : null;

        $order = (int) ($validated['order'] ?? ($existing?->order ?? ((Module::query()->max('order') ?? 0) + 1)));

        return [
            'title' => (string) $validated['title'],
            'description' => (string) $validated['description'],
            'min_age' => $minAge,
            'max_age' => $maxAge,
            'enrollment_mode' => (string) ($validated['enrollment_mode'] ?? ($existing?->enrollment_mode ?? 'auto')),
            'enrollment_limit' => $validated['enrollment_limit'] ?? $existing?->enrollment_limit,
            'access_type' => $accessType,
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency,
            'is_premium' => $accessType === 'paid',
            'duration_minutes' => (int) ($validated['duration_minutes'] ?? ($existing?->duration_minutes ?? 0)),
            'order' => $order,
            'certificate_pass_score' => (int) ($validated['certificate_pass_score'] ?? ($existing?->certificate_pass_score ?? 70)),
        ];
    }

    /**
     * @return array{0:int,1:int}
     */
    private function ageRangeForBracket(string $ageBracket): array
    {
        return match ($ageBracket) {
            'kids' => [5, 12],
            'adults' => [18, 100],
            default => [13, 17],
        };
    }
}
