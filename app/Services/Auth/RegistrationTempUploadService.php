<?php

namespace App\Services\Auth;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationTempUploadService
{
    private const SESSION_ROOT = 'registration_temp_uploads';

    public function store(string $flow, string $step, UploadedFile $file): array
    {
        $existing = $this->get($flow, $step);
        if (is_array($existing) && ! empty($existing['path'])) {
            Storage::disk('local')->delete((string) $existing['path']);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $fileName = sprintf('%s-%s.%s', $step, Str::uuid()->toString(), $extension);
        $path = $file->storeAs($this->tempDirectory($flow, $step), $fileName, 'local');

        $metadata = [
            'path' => $path,
            'original_name' => (string) $file->getClientOriginalName(),
            'mime_type' => (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'disk' => 'local',
        ];

        session([$this->sessionKey($flow, $step) => $metadata]);

        return $metadata;
    }

    public function get(string $flow, string $step): ?array
    {
        if (! $this->isSupportedFlowStep($flow, $step)) {
            return null;
        }

        $key = $this->sessionKey($flow, $step);
        $value = session($key);
        if (! is_array($value) || empty($value['path'])) {
            return null;
        }

        $path = (string) $value['path'];
        if (! $this->isSafeTempPath($path, $flow, $step)) {
            return null;
        }

        $diskName = (string) ($value['disk'] ?? 'public');
        if ($diskName === 'local') {
            return Storage::disk('local')->exists($path) ? $value : null;
        }

        if ($diskName !== 'public') {
            return null;
        }

        $public = Storage::disk('public');
        $local = Storage::disk('local');
        if (! $public->exists($path)) {
            if (! $local->exists($path)) {
                return null;
            }

            $value['disk'] = 'local';
            session([$key => $value]);

            return $value;
        }

        if (! $local->exists($path)) {
            $stream = $public->readStream($path);
            if (! is_resource($stream)) {
                return null;
            }

            try {
                if (! $local->writeStream($path, $stream)) {
                    return null;
                }
            } finally {
                fclose($stream);
            }
        }

        $publicHash = hash_file('sha256', $public->path($path));
        $localHash = hash_file('sha256', $local->path($path));
        if (! is_string($publicHash) || ! is_string($localHash) || ! hash_equals($publicHash, $localHash)) {
            return null;
        }

        if (! $public->delete($path)) {
            throw new \RuntimeException('Unable to remove the legacy public registration upload.');
        }

        $value['disk'] = 'local';
        session([$key => $value]);

        return $value;
    }

    public function remove(string $flow, string $step): void
    {
        $existing = $this->get($flow, $step);
        if (is_array($existing) && ! empty($existing['path'])) {
            Storage::disk('local')->delete((string) $existing['path']);
        }

        session()->forget($this->sessionKey($flow, $step));
    }

    public function finalize(string $flow, string $step, string $targetDir, string $targetPrefix): ?string
    {
        $existing = $this->get($flow, $step);
        if (! is_array($existing) || empty($existing['path'])) {
            session()->forget($this->sessionKey($flow, $step));

            return null;
        }

        $sourcePath = (string) $existing['path'];
        if (! $this->isSafeTempPath($sourcePath, $flow, $step) || ! Storage::disk('local')->exists($sourcePath)) {
            session()->forget($this->sessionKey($flow, $step));

            return null;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if ($extension === '' && ! empty($existing['original_name'])) {
            $extension = strtolower(pathinfo((string) $existing['original_name'], PATHINFO_EXTENSION));
        }

        $targetDirectory = trim($targetDir, '/');
        $targetFile = sprintf(
            '%s-%s%s',
            $targetPrefix,
            Str::uuid()->toString(),
            $extension !== '' ? '.'.$extension : ''
        );
        $targetPath = $targetDirectory !== '' ? $targetDirectory.'/'.$targetFile : $targetFile;

        Storage::disk('local')->makeDirectory($targetDirectory);
        if (! Storage::disk('local')->move($sourcePath, $targetPath)) {
            return null;
        }

        session()->forget($this->sessionKey($flow, $step));

        return $targetPath;
    }

    private function sessionKey(string $flow, string $step): string
    {
        return self::SESSION_ROOT.'.'.$flow.'.'.$step;
    }

    private function tempDirectory(string $flow, string $step): string
    {
        return 'registration-temp/'.$flow.'/'.$step;
    }

    private function isSupportedFlowStep(string $flow, string $step): bool
    {
        return in_array([$flow, $step], [
            ['parent', 'government_id'],
            ['child', 'verification_document'],
            ['learner', 'identity_front'],
            ['learner', 'identity_back'],
        ], true);
    }

    private function isSafeTempPath(string $path, string $flow, string $step): bool
    {
        $prefix = $this->tempDirectory($flow, $step).'/';
        $filename = str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : '';

        return $this->isSupportedFlowStep($flow, $step)
            && $filename !== ''
            && ! str_contains($filename, '/')
            && ! str_contains($filename, '\\')
            && $filename !== '.'
            && $filename !== '..';
    }
}
