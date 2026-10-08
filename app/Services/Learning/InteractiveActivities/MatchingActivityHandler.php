<?php

declare(strict_types=1);

namespace App\Services\Learning\InteractiveActivities;

use App\Contracts\Learning\InteractiveActivityHandler;
use App\Enums\InteractiveActivityType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Random\Randomizer;

class MatchingActivityHandler implements InteractiveActivityHandler
{
    public function type(): InteractiveActivityType
    {
        return InteractiveActivityType::MATCHING;
    }

    public function rules(string $prefix = 'configuration'): array
    {
        $prefix = $prefix === '' ? '' : "{$prefix}.";

        return [
            "{$prefix}schema_version" => ['sometimes', 'integer', 'in:1'],
            "{$prefix}pairs" => ['required', 'array', 'min:2', 'max:12'],
            "{$prefix}pairs.*.id" => ['nullable', 'string'],
            "{$prefix}pairs.*.left.id" => ['nullable', 'string'],
            "{$prefix}pairs.*.left.kind" => ['required', 'in:text'],
            "{$prefix}pairs.*.left.value" => ['present', 'nullable', 'string', 'max:500'],
            "{$prefix}pairs.*.left.image_path" => ['nullable', 'string', 'max:2048'],
            "{$prefix}pairs.*.left.image_alt" => ['nullable', 'string', 'max:500'],
            "{$prefix}pairs.*.right.id" => ['nullable', 'string'],
            "{$prefix}pairs.*.right.kind" => ['required', 'in:text'],
            "{$prefix}pairs.*.right.value" => ['present', 'nullable', 'string', 'max:500'],
            "{$prefix}pairs.*.right.image_path" => ['nullable', 'string', 'max:2048'],
            "{$prefix}pairs.*.right.image_alt" => ['nullable', 'string', 'max:500'],
        ];
    }

    public function normalize(array $configuration, ?array $existingConfiguration = null): array
    {
        $this->validate($configuration, $existingConfiguration);
        $existingPairIds = array_flip(array_filter(array_column($existingConfiguration['pairs'] ?? [], 'id'), 'is_string'));
        $existingLeftIds = array_flip(array_filter(array_map(static fn (array $pair): mixed => $pair['left']['id'] ?? null, $existingConfiguration['pairs'] ?? []), 'is_string'));
        $existingRightIds = array_flip(array_filter(array_map(static fn (array $pair): mixed => $pair['right']['id'] ?? null, $existingConfiguration['pairs'] ?? []), 'is_string'));

        return [
            'schema_version' => 1,
            'pairs' => array_map(function (array $pair) use ($existingPairIds, $existingLeftIds, $existingRightIds): array {
                $left = $pair['left'];
                $right = $pair['right'];

                return [
                    'id' => isset($existingPairIds[$pair['id'] ?? '']) ? $pair['id'] : (string) Str::uuid(),
                    'left' => $this->normalizeItem($left, isset($existingLeftIds[$left['id'] ?? '']) ? $left['id'] : (string) Str::uuid()),
                    'right' => $this->normalizeItem($right, isset($existingRightIds[$right['id'] ?? '']) ? $right['id'] : (string) Str::uuid()),
                ];
            }, $configuration['pairs']),
        ];
    }

    public function initialWorkingState(array $configuration, Randomizer $randomizer): array
    {
        $canonical = array_map(static fn (array $pair): string => $pair['right']['id'], $configuration['pairs']);
        $order = $randomizer->shuffleArray($canonical);

        if ($order === $canonical) {
            $order = count($order) === 2 ? array_reverse($order) : [...array_slice($order, 1), $order[0]];
        }

        return ['right_order' => $order, 'matched' => []];
    }

    public function learnerPayload(array $configuration, array $workingState): array
    {
        $rightById = [];
        $left = [];
        foreach ($configuration['pairs'] as $pair) {
            $left[] = $this->learnerItem($pair['left']);
            $rightById[$pair['right']['id']] = $this->learnerItem($pair['right']);
        }
        $matches = $workingState['matched'] ?? [];

        return [
            'left_items' => $left,
            'right_items' => array_values(array_filter(array_map(static fn (string $id): ?array => $rightById[$id] ?? null, $workingState['right_order'] ?? []))),
            'completed_left_item_ids' => $this->completedItemIds($matches, 'left_id'),
            'completed_right_item_ids' => $this->completedItemIds($matches, 'right_id'),
            'completed_matches' => $this->completedMatches($matches),
        ];
    }

