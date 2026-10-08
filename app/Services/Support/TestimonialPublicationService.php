<?php

namespace App\Services\Support;

use App\Enums\TestimonialStatus;
use App\Models\Testimonial;
use App\Models\User;

class TestimonialPublicationService
{
    /**
     * @return array{eligible: bool, code: ?string, message: ?string}
     */
    public function eligibility(Testimonial $testimonial): array
    {
        $testimonial->loadMissing('user');
        $author = $testimonial->user;

        if (! $author) {
            return $this->ineligible('author_missing', 'The testimonial author is unavailable.');
        }

        if (! app(TestimonialEligibility::class)->canSubmit($author)) {
            return $this->ineligible('author_not_eligible', 'Only active adult learners and instructors can publish testimonials.');
        }

        if (! $testimonial->consent_given || $testimonial->consent_withdrawn_at) {
            return $this->ineligible('consent_missing', 'Active publication consent is required.');
        }

        if (trim((string) $testimonial->display_name) === '') {
            return $this->ineligible('display_name_missing', 'Add a public display name before publishing.');
        }

        if ($testimonial->display_role && ! $testimonial->show_role) {
            return $this->ineligible('role_consent_missing', 'The public role is not covered by the submitter consent.');
        }

        if (trim((string) $testimonial->quotation) === '') {
            return $this->ineligible('quotation_missing', 'Add a testimonial quotation before publishing.');
        }

        if (mb_strlen($testimonial->quotation) > 1000) {
            return $this->ineligible('quotation_too_long', 'Shorten the testimonial quotation to 1,000 characters or fewer.');
        }

        return ['eligible' => true, 'code' => null, 'message' => null];
    }

    public function publish(Testimonial $testimonial, User $admin): Testimonial
    {
        $eligibility = $this->eligibility($testimonial);
        abort_unless($eligibility['eligible'], 422, $eligibility['message'] ?? 'This testimonial is not currently eligible for publication.');

        $testimonial->update(['status' => TestimonialStatus::Published, 'approved_by' => $admin->id, 'published_at' => now(), 'withdrawn_at' => null]);

        return $testimonial->fresh();
    }

    public function reject(Testimonial $testimonial, User $admin): Testimonial
    {
        $testimonial->update([
            'status' => TestimonialStatus::Rejected,
            'approved_by' => $admin->id,
            'published_at' => null,
            'withdrawn_at' => null,
        ]);

        return $testimonial->fresh();
    }

    public function withdraw(Testimonial $testimonial, bool $revokeConsent = false): Testimonial
    {
        $data = [
            'status' => TestimonialStatus::Withdrawn,
            'published_at' => null,
            'withdrawn_at' => now(),
        ];
        if ($revokeConsent) {
            $data['consent_withdrawn_at'] = now();
        }
        $testimonial->update($data);

        return $testimonial->fresh();
    }

    /**
     * @return array{eligible: false, code: string, message: string}
     */
    private function ineligible(string $code, string $message): array
    {
        return ['eligible' => false, 'code' => $code, 'message' => $message];
    }
}
