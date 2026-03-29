<?php

namespace App\Console\Commands;

use App\Models\InstructorApplication;
use App\Models\InstructorProfile;
use Illuminate\Console\Command;

class BackfillInstructorProfileFromApplications extends Command
{
    protected $signature = 'instructor-profile:backfill-from-applications';

    protected $description = 'Backfill null instructor profile educational/professional fields from latest approved application';

    public function handle(): int
    {
        $updated = 0;

        InstructorProfile::query()
            ->where(function ($query) {
                $query->whereNull('educational_background')
                    ->orWhereNull('professional_background');
            })
            ->chunkById(100, function ($profiles) use (&$updated): void {
                foreach ($profiles as $profile) {
                    $latestApproved = InstructorApplication::query()
                        ->where('user_id', $profile->user_id)
                        ->where('status', 'approved')
                        ->orderByDesc('approved_at')
                        ->orderByDesc('id')
                        ->first();

                    if (!$latestApproved) {
                        continue;
                    }

                    $payload = [];

                    if ($profile->educational_background === null && !empty($latestApproved->educational_background)) {
                        $payload['educational_background'] = $latestApproved->educational_background;
                    }

                    if ($profile->professional_background === null && !empty($latestApproved->bio)) {
                        $payload['professional_background'] = $latestApproved->bio;
                    }

                    if ($payload !== []) {
                        $profile->update($payload);
                        $updated++;
                    }
                }
            });

        $this->info("Backfill complete. Updated profiles: {$updated}");

        return self::SUCCESS;
    }
}
