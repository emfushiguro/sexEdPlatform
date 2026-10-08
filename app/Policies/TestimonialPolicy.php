<?php

namespace App\Policies;

use App\Models\Testimonial;
use App\Models\User;
use App\Services\Support\TestimonialEligibility;

class TestimonialPolicy
{
    public function viewAny(User $user): bool
    {
        return app(TestimonialEligibility::class)->canSubmit($user) || $user->hasRole('admin');
    }

    public function view(User $user, Testimonial $testimonial): bool
    {
        return $user->id === $testimonial->user_id || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return app(TestimonialEligibility::class)->canSubmit($user);
    }

    public function update(User $user, Testimonial $testimonial): bool
    {
        return $user->hasRole('admin');
    }

    public function withdraw(User $user, Testimonial $testimonial): bool
    {
        return $user->hasRole('admin')
            || ($user->id === $testimonial->user_id
                && in_array($testimonial->status?->value, ['draft', 'published'], true));
    }

    public function publish(User $user, Testimonial $testimonial): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user, Testimonial $testimonial): bool
    {
        return $user->hasRole('admin');
    }
}
