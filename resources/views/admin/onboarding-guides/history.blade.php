@extends('layouts.admin')

@section('title', 'Onboarding Guide History')
@section('page-title', 'Onboarding Guide History')

@section('content')
<div class="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.onboarding-guides.edit', $guide) }}" class="text-sm font-semibold text-brand-700 hover:underline">Back to guide</a>
            <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900">{{ $guide->target_role }} guide history</h1>
            <p class="mt-1 text-sm text-gray-500">Immutable versions and direct state rows for {{ $guide->guide_key }}.</p>
        </div>
        <span class="inline-flex rounded-full border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold text-gray-700">{{ str($guide->status)->headline() }}</span>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <caption class="sr-only">Immutable onboarding guide versions</caption>
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-4 font-semibold">Version</th>
                        <th class="px-5 py-4 font-semibold">Publication</th>
                        <th class="px-5 py-4 font-semibold">Re-completion</th>
                        <th class="px-5 py-4 font-semibold">Video</th>
                        <th class="px-5 py-4 font-semibold">Direct outcomes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($guide->versions as $version)
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">Version {{ $version->version_number }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $version->title }}</p>
                            </td>
                            <td class="px-5 py-4 text-gray-700">{{ $version->published_at?->format('M j, Y g:i A') ?? 'Draft' }}</td>
                            <td class="px-5 py-4 text-gray-700">{{ $version->requires_recompletion ? 'Required' : 'Informational' }}</td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                <span class="block">{{ number_format($version->video_size / 1048576, 1) }} MB</span>
                                <span class="block">{{ $version->video_mime_type }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-gray-600">
                                <span class="block"><strong class="text-gray-900">Direct completions:</strong> {{ $version->direct_completion_count }}</span>
                                <span class="block"><strong class="text-gray-900">Direct dismissals without completion:</strong> {{ $version->direct_dismissal_count }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">No versions exist for this guide.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
