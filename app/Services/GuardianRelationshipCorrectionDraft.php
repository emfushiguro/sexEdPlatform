<?php

namespace App\Services;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\GuardianRelationshipEvidenceRules;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class GuardianRelationshipCorrectionDraft
{
    private const SNAPSHOT = 'guardian_relationship_correction_draft';

    public function save(User $guardian, ParentChildAccount $relationship, int $round, string $slot, UploadedFile $file): array
    {
        $this->authorize($guardian, $relationship);
        $directory = $this->directory($guardian, $relationship, $round);

        return $this->locked($directory, function () use ($guardian, $relationship, $round, $slot, $file, $directory): array {
            $relationship = $relationship->fresh();
            $this->authorize($guardian, $relationship);
            $slots = $this->slots($relationship, $round);
            if (! isset($slots[$slot])) {
                throw ValidationException::withMessages(['slot' => 'Save only a requested correction.']);
            }
            Validator::make(['file' => $file], ['file' => GuardianRelationshipEvidenceRules::for([])['documents.*.file']])->validate();
            if (! in_array((new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()), ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
                throw ValidationException::withMessages(['file' => 'Upload a PDF, JPEG, PNG, or WebP file with matching contents.']);
            }
            $disk = Storage::disk('local');
            $saved = $this->read($directory, $slots);
            $previous = $saved[$slot]['path'] ?? null;
            $path = $disk->putFileAs($directory, $file, bin2hex(random_bytes(16)));
            if ($path === false) {
                throw new RuntimeException('Unable to save the replacement file.');
            }
            $saved[$slot] = $slots[$slot] + [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'sha256' => hash_file('sha256', $disk->path($path)),
                'expires_at' => time() + (int) config('session.lifetime', 120) * 60,
            ];
            try {
                if (! $disk->put($directory.'/manifest.json', json_encode($saved, JSON_THROW_ON_ERROR))) {
                    throw new RuntimeException('Unable to save the replacement details.');
                }
            } catch (\Throwable $exception) {
                $disk->delete($path);
                throw $exception;
            }
            if ($previous !== null) {
                $disk->delete($previous);
            }

            return $saved[$slot];
        });
    }

    public function current(User $guardian, ParentChildAccount $relationship): array
    {
        $this->authorize($guardian, $relationship);
        if ($relationship->relationship_verified_status !== ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED
            || $relationship->relationship_correction_request === null
            || in_array($relationship->relationship_status, ['rejected', 'revoked', 'inactive'], true)) {
            return [];
        }
        $round = (int) $relationship->current_evidence_round;
        $directory = $this->directory($guardian, $relationship, $round);

        return $this->locked($directory, fn () => $this->read($directory, $this->slots($relationship, $round)));
    }

    public function populate(Request $request, User $guardian, ParentChildAccount $relationship): void
    {
        $this->authorize($guardian, $relationship);
        Validator::make(['current_evidence_round' => $request->input('current_evidence_round')], ['current_evidence_round' => ['required', 'integer', 'min:0']])->validate();
        $round = (int) $request->input('current_evidence_round');
        $slots = $this->slots($relationship, $round);
        if ($request->input('documents') !== null || $request->files->has('documents')) {
            throw ValidationException::withMessages(['documents' => 'Save each replacement before submitting all corrections.']);
        }
        $directory = $this->directory($guardian, $relationship, $round);
        $this->locked($directory, function () use ($request, $directory, $slots): void {
            $saved = $this->read($directory, $slots);
            if (array_diff_key($slots, $saved) !== []) {
                throw ValidationException::withMessages(['documents' => 'Save every requested replacement before submitting all corrections.']);
            }
            $documents = [];
            $files = [];
            foreach ($slots as $slot => $metadata) {
                $documents[] = $metadata;
                $files[] = ['file' => new UploadedFile(Storage::disk('local')->path($saved[$slot]['path']), $saved[$slot]['name'], null, null, true)];
            }
            $request->merge(['documents' => $documents]);
            $request->files->set('documents', $files);
            $request->attributes->set(self::SNAPSHOT, ['directory' => $directory, 'saved' => $saved]);
        });
    }

    public function submit(Request $request, User $guardian, ParentChildAccount $relationship, Closure $submit): mixed
    {
        $this->authorize($guardian, $relationship);
        $round = (int) $request->input('current_evidence_round', -1);
        $directory = $this->directory($guardian, $relationship, $round);
        $saved = [];

        $result = $this->locked($directory, function () use ($request, $guardian, $relationship, $round, $directory, $submit, &$saved): mixed {
            $relationship = $relationship->fresh();
            $this->authorize($guardian, $relationship);
            $saved = $this->read($directory, $this->slots($relationship, $round));
            $snapshot = $request->attributes->get(self::SNAPSHOT);
            if ($saved === [] || $snapshot !== ['directory' => $directory, 'saved' => $saved]) {
                throw ValidationException::withMessages(['documents' => 'Saved replacements changed. Reload and submit the current corrections.']);
            }

            return $submit();
        });
        $cleanup = fn () => $this->locked($directory, fn () => $this->clear($directory, $saved));
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($cleanup);
        } else {
            $cleanup();
        }

        return $result;
    }

    private function authorize(User $guardian, ParentChildAccount $relationship): void
    {
        if ((int) $relationship->parent_user_id !== (int) $guardian->id
            || $guardian->status !== User::STATUS_ACTIVE || ! $guardian->isParentVerificationApproved()) {
            throw new AuthorizationException;
        }
    }

