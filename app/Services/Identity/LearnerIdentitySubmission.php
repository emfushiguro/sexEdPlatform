<?php

namespace App\Services\Identity;

use App\Http\Requests\Auth\SubmitLearnerIdentityRequest;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class LearnerIdentitySubmission
{
    public function submit(User $actor, LearnerIdentityVerification $case, array $data, array $files): LearnerIdentityVerification
    {
        $newPaths = [];
        $oldPaths = [];

        try {
            $submitted = DB::transaction(function () use ($actor, $case, $data, $files, &$newPaths, &$oldPaths): LearnerIdentityVerification {
                $case = LearnerIdentityVerification::query()->whereKey($case->getKey())->lockForUpdate()->first();
                if (! $case) {
                    throw new DomainException('Learner identity case is not available for submission.');
                }
                $pathway = $actor->deriveAgeBracketCache();
                if ($case->user_id !== $actor->id || $case->superseded_at !== null
                    || $case->pathway !== match ($pathway) { 'teens' => 'teen', 'adults' => 'adult', default => null }
                    || ! $actor->isLearner() || $actor->isParentRegistration() || ! $actor->hasVerifiedEmail()
                    || ! in_array($case->status, [null, 'rejected'], true)
                    || $actor->identityVerifications()->whereNull('superseded_at')->whereKeyNot($case->id)->exists()) {
                    throw new DomainException('Learner identity case is not available for submission.');
                }

                foreach ($files as $slot => $file) {
                    if (! in_array($slot, ['identity_front', 'identity_back', 'selfie'], true)) {
                        throw new InvalidArgumentException('Unsupported evidence slot.');
                    }
                }

                $request = SubmitLearnerIdentityRequest::create('/', 'POST', $data, [], $files);
                $request->setUserResolver(fn () => $actor);
                Validator::make(array_merge($data, $files), $request->rules())->validate();
                $previousStatus = $case->status;
                $type = $data['document_type'];
                $subtype = $type === 'government_id' ? $data['government_id_type'] : null;
                $requiresBack = $type === 'government_id'
                    && (bool) data_get(config('guardian_identity.id_types', []), $subtype.'.requires_back', false);

                foreach ($files as $slot => $file) {
                    if (! $file instanceof UploadedFile || ! $file->isValid()) {
                        throw new InvalidArgumentException('Invalid identity upload.');
                    }
                    $image = @getimagesize($file->getRealPath());
                    if ($image === false || ! in_array($image['mime'] ?? null, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        throw new InvalidArgumentException('Invalid identity image.');
                    }
                    $path = $file->store('learner-verifications/'.$actor->id.'/'.$case->pathway, 'local');
                    if ($path) {
                        $newPaths[] = $path;
                    }
                    if (! $path || ! Storage::disk('local')->exists($path)) {
                        throw new RuntimeException('Identity evidence could not be stored.');
                    }
                    $old = $case->evidence()->where('slot', $slot)->first();
                    if ($old) {
                        $oldPaths[] = $old->storage_path;
                    }
                    $case->evidence()->updateOrCreate(['slot' => $slot], [
                        'storage_path' => $path,
                        'mime_type' => $image['mime'],
                        'byte_size' => $file->getSize(),
                        'width' => $image[0],
                        'height' => $image[1],
                        'submitted_at' => now(),
                    ]);
                }

                if (! $requiresBack && $back = $case->evidence()->where('slot', 'identity_back')->first()) {
                    $oldPaths[] = $back->storage_path;
                    $back->delete();
                }

                $case->forceFill([
                    'document_type' => $type,
                    'government_id_type' => $subtype,
                    'government_id_type_other' => $subtype === 'other' ? $data['government_id_type_other'] : null,
                    'status' => 'pending',
                    'submission_round' => $case->submission_round + 1,
                    'submitted_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                ])->save();
                $case->audits()->create([
                    'actor_id' => $actor->id,
                    'action' => $previousStatus === 'rejected' ? 'resubmitted' : 'submitted',
                    'from_status' => $previousStatus,
                    'to_status' => 'pending',
                    'submission_round' => $case->submission_round,
                    'created_at' => now(),
                ]);

                if ($oldPaths) {
                    $pathsToDelete = $oldPaths;
                    DB::afterCommit(function () use ($pathsToDelete): void {
                        try {
                            if (! Storage::disk('local')->delete($pathsToDelete)) {
                                Log::warning('Learner identity evidence deletion failed.', ['paths' => $pathsToDelete]);
                            }
                        } catch (Throwable $e) {
                            Log::warning('Learner identity evidence deletion failed.', [
                                'paths' => $pathsToDelete,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    });
                }

                return $case->load('evidence');
            });

            if ($newPaths && DB::transactionLevel() > 0) {
                DB::afterRollBack(function () use ($newPaths): void {
                    try {
                        if (! Storage::disk('local')->delete($newPaths)) {
                            Log::warning('Learner identity rollback evidence cleanup failed.', ['paths' => $newPaths]);
                        }
                    } catch (Throwable $e) {
                        Log::warning('Learner identity rollback evidence cleanup failed.', [
                            'paths' => $newPaths,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }
        } catch (Throwable $e) {
            if ($newPaths) {
                Storage::disk('local')->delete($newPaths);
            }
            throw $e;
        }

        return $submitted;
    }
}
