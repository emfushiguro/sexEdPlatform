@php
    $participantName = $user?->name ?? 'Unknown participant';
    $photo = $user?->learnerProfile?->avatar_path ?? $user?->instructorProfile?->profile_photo_path;
    $initials = str($participantName)->squish()->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1)->upper())->implode('');
@endphp
<div class="flex items-center gap-3" data-participant-profile>
    <span class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-purple-100 text-xs font-bold text-purple-700" aria-hidden="true">
        {{ $initials }}
        @if($photo)
            <img src="{{ asset('storage/'.ltrim((string) preg_replace('#^storage/#', '', $photo), '/')) }}" alt="" loading="lazy" onerror="this.remove()" class="absolute inset-0 h-full w-full object-cover">
        @endif
    </span>
    <div class="min-w-0">
        <div class="font-semibold text-gray-900">{{ $participantName }}</div>
        <div class="text-xs text-gray-500">{{ $user?->email }}</div>
    </div>
</div>