    private function slots(ParentChildAccount $relationship, int $round): array
    {
        $correction = $relationship->relationship_correction_request;
        if ($round !== (int) $relationship->current_evidence_round || $round !== ($correction['round'] ?? null)) {
            throw ValidationException::withMessages(['current_evidence_round' => 'This form is out of date. Reload the current evidence.']);
        }
        if ($relationship->relationship_verified_status !== ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED
            || in_array($relationship->relationship_status, ['rejected', 'revoked', 'inactive'], true)) {
            throw ValidationException::withMessages(['documents' => 'Corrections are not open for this relationship.']);
        }
        $slots = [];
        $documents = $relationship->verificationDocuments()->where('submission_round', $round)->whereNull('superseded_at')->get()->keyBy('id');
        foreach ($correction['document_ids'] as $id) {
            $document = $documents->get($id);
            if ($document === null) {
                throw ValidationException::withMessages(['documents' => 'Reload the current requested evidence.']);
            }
            $slots['document:'.$id] = [
                'document_type' => $document->document_type,
                'document_side' => $document->document_side,
                'pairing_key' => $document->pairing_key,
                'replacement_document_id' => $document->id,
            ];
        }
        foreach ($correction['document_types'] as $type) {
            $slots['type:'.$type] = ['document_type' => $type, 'document_side' => 'not_applicable', 'pairing_key' => null, 'replacement_document_id' => null];
        }
        if ($slots === []) {
            throw ValidationException::withMessages(['documents' => 'There are no requested corrections to save.']);
        }

        return $slots;
    }

    private function directory(User $guardian, ParentChildAccount $relationship, int $round): string
    {
        return 'guardian-relationship-correction-drafts/'.hash('sha256', implode('|', [app('session')->getId(), $guardian->id, $relationship->id, $round]));
    }

    private function read(string $directory, array $slots): array
    {
        $disk = Storage::disk('local');
        $saved = $this->pruneDirectory($directory);
        $valid = [];
        foreach (is_array($saved) ? $saved : [] as $slot => $item) {
            if (! is_array($item) || ! isset($slots[$slot]) || array_intersect_key($item, $slots[$slot]) !== $slots[$slot]
                || ! is_string($item['path'] ?? null) || ! preg_match('#^'.preg_quote($directory, '#').'/[a-f0-9]{32}$#D', $item['path'])
                || ! is_string($item['name'] ?? null) || ! is_string($item['sha256'] ?? null)
                || ($item['expires_at'] ?? 0) <= time() || ! $disk->exists($item['path'])
                || ! hash_equals($item['sha256'], hash_file('sha256', $disk->path($item['path'])))) {
                continue;
            }
            $valid[$slot] = $item;
        }

        return $valid;
    }

    public function pruneExpired(): void
    {
        foreach (Storage::disk('local')->directories('guardian-relationship-correction-drafts') as $directory) {
            if (preg_match('#^guardian-relationship-correction-drafts/[a-f0-9]{64}$#D', $directory)) {
                $this->locked($directory, fn () => $this->pruneDirectory($directory));
            }
        }
    }

    private function pruneDirectory(string $directory): array
    {
        $disk = Storage::disk('local');
        $manifest = $directory.'/manifest.json';
        $saved = $disk->exists($manifest) ? json_decode($disk->get($manifest), true) : [];
        $saved = is_array($saved) ? $saved : [];
        $active = [];
        $filePattern = '#^'.preg_quote($directory, '#').'/[a-f0-9]{32}$#D';
        foreach ($saved as $slot => $item) {
            if (! is_array($item) || ! is_string($item['path'] ?? null) || ! preg_match($filePattern, $item['path'])) {
                continue;
            }
            if (is_int($item['expires_at'] ?? null) && $item['expires_at'] > time()) {
                $active[$slot] = $item;
            } else {
                $disk->delete($item['path']);
            }
        }
        $cutoff = time() - (int) config('session.lifetime', 120) * 60;
        $referenced = array_column($active, 'path');
        foreach ($disk->files($directory) as $path) {
            if (preg_match($filePattern, $path) && ! in_array($path, $referenced, true) && $disk->lastModified($path) <= $cutoff) {
                $disk->delete($path);
            }
        }
        if ($active === []) {
            $disk->delete($manifest);
        } elseif ($active !== $saved) {
            if (! $disk->put($manifest, json_encode($active, JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Unable to update the replacement details.');
            }
        }

        return $active;
    }

    private function locked(string $directory, Closure $callback): mixed
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory($directory);
        $lock = fopen($disk->path($directory.'/.lock'), 'c');
        if ($lock === false) {
            throw new RuntimeException('Unable to open the correction draft lock.');
        }
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to lock the correction draft.');
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function clear(string $directory, array $submitted): void
    {
        $disk = Storage::disk('local');
        $manifest = $directory.'/manifest.json';
        $saved = $disk->exists($manifest) ? json_decode($disk->get($manifest), true) : [];
        $saved = is_array($saved) ? $saved : [];
        foreach ($submitted as $slot => $item) {
            if (($saved[$slot] ?? null) === $item) {
                unset($saved[$slot]);
            }
        }
        $disk->delete(array_column($submitted, 'path'));
        // Keep the lock file so later requests always lock the same file.
        if ($saved === []) {
            $disk->delete($manifest);
        } else {
            $disk->put($manifest, json_encode($saved, JSON_THROW_ON_ERROR));
        }
    }
}
