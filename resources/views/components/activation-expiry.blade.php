@props(['expiresAt', 'live' => true])
@php
    $remainingMs = max(0, ($expiresAt?->getTimestampMs() ?? 0) - now()->getTimestampMs());
    $minutes = (int) ceil($remainingMs / 60000);
@endphp
<p {{ $attributes }}
    @if($live)
        x-data="{ minutes: {{ $minutes }}, deadline: Date.now() + {{ $remainingMs }}, timer: null, init() { this.timer = setInterval(() => { this.minutes = Math.max(0, Math.ceil((this.deadline - Date.now()) / 60000)) }, 1000) }, destroy() { clearInterval(this.timer) } }"
        x-text="minutes > 0 ? 'Expires in ' + minutes + (minutes === 1 ? ' minute.' : ' minutes.') : 'This code has expired. Ask for a new code.'"
        aria-live="polite"
    @endif
>{{ $minutes > 0 ? 'Expires in '.$minutes.($minutes === 1 ? ' minute.' : ' minutes.') : 'This code has expired. Ask for a new code.' }}</p>
