<?php

namespace App\Services\Identity;

use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Services\Auth\RegistrationTempUploadService;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class LearnerIdentityDocumentDraft
{
    private const SESSION_KEY = 'learner_identity_document_draft';

    public function __construct(private readonly RegistrationTempUploadService $uploads) {}

    public function current(User $user, LearnerIdentityVerification $case): ?array
    {
        $draft = session(self::SESSION_KEY);
        if (! is_array($draft)) {
            return null;
        }

        if (($draft['user_id'] ?? null) !== $user->id
            || ($draft['case_id'] ?? null) !== $case->id
            || ($draft['pathway'] ?? null) !== $case->pathway
            || ($draft['submission_round'] ?? null) !== $case->submission_round
            || $case->user_id !== $user->id
            || $case->superseded_at !== null
            || ! in_array($case->status, [null, 'rejected'], true)) {
            $this->clear();

            return null;
        }

        foreach (['identity_front', 'identity_back'] as $slot) {
            $path = $draft[$slot.'_path'] ?? null;
            $upload = $this->uploads->get('learner', $slot);
            if ($path !== null && ($upload['path'] ?? null) !== $path) {
                $this->clear();

                return null;
            }
        }

        return $draft;
    }

    public function stage(User $user, LearnerIdentityVerification $case, array $data, ?UploadedFile $front, ?UploadedFile $back): void
    {
        $previous = $this->current($user, $case);
        if ($previous === null || ($previous['id_selection'] ?? null) !== $data['id_selection']) {
            $this->clear();
        }

        try {
            if ($front) {
                $this->uploads->store('learner', 'identity_front', $front);
            }
            if ($back) {
                $this->uploads->store('learner', 'identity_back', $back);
            } elseif ($data['document_type'] !== 'government_id'
                || ! (bool) data_get(config('guardian_identity.id_types', []), $data['government_id_type'].'.requires_back', false)) {
                $this->uploads->remove('learner', 'identity_back');
            }

            $frontPath = $this->uploads->get('learner', 'identity_front')['path'] ?? null;
            $backPath = $this->uploads->get('learner', 'identity_back')['path'] ?? null;
            if (($front && ! $frontPath) || ($back && ! $backPath)) {
                throw new RuntimeException('Identity document could not be stored.');
            }

            session([self::SESSION_KEY => [
                'user_id' => $user->id,
                'case_id' => $case->id,
                'pathway' => $case->pathway,
                'submission_round' => $case->submission_round,
                'id_selection' => $data['id_selection'],
                'document_type' => $data['document_type'],
                'government_id_type' => $data['government_id_type'] ?? null,
                'government_id_type_other' => $data['government_id_type_other'] ?? null,
                'identity_front_path' => $frontPath,
                'identity_back_path' => $backPath,
            ]]);
        } catch (Throwable $e) {
            $this->clear();

            throw $e;
        }
    }

    public function files(User $user, LearnerIdentityVerification $case): array
    {
        $draft = $this->current($user, $case);
        if ($draft === null) {
            throw new DomainException('Your ID upload has expired. Please upload it again.');
        }

        $files = [];
        foreach (['identity_front', 'identity_back'] as $slot) {
            if (empty($draft[$slot.'_path'])) {
                continue;
            }
            $metadata = $this->uploads->get('learner', $slot);
            $files[$slot] = new UploadedFile(
                Storage::disk('local')->path($metadata['path']),
                $metadata['original_name'] ?: basename($metadata['path']),
                $metadata['mime_type'] ?: null,
                null,
                true,
            );
        }

        return $files;
    }

    public function clear(): void
    {
        $this->uploads->remove('learner', 'identity_front');
        $this->uploads->remove('learner', 'identity_back');
        session()->forget(self::SESSION_KEY);
    }
}
