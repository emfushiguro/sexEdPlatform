<?php

namespace App\Console\Commands;

use App\Models\LearningPath;
use App\Models\Module;
use App\Models\Seminar;
use App\Support\LearnerAgePolicy;
use Illuminate\Console\Command;
use Throwable;

class AuditLegacyKidsContent extends Command
{
    protected $signature = 'content:audit-legacy-kids';

    protected $description = 'Report legacy Kids-targeted content without changing it';

    public function handle(): int
    {
        try {
            Module::query()->with('learnerCategories')->cursor()->each(function (Module $module): void {
                $this->report('modules', $module->id, $module->rawLearnerCategoriesForLegacyAudit(), $module->title.' '.$module->description);
            });

            LearningPath::query()->cursor()->each(function (LearningPath $path): void {
                $this->report('learning_paths', $path->id, $path->rawLearnerCategoriesForLegacyAudit(), $path->title.' '.$path->description);
            });

            Seminar::query()->cursor()->each(function (Seminar $seminar): void {
                $this->report('seminars', $seminar->id, (array) ($seminar->learner_age_categories ?? []), $seminar->title.' '.$seminar->description);
            });
        } catch (Throwable $exception) {
            $this->error('Legacy content audit failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @param array<int, mixed> $rawCategories */
    private function report(string $model, int|string $id, array $rawCategories, string $searchText): void
    {
        $raw = collect($rawCategories)
            ->filter(fn (mixed $category): bool => is_string($category))
            ->map(fn (string $category): string => strtolower(trim($category)))
            ->values();
        $active = $raw
            ->map(fn (string $category): ?string => LearnerAgePolicy::normalizeActiveCategory($category))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $hasKids = $raw->contains(fn (string $category): bool => in_array($category, ['kid', 'kids', 'child', 'children'], true));
        $reasons = [];

        if ($hasKids) {
            $reasons[] = $active === [] ? 'kids_only' : 'mixed_with_kids';
        }
        if (preg_match('/\b(?:kid|kids)\b/i', $searchText) === 1) {
            $reasons[] = 'kids_wording';
        }

        foreach (array_unique($reasons) as $reason) {
            $this->line(sprintf(
                '%s | %s | %s | remaining=%s',
                $model,
                $id,
                $reason,
                $active === [] ? 'none' : implode(',', $active),
            ));
        }
    }
}
