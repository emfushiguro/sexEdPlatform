@extends('layouts.admin')

@section('title', 'Onboarding Guides')
@section('page-title', 'Onboarding Guides')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
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
            ['label' => 'Total guides', 'value' => $totalGuides, 'tone' => 'bg-purple-50 text-purple-700'],
            ['label' => 'Published', 'value' => $publishedGuides, 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => 'Drafts', 'value' => $draftGuides, 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Archived', 'value' => $archivedGuides, 'tone' => 'bg-slate-100 text-slate-700'],
        ] as $card)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="inline-flex rounded-xl px-3 py-1 text-xs font-bold {{ $card['tone'] }}">{{ $card['label'] }}</div>
                <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
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
                                    <a href="{{ route('admin.onboarding-guides.edit', $guide) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Edit</a>
                                    <a href="{{ route('admin.onboarding-guides.history', $guide) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">History</a>
                                    @if($guide->status === \App\Models\OnboardingGuide::STATUS_PUBLISHED)
                                        <form method="POST" action="{{ route('admin.onboarding-guides.unpublish', $guide) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50">Unpublish</button>
                                        </form>
                                    @endif
                                    @if($guide->status !== \App\Models\OnboardingGuide::STATUS_ARCHIVED)
                                        <form method="POST" action="{{ route('admin.onboarding-guides.archive', $guide) }}" onsubmit="return confirm('Archive this onboarding guide? Its files and history will be retained.');">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">Archive</button>
                                        </form>
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
