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
        if (is_array($existing) && !empty($existing['path'])) {
            Storage::disk('public')->delete((string) $existing['path']);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $fileName = sprintf('%s-%s.%s', $step, Str::uuid()->toString(), $extension);
        $path = $file->storeAs($this->tempDirectory($flow, $step), $fileName, 'public');

        $metadata = [
            'path' => $path,
            'original_name' => (string) $file->getClientOriginalName(),
            'mime_type' => (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'disk' => 'public',
        ];

        session([$this->sessionKey($flow, $step) => $metadata]);

        return $metadata;
    }

    public function get(string $flow, string $step): ?array
    {
        $value = session($this->sessionKey($flow, $step));

        return is_array($value) ? $value : null;
    }

    public function remove(string $flow, string $step): void
    {
        $existing = $this->get($flow, $step);
        if (is_array($existing) && !empty($existing['path'])) {
            Storage::disk('public')->delete((string) $existing['path']);
        }

        session()->forget($this->sessionKey($flow, $step));
    }

    public function finalize(string $flow, string $step, string $targetDir, string $targetPrefix): ?string
    {
        $existing = $this->get($flow, $step);
        if (!is_array($existing) || empty($existing['path'])) {
            return null;
        }

        $sourcePath = (string) $existing['path'];
        if (!Storage::disk('public')->exists($sourcePath)) {
            session()->forget($this->sessionKey($flow, $step));

            return null;
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if ($extension === '' && !empty($existing['original_name'])) {
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

        Storage::disk('public')->makeDirectory($targetDirectory);
        Storage::disk('public')->move($sourcePath, $targetPath);

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
}
