<?php

namespace App\Console\Commands;

use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MoveLegacyIdentityDocumentsToPrivateStorage extends Command
{
    protected $signature = 'identity:move-legacy-documents {--apply : Copy verified files to private storage and remove public originals}';

    protected $description = 'Move legacy identity verification documents from public to private storage';

    private const ALLOWED_PREFIXES = [
        'registration-temp/',
        'parent-verifications/',
        'guardian-verifications/',
        'child-verifications/',
    ];

    public function handle(): int
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $unsafe = [];
        $paths = [];

        foreach ($public->allFiles('registration-temp') as $path) {
            $paths[] = $path;
        }

        foreach (User::query()->get(['parent_id_document_path', 'parent_id_document_back_path']) as $user) {
            $paths[] = $user->parent_id_document_path;
            $paths[] = $user->parent_id_document_back_path;
        }

        $paths = array_merge(
            $paths,
            ParentChildAccount::query()->whereNotNull('verification_document_path')->pluck('verification_document_path')->all(),
            GuardianRelationshipVerificationDocument::query()->pluck('path')->all(),
        );

        $candidates = [];
        foreach (array_unique(array_filter($paths, fn (mixed $path): bool => is_string($path) && $path !== '')) as $path) {
            if (! $this->isAllowedPath($path)) {
                $unsafe[] = $path;
                continue;
            }

            $candidates[] = $path;
        }

        sort($candidates);

        $apply = (bool) $this->option('apply');
        $ready = 0;
        $moved = 0;
        $missing = [];
        $conflicts = [];
        $failures = [];

        foreach ($candidates as $path) {
            $publicExists = $public->exists($path);
            $localExists = $local->exists($path);

            if (! $publicExists) {
                if (! $localExists) {
                    $missing[] = $path;
                }
                continue;
            }

            $publicHash = $this->sha256($public->path($path));
            if ($publicHash === null) {
                $failures[] = $path;
                continue;
            }

            if ($localExists) {
                $localHash = $this->sha256($local->path($path));
                if ($localHash === null) {
                    $failures[] = $path;
                    continue;
                }
                if (! hash_equals($publicHash, $localHash)) {
                    $conflicts[] = $path;
                    continue;
                }
            }

            $ready++;
            if (! $apply) {
                continue;
            }

            if (! $localExists && ! $this->copyAndVerify($path, $publicHash)) {
                $failures[] = $path;
                continue;
            }

            if (! $public->delete($path)) {
                $failures[] = $path;
                continue;
            }

            $moved++;
        }

        $this->line(sprintf(
            'identity document migration: candidates=%d ready=%d moved=%d missing=%d conflicts=%d unsafe=%d failures=%d mode=%s',
            count($candidates),
            $ready,
            $moved,
            count($missing),
            count($conflicts),
            count($unsafe),
            count($failures),
            $apply ? 'apply' : 'dry-run',
        ));

        foreach ([
            'missing' => $missing,
            'conflicts' => $conflicts,
            'unsafe' => $unsafe,
            'failures' => $failures,
        ] as $label => $reportedPaths) {
            foreach ($reportedPaths as $path) {
                $this->warn($label.': '.$path);
            }
        }

        return self::SUCCESS;
    }

    private function copyAndVerify(string $path, string $expectedHash): bool
    {
        $public = Storage::disk('public');
        $local = Storage::disk('local');
        $stream = $public->readStream($path);
        if (! is_resource($stream)) {
            return false;
        }

        try {
            if (! $local->writeStream($path, $stream)) {
                return false;
            }
        } catch (Throwable) {
            return false;
        } finally {
            fclose($stream);
        }

        $publicHash = $this->sha256($public->path($path));
        $localHash = $this->sha256($local->path($path));

        return $publicHash !== null
            && $localHash !== null
            && hash_equals($expectedHash, $publicHash)
            && hash_equals($publicHash, $localHash);
    }

    private function sha256(string $path): ?string
    {
        $hash = @hash_file('sha256', $path);

        return is_string($hash) ? $hash : null;
    }

    private function isAllowedPath(string $path): bool
    {
        if ($path === ''
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || str_contains($path, "\0")
            || preg_match('/^[a-zA-Z]:/', $path) === 1) {
            return false;
        }

        $segments = explode('/', $path);
        if (in_array('', $segments, true) || in_array('.', $segments, true) || in_array('..', $segments, true)) {
            return false;
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix) && strlen($path) > strlen($prefix)) {
                return true;
            }
        }

        return false;
    }
}
