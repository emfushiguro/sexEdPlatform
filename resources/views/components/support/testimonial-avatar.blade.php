@props([
    'testimonial',
    'respectConsent' => true,
])

@php
    $displayName = trim((string) ($testimonial->display_name ?: 'Anonymous learner'));
    $avatarPath = $testimonial->user?->learnerProfile?->avatar_path
        ?? $testimonial->user?->instructorProfile?->profile_photo_path
        ?? $testimonial->user?->profile?->avatar;
    $avatarUrl = $avatarPath
        ? (request()->routeIs('admin.*')
            ? route('admin.testimonials.avatar', $testimonial)
            : route('testimonials.avatar', $testimonial))
        : null;
    $initials = collect(preg_split('/\s+/', $displayName) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : 'U';
@endphp

@if(! $respectConsent || $testimonial->show_profile_image)
    @if($avatarUrl)
        <img data-testimonial-avatar-image src="{{ $avatarUrl }}" alt="{{ $displayName }}" loading="lazy" referrerpolicy="no-referrer" onerror="this.hidden=true;this.nextElementSibling.hidden=false" class="h-12 w-12 shrink-0 rounded-full object-cover">
        <span data-testimonial-avatar-fallback hidden aria-label="{{ $displayName }} profile image placeholder" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">{{ $initials }}</span>
    @else
        <span data-testimonial-avatar-fallback aria-label="{{ $displayName }} profile image placeholder" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-purple-100 text-sm font-bold text-purple-700">{{ $initials }}</span>
    @endif
@endif
