@props([
    'version',
    'status' => null,
    'showCompletionAction' => false,
])

@php
    $guideStatus = $status['status'] ?? 'incomplete';
    $completedAt = $status['completed_at'] ?? null;
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-5 sm:px-7">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-600">Onboarding guide</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ $version->title }}</h1>
        @if(filled($version->description))
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ $version->description }}</p>
        @endif
    </div>

    <div class="p-5 sm:p-7">
        <video
            controls
            playsinline
            preload="metadata"
            class="aspect-video w-full rounded-xl bg-slate-950 object-contain"
            src="{{ route('onboarding-guides.video', $version) }}"
            aria-label="{{ $version->title }} video"
        >
            Your browser does not support embedded videos.
        </video>

        <div class="mt-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-slate-900">
                    @if($guideStatus === 'completed')
                        Guide completed
                    @elseif($guideStatus === 'dismissed')
                        Guide dismissed
                    @else
                        Guide not completed
                    @endif
                </p>
                @if($guideStatus === 'completed' && $completedAt)
                    <p class="mt-1 text-sm text-slate-600">Completed on {{ $completedAt->format('F j, Y') }}</p>
                @elseif($guideStatus === 'dismissed')
                    <p class="mt-1 text-sm text-slate-600">You can still mark this guide complete after reviewing it.</p>
                @else
                    <p class="mt-1 text-sm text-slate-600">Review the guide, then confirm when you are finished.</p>
                @endif
            </div>

            @if($showCompletionAction && $guideStatus !== 'completed')
                <form method="POST" action="{{ route('onboarding-guides.complete', $version) }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 sm:w-auto"
                    >
                        Mark guide complete
                    </button>
                </form>
            @endif
        </div>
    </div>
</section>
