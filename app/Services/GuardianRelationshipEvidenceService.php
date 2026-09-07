<?php

namespace App\Services;

use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuardianRelationshipEvidenceService
{
    public function storeUploadedRound(
        ParentChildAccount $relationship,
        User $guardian,
        int $round,
        array $documents,
        array &$storedPaths,
    ): Collection {
        $storedPaths = [];
        $hashes = [];

        $acceptedTypes = GuardianRelationshipTypes::acceptedDocumentTypes(
            $relationship->relationship_type,
        );
        Validator::make(
            ['documents' => $documents],
            GuardianRelationshipEvidenceRules::for($acceptedTypes),
        )->validate();

        $metadataErrors = GuardianRelationshipEvidenceRules::metadataErrors($documents);
        if ($metadataErrors !== []) {
            throw ValidationException::withMessages($metadataErrors);
        }

        try {
            return DB::transaction(function () use (
                $relationship,
                $guardian,
                $round,
                $documents,
                &$storedPaths,
                &$hashes,
            ): Collection {
                ParentChildAccount::query()
                    ->whereKey($relationship->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (GuardianRelationshipVerificationDocument::query()
                    ->where('parent_child_account_id', $relationship->id)
                    ->where('submission_round', $round)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'documents' => 'This evidence round has already been submitted. Upload a fresh round instead.',
                    ]);
                }

                return collect($documents)->values()->map(function (array $item, int $index) use (
                    $relationship,
                    $guardian,
                    $round,
                    &$storedPaths,
                    &$hashes,
                ): GuardianRelationshipVerificationDocument {
                    $file = $item['file'];
                    if (! $file instanceof UploadedFile) {
                        throw ValidationException::withMessages(['documents' => 'Every evidence item must contain a file.']);
                    }
                    $realPath = $file->getRealPath();
                    if (! is_string($realPath)) {
                        throw ValidationException::withMessages(['documents' => 'An evidence file could not be read.']);
                    }

                    $hash = hash_file('sha256', $realPath);
                    if (! is_string($hash) || in_array($hash, $hashes, true)) {
                        throw ValidationException::withMessages(['documents' => 'The same evidence file cannot be uploaded twice.']);
                    }

                    if (GuardianRelationshipVerificationDocument::query()
                        ->where('parent_child_account_id', $relationship->id)
                        ->where('submission_round', $round)
                        ->where('content_sha256', $hash)
                        ->exists()) {
                        throw ValidationException::withMessages(['documents' => 'The same evidence file cannot be uploaded twice.']);
                    }

                    $hashes[] = $hash;
                    $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                    $filename = Str::uuid().'.'.$extension;
                    $path = $file->storeAs(
                        'guardian-relationship-verifications/'.$relationship->id.'/round-'.$round,
                        $filename,
                        'local',
                    );

                    if (! is_string($path)) {
                        throw ValidationException::withMessages(['documents' => 'An evidence file could not be stored.']);
                    }

                    $storedPaths[] = $path;

                    return GuardianRelationshipVerificationDocument::query()->create([
                        'parent_child_account_id' => $relationship->id,
                        'uploaded_by_user_id' => $guardian->id,
                        'document_type' => (string) $item['document_type'],
                        'submission_round' => $round,
                        'document_side' => (string) $item['document_side'],
                        'pairing_key' => $item['pairing_key'] ?? null,
                        'display_order' => $index,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => basename((string) $file->getClientOriginalName()),
                        'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'size_bytes' => (int) ($file->getSize() ?: 0),
                        'content_sha256' => $hash,
                        'submitted_at' => now(),
                    ]);
                });
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            $storedPaths = [];

            if ($this->isDuplicateEvidenceHashConstraint($exception)) {
                throw ValidationException::withMessages([
                    'documents' => 'The same evidence file cannot be uploaded twice.',
                ]);
            }

            throw $exception;
        }
    }

    public function deleteStoredPaths(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('local')->delete(array_values(array_unique($paths)));
        }
    }

    private function isDuplicateEvidenceHashConstraint(Throwable $exception): bool
    {
        return $exception instanceof QueryException
            && str_contains($exception->getMessage(), 'grvd_round_hash_unique');
    }
}