    public function evaluate(array $configuration, array $answer, array $workingState): array
    {
        if (array_key_exists('connections', $answer)) {
            return $this->evaluateConnections($configuration, $answer['connections'], $workingState);
        }

        $leftId = $answer['left_id'] ?? null;
        $rightId = $answer['right_id'] ?? null;
        $pairs = $workingState['matched'] ?? [];
        $mapping = [];

        foreach ($configuration['pairs'] as $pair) {
            $mapping[$pair['left']['id']] = $pair['right']['id'];
        }

        if (! is_string($leftId) || ! is_string($rightId) || ! isset($mapping[$leftId]) || ! in_array($rightId, $mapping, true) || $this->containsId($pairs, 'left_id', $leftId) || $this->containsId($pairs, 'right_id', $rightId)) {
            return $this->result(false, false, false, $workingState, 'invalid_answer');
        }

        $correct = $mapping[$leftId] === $rightId;
        if ($correct) {
            $pairs[] = ['left_id' => $leftId, 'right_id' => $rightId];
            $workingState['matched'] = $pairs;
        }

        return $this->result(true, $correct, $correct && count($pairs) === count($mapping), $workingState);
    }

    private function evaluateConnections(array $configuration, mixed $connections, array $workingState): array
    {
        if (! is_array($connections) || $connections !== array_values($connections)) {
            return $this->result(false, false, false, $workingState, 'invalid_answer');
        }

        $mapping = [];
        foreach ($configuration['pairs'] as $pair) {
            $mapping[$pair['left']['id']] = $pair['right']['id'];
        }

        $seenLeft = [];
        $seenRight = [];
        $connectionsByLeft = [];
        foreach ($connections as $connection) {
            $leftId = is_array($connection) ? ($connection['left_id'] ?? null) : null;
            $rightId = is_array($connection) ? ($connection['right_id'] ?? null) : null;

            if (! is_string($leftId)
                || ! is_string($rightId)
                || ! isset($mapping[$leftId])
                || ! in_array($rightId, $mapping, true)
                || isset($seenLeft[$leftId])
                || isset($seenRight[$rightId])) {
                return $this->result(false, false, false, $workingState, 'invalid_answer');
            }

            $seenLeft[$leftId] = true;
            $seenRight[$rightId] = true;
            $connectionsByLeft[$leftId] = $rightId;
        }

        $pairResults = array_map(static function (array $pair) use ($connectionsByLeft, $mapping): array {
            $leftId = $pair['left']['id'];
            $rightId = $connectionsByLeft[$leftId] ?? null;
            if ($rightId === null) {
                return ['left_id' => $leftId, 'right_id' => null, 'is_correct' => null, 'state' => 'unanswered'];
            }

            $correct = $mapping[$leftId] === $rightId;

            return [
                'left_id' => $leftId,
                'right_id' => $rightId,
                'is_correct' => $correct,
                'state' => $correct ? 'correct' : 'incorrect',
            ];
        }, $configuration['pairs']);

        $matched = [];
        $matchedLeft = [];
        $matchedRight = [];
        foreach ($this->completedMatches($workingState['matched'] ?? []) as $match) {
            if (($mapping[$match['left_id']] ?? null) !== $match['right_id']
                || isset($matchedLeft[$match['left_id']])
                || isset($matchedRight[$match['right_id']])) {
                continue;
            }

            $matched[] = $match;
            $matchedLeft[$match['left_id']] = true;
            $matchedRight[$match['right_id']] = true;
        }

        foreach ($pairResults as $result) {
            if (! $result['is_correct'] || isset($matchedLeft[$result['left_id']]) || isset($matchedRight[$result['right_id']])) {
                continue;
            }

            $matched[] = [
                'left_id' => $result['left_id'],
                'right_id' => $result['right_id'],
            ];
            $matchedLeft[$result['left_id']] = true;
            $matchedRight[$result['right_id']] = true;
        }

        $workingState['matched'] = $matched;
        $complete = count($matched) === count($mapping);
        $correct = $complete
            && count(array_filter($pairResults, static fn (array $result): bool => $result['state'] !== 'correct')) === 0;

        return $this->result(true, $correct, $complete, $workingState, null, ['pair_results' => $pairResults]);
    }

