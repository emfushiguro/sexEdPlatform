<?php

namespace App\Console\Commands;

use App\Services\Moderation\Backfill\CentralizedModerationBackfillService;
use Illuminate\Console\Command;

class BackfillCentralizedModeration extends Command
{
    protected $signature = 'moderation:backfill-centralized
                            {--reconcile-only : Run parity reconciliation without writing centralized records}';

    protected $description = 'Backfill legacy moderation artifacts into centralized moderation cases and report parity.';

    public function handle(CentralizedModerationBackfillService $backfillService): int
    {
        $this->info('Centralized moderation backfill started.');

        if (!$this->option('reconcile-only')) {
            $processed = $backfillService->backfill();

            $this->line('Backfill processed counts:');
            $this->table(
                ['Source', 'Processed'],
                [
                    ['module_review', (string) $processed['module_review']],
                    ['chat_report', (string) $processed['chat_report']],
                    ['learner_report', (string) $processed['learner_report']],
                    ['instructor_application', (string) $processed['instructor_application']],
                    ['skipped', (string) $processed['skipped']],
                    ['total', (string) $processed['total']],
                ],
            );
        }

        $reconciliation = $backfillService->reconcileParity();

        $this->line('Parity reconciliation summary:');
        $this->table(
            ['Source', 'Legacy', 'Centralized', 'Delta'],
            array_map(
                fn (array $row): array => [
                    (string) $row['source'],
                    (string) $row['legacy_count'],
                    (string) $row['centralized_count'],
                    (string) $row['delta'],
                ],
                $reconciliation['sources'],
            ),
        );

        if (!$reconciliation['matched']) {
            $this->warn('Parity mismatches detected. Review deltas before rollout cutover.');
        } else {
            $this->info('Parity check passed with no mismatches.');
        }

        return self::SUCCESS;
    }
}
