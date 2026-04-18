@extends('layouts.admin')

@section('content')
@php
    $totalPending = \App\Models\ModuleReviewRequest::whereIn('status', ['submitted', 'in_review'])->count();
    $newSubmissions = \App\Models\ModuleReviewRequest::where('status', 'submitted')->count();
    $underReview = \App\Models\ModuleReviewRequest::where('status', 'in_review')->count();
    $recentlyApproved = \App\Models\ModuleReviewRequest::where('status', 'approved')->where('updated_at', '>=', now()->subDays(7))->count();
@endphp
    <div class="space-y-6" x-data="moduleReviewQueue({
        search: @js($search ?? ''),
        status: @js($statusFilter ?? ''),
        instructor: @js(($instructorFilter ?? 0) > 0 ? (string) $instructorFilter : ''),
        submittedDate: @js($submittedDate ?? ''),
    })">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
            <article class="min-h-[116px] rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-700">Total Pending</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 via-brand-700 to-brand-900 text-white shadow-lg shadow-brand-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900">{{ number_format($totalPending) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-600">New Submissions</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 via-brand-600 to-brand-800 text-white shadow-lg shadow-brand-200">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900">{{ number_format($newSubmissions) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-800">Under Review</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 text-white shadow-lg shadow-brand-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900">{{ number_format($underReview) }}</p>
            </article>
            <article class="min-h-[116px] rounded-[28px] border border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70 p-5 shadow-theme-xs">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-900">Approved (7d)</p>
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-700 via-brand-800 to-brand-900 text-white shadow-lg shadow-brand-300">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-4xl leading-none font-bold text-gray-900">{{ number_format($recentlyApproved) }}</p>
            </article>
        </section>

        <!-- Content Reviews Filters -->
        <header class="relative flex flex-col items-center justify-between gap-6 overflow-hidden rounded-[30px] border border-gray-200 bg-[radial-gradient(136.21%_427.05%_at_16.03%_57.51%,_#FFFFFF_2.22%,_#F3F4F6_31.54%,_#FFFFFF_58.49%,_#F3F4F6_87.97%,_#FFFFFF_100%)] px-6 py-6 shadow-theme-xs mb-6 sm:flex-row md:px-10">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Review Queue Filters</h1>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center xl:w-3/4 justify-end">
                <div class="relative w-full sm:w-64 max-w-sm">
                    <label class="sr-only">Search</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text"
                           x-model.debounce.500ms="filters.search"
                           placeholder="Module title or instructor..."
                           class="block w-full rounded-full border-gray-300 pl-10 focus:border-brand-500 focus:ring-brand-500 sm:text-sm">
                </div>
                
                <select x-model="filters.status" class="w-full rounded-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 sm:w-auto sm:text-sm">
                    <option value="">All statuses</option>
                    <option value="submitted">Submitted</option>
                    <option value="in_review">Under Review</option>
                </select>

                <select x-model="filters.instructor" class="w-full rounded-full border-gray-300 focus:border-brand-500 focus:ring-brand-500 sm:w-auto sm:text-sm">
                    <option value="">All instructors</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                    @endforeach
                </select>

                <button type="button" @click="resetFilters()" class="text-sm font-medium text-brand-600 hover:text-brand-500 shrink-0 border border-gray-200 bg-white rounded-full px-3 py-1.5 shadow-sm min-w-max">
                    Clear
                </button>
            </div>
        </header>

        <div class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">No.</th>
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
                                $rowNumber = ($reviewRequests->firstItem() ?? 1) + $loop->index;
                                $statusLabel = $reviewRequest->status === 'submitted' ? 'Submitted' : 'Under Review';
                                $submittedDateValue = optional($reviewRequest->submitted_at)->format('Y-m-d');
                            @endphp
                            <tr class="transition hover:bg-brand-50/30"
                                x-show="matchesRow(@js(strtolower((string) $reviewRequest->module_title)), @js(strtolower((string) $publisherName)), @js((string) $reviewRequest->status), @js((string) $reviewRequest->submitted_by), @js((string) $submittedDateValue))">
                                <td class="px-6 py-4 text-sm font-semibold text-gray-500">{{ $rowNumber }}</td>
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
                                    @if($reviewRequest->status === 'submitted')
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Submitted</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-700">Under Review</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ optional($reviewRequest->submitted_at)->format('M d, Y h:i A') }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($reviewRequest->status === 'submitted')
                                        <form method="POST" action="{{ route('admin.content-reviews.start-review', $reviewRequest) }}">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex h-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"
                                                title="Mark as under review"
                                                aria-label="Mark as under review">
                                                Start Review
                                            </button>
                                        </form>
                                        @endif

                                        <a href="{{ route('admin.content-reviews.show', $reviewRequest) }}"
                                           class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
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
                                                class="inline-flex items-center justify-center w-10 h-10 transition border rounded-2xl border-brand-200 bg-white hover:bg-brand-50 text-gray-700"
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
                                <td colspan="7" class="px-6 py-14 text-center">
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

    <script>
        function moduleReviewQueue(initial) {
            return {
                initial,
                filters: {
                    search: initial.search || '',
                    status: initial.status || '',
                    instructor: initial.instructor === '0' ? '' : (initial.instructor || ''),
                    submittedDate: initial.submittedDate || '',
                },
                matchesRow(moduleTitle, instructorName, status, instructorId, submittedDate) {
                    const search = (this.filters.search || '').trim().toLowerCase();
                    const statusMatch = !this.filters.status || this.filters.status === status;
                    const instructorMatch = !this.filters.instructor || this.filters.instructor === String(instructorId || '');
                    const dateMatch = !this.filters.submittedDate || this.filters.submittedDate === String(submittedDate || '');
                    const searchBlob = `${moduleTitle} ${instructorName} ${status}`;
                    const searchMatch = !search || searchBlob.includes(search);

                    return statusMatch && instructorMatch && dateMatch && searchMatch;
                },
                resetFilters() {
                    this.filters.search = '';
                    this.filters.status = '';
                    this.filters.instructor = '';
                    this.filters.submittedDate = '';
                },
            };
        }
    </script>
@endsection