    public function answerFingerprint(array $configuration): string
    {
        $material = array_map(fn (array $pair): array => [$this->semanticValue($pair['left']), $this->semanticValue($pair['right'])], $configuration['pairs'] ?? []);
        usort($material, static fn (array $a, array $b): int => $a <=> $b);

        return hash('sha256', json_encode($material, JSON_THROW_ON_ERROR));
    }

    public function previewPayload(array $configuration, array $workingState): array
    {
        return $this->learnerPayload($configuration, $workingState);
    }

    private function validate(array $configuration, ?array $existingConfiguration): void
    {
        if ($existingConfiguration !== null && array_key_exists('schema_version', $existingConfiguration) && $existingConfiguration['schema_version'] !== 1) {
            throw ValidationException::withMessages(['configuration.schema_version' => 'Unsupported schema version.']);
        }

        $validator = Validator::make($configuration, $this->rules(''));
        $validator->after(function ($validator) use ($configuration): void {
            foreach (['left', 'right'] as $side) {
                $values = [];
                foreach ($configuration['pairs'] ?? [] as $index => $pair) {
                    $item = $pair[$side] ?? [];
                    $text = trim((string) ($item['value'] ?? ''));
                    $path = trim((string) ($item['image_path'] ?? ''));
                    if ($text === '' && $path === '') {
                        $validator->errors()->add("pairs.{$index}.{$side}.value", 'An item requires text or an image.');
                    }
                    if ($path !== '' && trim((string) ($item['image_alt'] ?? '')) === '') {
                        $validator->errors()->add("pairs.{$index}.{$side}.image_alt", 'An image requires alt text.');
                    }
                    $values[] = $this->duplicateValue($item);
                }
                if (count($values) !== count(array_unique($values))) {
                    $validator->errors()->add("pairs.{$side}", "Duplicate {$side} values are not allowed.");
                }
            }
        });
        $validator->validate();
    }

    private function comparisonValue(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }

    private function normalizeItem(array $item, string $id): array
    {
        $path = trim((string) ($item['image_path'] ?? ''));

        return [
            'id' => $id,
            'kind' => 'text',
            'value' => trim((string) ($item['value'] ?? '')),
            'image_path' => $path !== '' ? $path : null,
            'image_alt' => $path !== '' ? trim((string) ($item['image_alt'] ?? '')) : null,
        ];
    }

    private function learnerItem(array $item): array
    {
        return [
            'id' => $item['id'],
            'kind' => $item['kind'],
            'value' => $item['value'],
            'image_url' => $item['image_path'] ? Storage::disk('public')->url($item['image_path']) : null,
            'image_alt' => $item['image_alt'],
        ];
    }

    private function duplicateValue(array $item): string
    {
        $text = $this->comparisonValue((string) ($item['value'] ?? ''));

        return $text !== '' ? "text:{$text}" : 'image:'.trim((string) ($item['image_path'] ?? ''));
    }

    private function semanticValue(array $item): string
    {
        $text = $this->comparisonValue((string) ($item['value'] ?? ''));
        if ($text !== '') {
            return "text:{$text}";
        }

        return 'image:'.trim((string) ($item['image_path'] ?? '')).'|'.$this->comparisonValue((string) ($item['image_alt'] ?? ''));
    }

    private function containsId(array $pairs, string $key, string $id): bool
    {
        return in_array($id, array_column($pairs, $key), true);
    }

    private function completedItemIds(array $matches, string $key): array
    {
        $ids = array_values(array_unique(array_filter(array_column($matches, $key), 'is_string')));
        sort($ids, SORT_STRING);

        return $ids;
    }

    /** @return list<array{left_id: string, right_id: string}> */
    private function completedMatches(array $matches): array
    {
        return array_values(array_filter(array_map(
            static fn ($match): ?array => is_array($match)
                && is_string($match['left_id'] ?? null)
                && is_string($match['right_id'] ?? null)
                    ? ['left_id' => $match['left_id'], 'right_id' => $match['right_id']]
                    : null,
            $matches,
        )));
    }

    private function result(bool $accepted, bool $correct, bool $complete, array $workingState, ?string $rejectionReason = null, array $details = []): array
    {
        $result = ['accepted' => $accepted, 'is_correct' => $correct, 'is_complete' => $complete, 'working_state' => $workingState];

        if ($rejectionReason !== null) {
            $result['rejection_reason'] = $rejectionReason;
        }

        return [...$result, ...$details];
    }
}
