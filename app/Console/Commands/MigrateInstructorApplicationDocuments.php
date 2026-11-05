<?php

namespace App\Console\Commands;

use App\Models\InstructorApplication;
use App\Services\InstructorApplication\InstructorApplicationDocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MigrateInstructorApplicationDocuments extends Command
{
    protected $signature = 'instructor-applications:migrate-documents
        {--commit : Copy verified files and update rows}
        {--application-id= : Limit the audit to one application}
        {--chunk=100 : Number of applications to inspect per batch}
        {--delete-verified-source : Explicitly delete a verified public source after a committed copy}';

    protected $description = 'Audit or migrate legacy instructor application documents to private storage.';

    public function handle(InstructorApplicationDocumentStorage $documents): int
    {
        if (! Schema::hasColumn('instructor_applications', 'document_disk')) {
            $this->error('The instructor application document migration is not applied to this database.');

            return self::FAILURE;
        }

        if ($this->option('delete-verified-source') && ! $this->option('commit')) {
            $this->error('--delete-verified-source requires --commit and is a separate operator decision.');

            return self::FAILURE;
        }

        $query = InstructorApplication::query()
            ->where('document_disk', 'public')
            ->when($this->option('application-id'), fn ($builder, $id) => $builder->whereKey((int) $id));
        $counts = ['applications' => 0, 'files' => 0, 'missing' => 0, 'mismatched' => 0, 'copied' => 0];

        $query->chunkById(max(1, (int) $this->option('chunk')), function ($applications) use (&$counts, $documents): void {
            foreach ($applications as $application) {
                $counts['applications']++;
                $updates = [];
                $sourcesToDelete = [];
                foreach (['government_id', 'clearance', 'cv_resume', 'teaching_credential', 'sexed_certificate', 'professional_license'] as $slot) {
                    $column = $documents->columnFor($slot);
                    $path = $application->{$column};
                    if (! is_string($path) || $path === '') {
                        continue;
                    }
                    $counts['files']++;
                    $source = Storage::disk('public');
                    if (! $source->exists($path)) {
                        $counts['missing']++;
                        continue;
                    }
                    $contents = $source->get($path);
                    $targetPath = 'instructor-applications/'.$application->user_id.'/legacy-'.$application->id.'-'.$slot.'-'.basename($path);
                    $target = Storage::disk('local');
                    if ($target->exists($targetPath) && hash('sha256', (string) $target->get($targetPath)) !== hash('sha256', (string) $contents)) {
                        $counts['mismatched']++;
                        continue;
                    }
                    if (! $target->exists($targetPath)) {
                        if (! $this->option('commit')) {
                            $this->line("Would copy application {$application->id} {$slot}");
                            continue;
                        }
                        $target->put($targetPath, $contents);
                        $counts['copied']++;
                    }
                    $metadata = $application->application_metadata ?: [];
                    $metadata['documents'][$slot] = array_merge($metadata['documents'][$slot] ?? [], [
                        'path' => $targetPath,
                        'disk' => 'local',
                    ]);
                    $updates['application_metadata'] = $metadata;
                    if ($this->option('delete-verified-source')) {
                        $sourcesToDelete[] = $path;
                    }
                }
                if ($this->option('commit') && $updates !== []) {
                    DB::transaction(function () use ($application, $updates): void {
                        $application->update(array_merge($updates, ['document_disk' => 'local']));
                    });
                    if ($this->option('delete-verified-source')) {
                        foreach ($sourcesToDelete as $sourcePath) {
                            Storage::disk('public')->delete($sourcePath);
                        }
                    }
                }
            }
        });

        foreach ($counts as $key => $value) {
            $this->line($key.': '.$value);
        }

        return self::SUCCESS;
    }
}
