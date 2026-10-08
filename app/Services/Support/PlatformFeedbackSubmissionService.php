<?php

namespace App\Services\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlatformFeedbackSubmissionService
{
    public function submit(User $user, array $validated, ?UploadedFile $attachment, string $userAgent): PlatformFeedback
    {
        $path = null;
        try {
            if ($attachment) {
                $path = $attachment->store("platform-feedback/{$user->id}", 'local');
                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('Unable to store feedback attachment.');
                }
            }
            $submissionToken = $validated['submission_token'] ?? $this->fallbackToken($user, $validated);
            $tokenRecord = PlatformFeedback::query()->where('submission_token', $submissionToken)->first();
            if ($tokenRecord && (int) $tokenRecord->user_id !== (int) $user->id) {
                throw ValidationException::withMessages(['submission_token' => 'This form has expired. Please refresh the page and try again.']);
            }
            if ($tokenRecord) {
                if ($path) {
                    Storage::disk('local')->delete($path);
                }

                return $tokenRecord;
            }
            $reference = $this->reference();

            try {
                return DB::transaction(function () use ($user, $validated, $path, $userAgent, $reference, $submissionToken): PlatformFeedback {
                    return PlatformFeedback::create([
                        'reference_number' => $reference,
                        'submission_token' => $submissionToken,
                        'user_id' => $user->id,
                        'user_role' => (string) ($user->role ?? $user->account_type ?? 'user'),
                        'type' => $validated['type'],
                        'subject' => $validated['subject'],
                        'description' => $validated['description'],
                        'rating' => $validated['rating'] ?? null,
                        'user_agent' => Str::limit($userAgent, 500, ''),
                        'may_contact' => (bool) ($validated['may_contact'] ?? false),
                        'attachment_path' => $path,
                        'status' => PlatformFeedbackStatus::New,
                    ]);
                });
            } catch (QueryException $exception) {
                if ((string) $exception->getCode() === '23000') {
                    $winner = PlatformFeedback::query()->where('submission_token', $submissionToken)->first();
                    if ($winner && (int) $winner->user_id === (int) $user->id) {
                        if ($path) {
                            Storage::disk('local')->delete($path);
                        }

                        return $winner;
                    }
                    if ($winner) {
                        throw ValidationException::withMessages(['submission_token' => 'This form has expired. Please refresh the page and try again.']);
                    }
                }
                throw $exception;
            }
        } catch (Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $e;
        }
    }

    private function reference(): string
    {
        do {
            $value = 'FB-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (PlatformFeedback::query()->where('reference_number', $value)->exists());

        return $value;
    }

    private function fallbackToken(User $user, array $validated): string
    {
        $hex = hash('sha256', $user->id.'|'.json_encode([$validated['type'] ?? null, $validated['subject'] ?? null, $validated['description'] ?? null]));

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-4'.substr($hex, 13, 3).'-8'.substr($hex, 16, 3).'-'.substr($hex, 19, 12);
    }
}
