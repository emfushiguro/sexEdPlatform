<form method="POST" action="{{ route('connector.educators.conversation', [$connector, $instructor]) }}" x-data="{ submitting: false }" @submit="submitting = true">
    @csrf
    <button type="submit" :disabled="submitting" :aria-busy="submitting" aria-label="Message instructor {{ $instructor->name }}" title="Message instructor" data-educator-action="message" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-purple-700 text-white hover:bg-purple-800 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-60">
        <svg x-show="!submitting" aria-hidden="true" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-8 4h5m-8 7 1.5-4.5A8.5 8.5 0 1 1 20.5 12a8.5 8.5 0 0 1-12 7.7L5 21Z" />
        </svg>
        <svg x-show="submitting" x-cloak aria-hidden="true" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
            <path class="opacity-75" fill="currentColor" d="M12 0v3a9 9 0 0 1 9 9h3A12 12 0 0 0 12 0Z" />
        </svg>
        <span x-show="submitting" x-cloak class="sr-only" role="status">Opening conversation...</span>
    </button>
</form>
