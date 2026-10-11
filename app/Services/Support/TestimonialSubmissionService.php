<?php

namespace App\Services\Support;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TestimonialSubmissionService
{
    public function __construct(private readonly TestimonialEligibility $eligibility) {}

    public function submit(User $user, array $validated): Testimonial
    {
        abort_unless($this->eligibility->canSubmit($user), 403, 'Your account is not eligible to submit a testimonial.');
        abort_unless((bool) ($validated['consent'] ?? false), 422, 'Consent is required before submitting a testimonial.');

        $token = $validated['submission_token'] ?? $this->fallbackToken($user, $validated);
        $tokenRecord = Testimonial::query()->where('submission_token', $token)->first();
        if ($tokenRecord && (int) $tokenRecord->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['submission_token' => 'This form has expired. Please refresh the page and try again.']);
        }
        if ($tokenRecord) {
            return $tokenRecord;
        }

        try {
            return DB::transaction(fn (): Testimonial => Testimonial::create([
                'submission_token' => $token,
                'platform_feedback_id' => null,
                'user_id' => $user->id,
                'display_name' => $this->publicDisplayName($user),
                'display_role' => $this->roleLabel($user),
                'quotation' => $validated['quotation'],
                'show_profile_image' => true,
                'show_role' => true,
                'consent_given' => true,
                'consented_at' => now(),
                'consent_withdrawn_at' => null,
                'status' => TestimonialStatus::Draft,
                'published_at' => null,
                'withdrawn_at' => null,
            ]));
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $winner = Testimonial::query()->where('submission_token', $token)->first();
                if ($winner && (int) $winner->user_id === (int) $user->id) {
                    return $winner;
                }
                if ($winner) {
                    throw ValidationException::withMessages(['submission_token' => 'This form has expired. Please refresh the page and try again.']);
                }
            }
            throw $exception;
        }
    }

    public function publicDisplayName(User $user): string
    {
        $user->loadMissing('learnerProfile');

        if ($user->role === 'learner' && filled($user->learnerProfile?->username)) {
            return trim((string) $user->learnerProfile->username);
        }

        return trim((string) ($user->full_name ?: $user->name ?: ucfirst((string) $user->role)));
    }

    private function roleLabel(User $user): string
    {
        return $user->role === 'instructor' ? 'Instructor' : 'Learner';
    }

    private function fallbackToken(User $user, array $validated): string
    {
        $hex = hash('sha256', $user->id.'|'.json_encode([$validated['quotation'] ?? null]));

        return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-4'.substr($hex, 13, 3).'-8'.substr($hex, 16, 3).'-'.substr($hex, 19, 12);
    }
}
