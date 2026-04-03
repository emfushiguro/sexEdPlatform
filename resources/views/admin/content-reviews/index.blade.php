@extends('layouts.admin')

@section('content')
    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">Moderation Queue</p>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Pending Content Reviews</h1>
            <p class="mt-1 text-sm text-gray-500">Review instructor-submitted modules before publishing them to learners.</p>
        </div>

        <div class="overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(115,13,177,0.16),_transparent_34%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-5">
                <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Pending Content Reviews Table</h2>
                <p class="mt-1 text-sm text-gray-500">Use Review to inspect content and Archive for withdrawn or invalid submissions.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module Thumbnail</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Module Name</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Publisher</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Submission Date</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($reviewRequests as $reviewRequest)
                            @php
                                $module = $reviewRequest->module;
                                $thumbnailUrl = $module?->thumbnail_url;
                                $publisherName = $reviewRequest->submitter?->name ?? 'Unknown Instructor';
                            @endphp
                            <tr class="transition hover:bg-violet-50/30">
                                <td class="px-6 py-4">
                                    @if($thumbnailUrl)
                                        <img src="{{ $thumbnailUrl }}" alt="{{ $reviewRequest->module_title }} thumbnail" class="h-14 w-20 rounded-xl border border-gray-200 object-cover">
                                    @else
                                        <div class="flex h-14 w-20 items-center justify-center rounded-xl border border-dashed border-gray-300 bg-gray-50 text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            No Image
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900">{{ $reviewRequest->module_title }}</p>
                                    <p class="text-xs text-gray-500">Request #{{ $reviewRequest->id }}</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $publisherName }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700">Pending Review</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ optional($reviewRequest->submitted_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.content-reviews.show', $reviewRequest) }}"
                                           class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
                                           title="Review"
                                           aria-label="Review module">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('admin.content-reviews.archive', $reviewRequest) }}" onsubmit="return confirm('Archive this submission from the moderation queue?');">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                title="Archive"
                                                aria-label="Archive submission">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M4 8l1 11a2 2 0 002 2h10a2 2 0 002-2l1-11M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center">
                                    <div class="mx-auto max-w-sm">
                                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-4 text-sm font-semibold text-gray-900">No pending module reviews</h3>
                                        <p class="mt-1 text-sm text-gray-500">Instructor submissions that need moderation will appear here.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                {{ $reviewRequests->links() }}
            </div>
        </div>
    </div>
@endsection
