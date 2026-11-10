@php
    $version = $presentation['version'];
@endphp

<style>
    #onboarding-guide-video:fullscreen { width: 100%; height: 100%; max-height: none !important; }
</style>

<dialog
    x-data="onboardingGuideModal(@js([
        'dismissUrl' => route('onboarding-guides.dismiss', $version),
        'completeUrl' => route('onboarding-guides.complete', $version),
        'csrfToken' => csrf_token(),
    ]))"
    x-ref="dialog"
    x-init="$nextTick(() => $refs.dialog.showModal())"
    @cancel.prevent="dismiss()"
    aria-labelledby="onboarding-guide-title"
    aria-describedby="onboarding-guide-description"
    style="width: min(92vw, 780px); max-width: 780px; max-height: min(90dvh, 850px);"
    class="m-auto overflow-y-auto rounded-2xl border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/70"
>
    <div class="relative p-5 sm:p-7">
        <button type="button" @click="dismiss()" aria-label="Close onboarding guide"
            class="absolute right-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full text-slate-500 hover:bg-purple-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
        </button>

        <header class="pr-12">
            <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Welcome to Conscious Connections</p>
            <h2 id="onboarding-guide-title" class="mt-2 text-xl font-bold leading-tight text-slate-900 sm:text-2xl">{{ $version->title }}</h2>
            <p id="onboarding-guide-description" class="mt-2 text-sm leading-6 text-slate-600">{{ $version->description }}</p>
        </header>

        <div class="mt-4 overflow-hidden rounded-xl bg-black">
            <video id="onboarding-guide-video" x-ref="video" src="{{ route('onboarding-guides.video', $version) }}" controls playsinline preload="metadata"
                style="display: block; width: 100%; max-height: 45vh; aspect-ratio: 16 / 9; object-fit: contain;"
                class="bg-black" aria-label="{{ $version->title }} video"></video>
        </div>

        <p class="mt-3 text-sm text-slate-500">You can replay this guide anytime from the Guide page.</p>
        <p x-show="error" x-cloak x-text="error" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert"></p>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <button type="button" @click="startWatching()" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Start Watching</button>
            <button type="button" @click="dismiss()" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">Skip for now</button>
        </div>
    </div>
</dialog>
