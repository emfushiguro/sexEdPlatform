@php
    $version = $presentation['version'];
    $status = $presentation['status'];
@endphp

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
    class="m-auto max-h-[95vh] w-[min(94vw,64rem)] overflow-y-auto rounded-[2rem] border-0 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/70"
>
    <div class="grid gap-0 md:grid-cols-[minmax(0,1.05fr)_minmax(18rem,.95fr)]">
        <div class="relative overflow-hidden bg-slate-950 px-6 py-8 text-white sm:px-10 sm:py-10">
            <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-fuchsia-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-16 h-48 w-48 rounded-full bg-cyan-400/15 blur-3xl" aria-hidden="true"></div>

            <div class="relative">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">A quick orientation</p>
                <h2 id="onboarding-guide-title" class="mt-3 max-w-lg text-2xl font-bold tracking-tight sm:text-3xl">{{ $version->title }}</h2>
                <p id="onboarding-guide-description" class="mt-3 max-w-xl text-sm leading-6 text-slate-300">{{ $version->description }}</p>

                <div class="mt-7 overflow-hidden rounded-2xl border border-white/15 bg-black/30 shadow-xl">
                    <video
                        x-ref="video"
                        src="{{ route('onboarding-guides.video', $version) }}"
                        controls
                        playsinline
                        preload="metadata"
                        class="aspect-video w-full bg-black object-contain"
                        aria-label="{{ $version->title }} video"
                    ></video>
                </div>
            </div>
        </div>

        <div class="flex flex-col px-6 py-7 sm:px-8 sm:py-9">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Welcome to Conscious Connections</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Watch when you are ready, or come back to the guide from your dashboard navigation.</p>
                </div>
                <button
                    type="button"
                    @click="dismiss()"
                    class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-fuchsia-500 focus:ring-offset-2"
                    aria-label="Close onboarding guide"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="mt-8 rounded-2xl border border-cyan-100 bg-cyan-50/70 p-4">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-cyan-800">Your current status</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $status['status'] === 'incomplete' ? 'Ready when you are' : str($status['status'])->headline() }}</p>
                <p class="mt-1 text-xs leading-5 text-slate-600">You can replay this guide anytime from the Guide page.</p>
            </div>

            <p x-show="error" x-cloak x-text="error" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm leading-5 text-rose-800" role="alert"></p>

            <div class="mt-auto flex flex-col gap-3 pt-8">
                <button
                    type="button"
                    @click="startWatching()"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
                >
                    Start Watching
                </button>
                <button
                    type="button"
                    @click="complete()"
                    :disabled="saving"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-cyan-700 px-4 py-3 text-sm font-bold text-cyan-800 transition hover:bg-cyan-50 focus:outline-none focus:ring-2 focus:ring-cyan-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
                >
                    <span x-show="!saving">Mark guide complete</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
                <button
                    type="button"
                    @click="dismiss()"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2"
                >
                    Skip for now
                </button>
            </div>
        </div>
    </div>
</dialog>
