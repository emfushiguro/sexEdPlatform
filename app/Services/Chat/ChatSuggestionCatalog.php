<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Support\LearnerAgePolicy;

class ChatSuggestionCatalog
{
    public function forUser(User $user): array
    {
        if (! $user->isLearner()) {
            return [];
        }

        $audience = $this->resolveAudience($user);
        if ($audience === null) {
            return [];
        }

        return collect(config('chat_suggestions.suggestions', []))
            ->filter(function (array $suggestion) use ($audience): bool {
                if (($suggestion['active'] ?? false) !== true) {
                    return false;
                }

                $allowedAudiences = array_values((array) ($suggestion['audience'] ?? []));

                if ($audience !== null) {
                    return in_array($audience, $allowedAudiences, true);
                }

                return false;
            })
            ->map(fn (array $suggestion): array => $this->normalize($suggestion))
            ->sortBy(fn (array $suggestion): string => sprintf(
                '%010d:%s',
                (int) $suggestion['display_order'],
                $suggestion['key'],
            ))
            ->values()
            ->all();
    }

    public function select(array $suggestions, string $context, int $limit, array $excludedKeys = []): array
    {
        if ($limit < 1) {
            return [];
        }

        $excluded = array_fill_keys(array_map('strval', $excludedKeys), true);

        return collect($suggestions)
            ->filter(function (array $suggestion) use ($excluded): bool {
                return ! isset($excluded[(string) ($suggestion['key'] ?? '')])
                    && ($suggestion['active'] ?? false) === true;
            })
            ->map(function (array $suggestion) use ($context): array {
                $contexts = array_values((array) ($suggestion['context'] ?? []));
                $priority = in_array($context, $contexts, true)
                    ? 0
                    : (in_array('general', $contexts, true) ? 1 : 2);

                return [
                    ...$this->normalize($suggestion),
                    '_context_priority' => $priority,
                ];
            })
            ->sortBy(fn (array $suggestion): string => sprintf(
                '%d:%010d:%s',
                $suggestion['_context_priority'],
                (int) $suggestion['display_order'],
                $suggestion['key'],
            ))
            ->unique('key')
            ->unique('text')
            ->take($limit)
            ->map(function (array $suggestion): array {
                unset($suggestion['_context_priority']);

                return $suggestion;
            })
            ->values()
            ->all();
    }

    private function resolveAudience(User $user): ?string
    {
        return LearnerAgePolicy::category($user->birthdate);
    }

    private function normalize(array $suggestion): array
    {
        return [
            'key' => (string) ($suggestion['key'] ?? ''),
            'text' => (string) ($suggestion['text'] ?? ''),
            'category' => (string) ($suggestion['category'] ?? 'general'),
            'audience' => array_values((array) ($suggestion['audience'] ?? [])),
            'context' => array_values((array) ($suggestion['context'] ?? ['general'])),
            'active' => (bool) ($suggestion['active'] ?? false),
            'display_order' => (int) ($suggestion['display_order'] ?? 0),
        ];
    }
}
