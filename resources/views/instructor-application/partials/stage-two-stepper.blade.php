@php
    $step = $step ?? 'documents';
    $expertiseActive = $step === 'expertise';
@endphp

<nav aria-label="Stage Two application steps" class="mx-auto mb-6 max-w-xl">
    <div class="rounded-2xl border border-purple-100/70 bg-white px-5 py-4 shadow-sm sm:px-6">
        <div class="flex items-start">
            @if ($expertiseActive)
                <a href="{{ route('instructor-application.application.edit') }}" class="group flex min-w-0 flex-1 flex-col items-center text-center focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2" aria-label="Return to required documents">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-purple-600 to-indigo-700 text-white shadow-sm transition group-hover:scale-105">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <span class="mt-2 text-xs font-semibold leading-tight text-purple-700">Required documents</span>
                </a>
            @else
                <div class="flex min-w-0 flex-1 flex-col items-center text-center" aria-current="step">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-sm font-bold text-purple-700 ring-2 ring-purple-400 shadow-sm">1</span>
                    <span class="mt-2 text-xs font-semibold leading-tight text-purple-700">Required documents</span>
                </div>
            @endif

            <div class="mt-[18px] h-0.5 flex-1 bg-gradient-to-r from-purple-600 to-indigo-700" aria-hidden="true"></div>

            <div class="flex min-w-0 flex-1 flex-col items-center text-center" @if ($expertiseActive) aria-current="step" @endif>
                <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $expertiseActive ? 'bg-white text-sm font-bold text-purple-700 ring-2 ring-purple-400 shadow-sm' : 'border border-gray-200 bg-white text-sm font-medium text-gray-400' }}">2</span>
                <span class="mt-2 text-xs font-semibold leading-tight {{ $expertiseActive ? 'text-purple-700' : 'text-gray-400' }}">Teaching expertise</span>
            </div>
        </div>
    </div>
</nav>
