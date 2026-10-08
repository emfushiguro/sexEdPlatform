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

class SequencingActivityHandler implements InteractiveActivityHandler
{
    public function type(): InteractiveActivityType
    {
        return InteractiveActivityType::SEQUENCING;
    }

    public function rules(string $prefix = 'configuration'): array
    {
        $prefix = $prefix === '' ? '' : "{$prefix}.";

        return [
            "{$prefix}schema_version" => ['sometimes', 'integer', 'in:1'],
            "{$prefix}items" => ['required', 'array', 'min:3', 'max:12'],
            "{$prefix}items.*.id" => ['nullable', 'string'],
            "{$prefix}items.*.kind" => ['required', 'in:text'],
            "{$prefix}items.*.value" => ['present', 'nullable', 'string', 'max:500'],
            "{$prefix}items.*.image_path" => ['nullable', 'string', 'max:2048'],
            "{$prefix}items.*.image_alt" => ['nullable', 'string', 'max:500'],
            "{$prefix}items.*.correct_position" => ['nullable', 'integer'],
        ];
    }

    public function normalize(array $configuration, ?array $existingConfiguration = null): array
    {
        $this->validate($configuration, $existingConfiguration);
        $existingIds = array_flip(array_filter(array_column($existingConfiguration['items'] ?? [], 'id'), 'is_string'));

        return [
            'schema_version' => 1,
            'items' => array_map(function (array $item, int $index) use ($existingIds): array {
                return [
                    ...$this->normalizeItem($item, isset($existingIds[$item['id'] ?? '']) ? $item['id'] : (string) Str::uuid()),
                    'correct_position' => $index + 1,
                ];
            }, $configuration['items'], array_keys($configuration['items'])),
        ];
    }

    public function initialWorkingState(array $configuration, Randomizer $randomizer): array
    {
        $canonical = array_map(static fn (array $item): string => $item['id'], $configuration['items']);
        $order = $randomizer->shuffleArray($canonical);

        if ($order === $canonical) {
            $order = [...array_slice($order, 1), $order[0]];
        }

        return ['item_order' => $order];
    }

    public function learnerPayload(array $configuration, array $workingState): array
    {
        $items = [];
        foreach ($configuration['items'] as $item) {
            $items[$item['id']] = $this->learnerItem($item);
        }

        return ['items' => array_values(array_filter(array_map(static fn (string $id): ?array => $items[$id] ?? null, $workingState['item_order'] ?? [])))];
    }

    public function evaluate(array $configuration, array $answer, array $workingState): array
    {
        $order = $answer['item_order'] ?? null;
        $canonical = array_map(static fn (array $item): string => $item['id'], $configuration['items']);

        if (! is_array($order) || count($order) !== count($canonical) || count(array_unique($order)) !== count($order) || array_diff($order, $canonical) !== []) {
            return $this->result(false, false, false, $workingState, 'invalid_answer');
        }

        $workingState['item_order'] = array_values($order);
        $correct = $workingState['item_order'] === $canonical;
        $positionResults = [];
        foreach ($workingState['item_order'] as $index => $itemId) {
            $isCorrect = $itemId === ($canonical[$index] ?? null);
            $positionResults[] = [
                'item_id' => $itemId,
                'position' => $index + 1,
                'is_correct' => $isCorrect,
            ];
        }

        return $this->result(true, $correct, $correct, $workingState, null, ['position_results' => $positionResults]);
    }

    public function answerFingerprint(array $configuration): string
    {
        $items = $configuration['items'] ?? [];
        usort($items, static fn (array $a, array $b): int => ($a['correct_position'] ?? 0) <=> ($b['correct_position'] ?? 0));
        $material = array_map(fn (array $item): string => $this->semanticValue($item), $items);

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
            $values = [];
            foreach ($configuration['items'] ?? [] as $index => $item) {
                $text = trim((string) ($item['value'] ?? ''));
                $path = trim((string) ($item['image_path'] ?? ''));
                if ($text === '' && $path === '') {
                    $validator->errors()->add("items.{$index}.value", 'An item requires text or an image.');
                }
                if ($path !== '' && trim((string) ($item['image_alt'] ?? '')) === '') {
                    $validator->errors()->add("items.{$index}.image_alt", 'An image requires alt text.');
                }
                $values[] = $this->duplicateValue($item);
            }
            if (count($values) !== count(array_unique($values))) {
                $validator->errors()->add('items', 'Duplicate item values are not allowed.');
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

    private function result(bool $accepted, bool $correct, bool $complete, array $workingState, ?string $rejectionReason = null, array $details = []): array
    {
        $result = ['accepted' => $accepted, 'is_correct' => $correct, 'is_complete' => $complete, 'working_state' => $workingState];

        if ($rejectionReason !== null) {
            $result['rejection_reason'] = $rejectionReason;
        }

        return [...$result, ...$details];
    }
}
