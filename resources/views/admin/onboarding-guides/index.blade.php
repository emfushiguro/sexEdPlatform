@extends('layouts.admin')

@section('title', 'Onboarding Guides')
@section('page-title', 'Onboarding Guides')

@section('content')
<div class="space-y-8">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-purple-700">Platform onboarding</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Onboarding Guides</h1>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Create private role-specific walkthroughs, publish revisions, and review direct learner outcomes.</p>
        </div>
        <a href="{{ route('admin.onboarding-guides.create') }}" class="inline-flex min-h-10 items-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-purple-400">Create guide</a>
    </div>

    @php
        $totalGuides = $guides->count();
        $publishedGuides = $guides->where('status', \App\Models\OnboardingGuide::STATUS_PUBLISHED)->count();
        $draftGuides = $guides->where('status', \App\Models\OnboardingGuide::STATUS_DRAFT)->count();
        $archivedGuides = $guides->where('status', \App\Models\OnboardingGuide::STATUS_ARCHIVED)->count();
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Total guides', 'value' => $totalGuides, 'icon' => 'M4 6h16M4 12h16M4 18h16'],
            ['label' => 'Published', 'value' => $publishedGuides, 'icon' => 'M5 12l4 4L19 6'],
            ['label' => 'Drafts', 'value' => $draftGuides, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['label' => 'Archived', 'value' => $archivedGuides, 'icon' => 'M3 4h18v4H3zm2 4v11h14V8m-9 4h4'],
        ] as $card)
            <article class="relative overflow-hidden rounded-[24px] border border-brand-200/80 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-6 shadow-soft ring-1 ring-brand-200/40 transition duration-200 hover:-translate-y-0.5 hover:shadow-medium">
                <div class="flex items-start justify-between">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700/80">{{ $card['label'] }}</p>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-brand-500 text-white shadow-glow-purple ring-1 ring-brand-600/40">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" /></svg>
                    </span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</p>
            </article>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
        <div class="border-b border-brand-100 bg-[radial-gradient(circle_at_top_left,_rgba(163,14,178,0.17),_transparent_34%),radial-gradient(circle_at_top_right,_rgba(59,12,177,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8f3ff_100%)] px-6 py-6">
            <h2 class="mt-2 text-xl font-bold text-gray-900">Onboarding Guides Table</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <caption class="sr-only">Onboarding guide management</caption>
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-4 font-semibold">Guide</th>
                        <th class="px-5 py-4 font-semibold">Role</th>
                        <th class="px-5 py-4 font-semibold">Status</th>
                        <th class="px-5 py-4 font-semibold">Version</th>
                        <th class="px-5 py-4 font-semibold">Audience</th>
                        <th class="px-5 py-4 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($guides as $guide)
                        @php
                            $version = $guide->currentVersion ?? $guide->draft_version;
                            $analytics = $guide->analytics;
                            $statusClass = match($guide->status) {
                                \App\Models\OnboardingGuide::STATUS_PUBLISHED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                \App\Models\OnboardingGuide::STATUS_ARCHIVED => 'border-slate-200 bg-slate-100 text-slate-700',
                                default => 'border-amber-200 bg-amber-50 text-amber-700',
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $version?->title ?? 'Untitled guide' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $guide->guide_key }} · {{ $guide->versions_count }} total version(s)</p>
                            </td>
                            <td class="px-5 py-4 capitalize text-gray-700">{{ $guide->target_role }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClass }}">{{ str($guide->status)->headline() }}</span></td>
                            <td class="px-5 py-4 text-gray-700">{{ $version ? 'Version '.$version->version_number : '—' }}</td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                @if($analytics)
                                    <span class="block"><strong class="text-gray-900">Eligible users:</strong> {{ $analytics['eligible'] }}</span>
                                    <span class="block"><strong class="text-gray-900">Completed:</strong> {{ $analytics['completed'] }}</span>
                                    <span class="block"><strong class="text-gray-900">Dismissed:</strong> {{ $analytics['dismissed'] }}</span>
                                @else
                                    <span>Publish to collect aggregate counts.</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('admin.onboarding-guides.edit', $guide) }}" aria-label="Edit {{ $version?->title ?? 'guide' }}" title="Edit" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-purple-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-purple-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 0 1 3.182 3.182L9.75 16.963l-4.5.75.75-4.5L16.862 3.487Z" /></svg></a>
                                    <a href="{{ route('admin.onboarding-guides.history', $guide) }}" aria-label="View history for {{ $version?->title ?? 'guide' }}" title="History" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-purple-50 hover:text-brand-700 focus:outline-none focus:ring-2 focus:ring-purple-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 3-6.708L3 8m0-5v5h5m4-1v5l3 2" /></svg></a>
                                    @if($guide->status === \App\Models\OnboardingGuide::STATUS_PUBLISHED)
                                        <button type="button" onclick="document.getElementById('unpublish-guide-{{ $guide->id }}').showModal()" aria-label="Unpublish {{ $version?->title ?? 'guide' }}" aria-haspopup="dialog" title="Unpublish" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5v14" transform="rotate(45 12 12)" /></svg></button>
                                        <dialog id="unpublish-guide-{{ $guide->id }}" aria-labelledby="unpublish-title-{{ $guide->id }}" class="m-auto w-11/12 max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-left shadow-2xl backdrop:bg-slate-950/60">
                                            <h2 id="unpublish-title-{{ $guide->id }}" class="text-lg font-bold text-gray-900">Unpublish this guide?</h2>
                                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $version?->title ?? 'This guide' }} will no longer be available to users. Its video, version history, and user progress will be kept.</p>
                                            <form method="POST" action="{{ route('admin.onboarding-guides.unpublish', $guide) }}" class="mt-6 flex justify-end gap-3">@csrf<button type="button" onclick="this.closest('dialog').close()" autofocus class="min-h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><button type="submit" class="min-h-11 rounded-xl bg-amber-700 px-4 text-sm font-semibold text-white hover:bg-amber-800">Unpublish</button></form>
                                        </dialog>
                                    @endif
                                    @if($guide->status !== \App\Models\OnboardingGuide::STATUS_ARCHIVED)
                                        <button type="button" onclick="document.getElementById('archive-guide-{{ $guide->id }}').showModal()" aria-label="Archive {{ $version?->title ?? 'guide' }}" aria-haspopup="dialog" title="Archive" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-rose-200 text-rose-700 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-500"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18v4H3zm2 4v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8m-9 4h4" /></svg></button>
                                        <dialog id="archive-guide-{{ $guide->id }}" aria-labelledby="archive-title-{{ $guide->id }}" class="m-auto w-11/12 max-w-md rounded-2xl border border-gray-200 bg-white p-6 text-left shadow-2xl backdrop:bg-slate-950/60">
                                            <h2 id="archive-title-{{ $guide->id }}" class="text-lg font-bold text-gray-900">Archive this guide?</h2>
                                            <p class="mt-2 text-sm leading-6 text-gray-600">{{ $version?->title ?? 'This guide' }} will no longer be available or editable. Its video, version history, and user progress will be kept.</p>
                                            <form method="POST" action="{{ route('admin.onboarding-guides.archive', $guide) }}" class="mt-6 flex justify-end gap-3">@csrf<button type="button" onclick="this.closest('dialog').close()" autofocus class="min-h-11 rounded-xl border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button><button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-4 text-sm font-semibold text-white hover:bg-rose-800">Archive</button></form>
                                        </dialog>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-500">No onboarding guides have been created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
