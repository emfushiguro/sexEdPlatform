<?php

namespace App\Services\InstructorApplication;

use App\Models\InstructorApplication;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class InstructorApplicationDocumentStorage
{
    private const SLOTS = [
        'government_id' => 'government_id_path',
        'clearance' => 'clearance_path',
        'cv_resume' => 'cv_resume_path',
        'teaching_credential' => 'teaching_credential_path',
        'sexed_certificate' => 'sexed_certificate_path',
        'professional_license' => 'professional_license_path',
    ];

    public function store(User $user, UploadedFile $file, string $slot): array
    {
        $column = $this->columnFor($slot);
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid().'.'.$extension;
        $path = 'instructor-applications/'.$user->id.'/'.$filename;

        Storage::disk(config('instructor_application.document_disk', 'local'))
            ->putFileAs(dirname($path), $file, basename($path));

        return [
            'slot' => $slot,
            'column' => $column,
            'path' => $path,
            'disk' => config('instructor_application.document_disk', 'local'),
            'original_name' => $this->safeDisplayName($file->getClientOriginalName(), $extension),
            'size' => (int) $file->getSize(),
            'mime_type' => (string) $file->getClientMimeType(),
        ];
    }

    public function columnFor(string $slot): string
    {
        abort_unless(array_key_exists($slot, self::SLOTS), 404);

        return self::SLOTS[$slot];
    }

    public function diskFor(InstructorApplication $application, string $slot): string
    {
        $metadata = $application->application_metadata['documents'][$slot] ?? null;
        $disk = is_array($metadata) ? ($metadata['disk'] ?? null) : null;

        return in_array($disk, ['local', 'public'], true)
            ? $disk
            : ($application->document_disk ?: 'public');
    }

    public function displayNameFor(InstructorApplication $application, string $slot): string
    {
        $metadata = $application->application_metadata['documents'][$slot] ?? null;
        $name = is_array($metadata) ? ($metadata['original_name'] ?? null) : null;

        return $this->safeDisplayName((string) ($name ?: $slot), pathinfo((string) $application->{$this->columnFor($slot)}, PATHINFO_EXTENSION));
    }

    private function safeDisplayName(string $name, string $extension): string
    {
        $name = basename(str_replace(['\\', '/', '"', "'"], '', $name));
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: 'document';
        $name = trim($name, " ._");

        if ($name === '') {
            $name = 'document';
        }

        return str_ends_with(strtolower($name), '.'.strtolower($extension)) || $extension === ''
            ? $name
            : $name.'.'.$extension;
    }
}
