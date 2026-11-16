@extends('layouts.admin')
@section('title', 'Moderation Appeals')
@section('page-title', 'Moderation Appeals')

@section('content')
@include('admin.moderation.partials.workspace-styles')
<div class="moderation-workspace space-y-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-700">Moderation workspace</p>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">Appeal review queue</h2>
            <p class="mt-2 text-sm text-gray-600">Review user appeals and keep each suspension decision in context.</p>
        </div>
        <a href="{{ route('admin.moderation-suspensions.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to suspensions</a>
    </header>

    <section class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('admin.moderation-appeals.index') }}" class="grid gap-4 border-b border-gray-100 bg-gray-50 p-4 sm:grid-cols-2 lg:grid-cols-4 sm:p-6" data-testid="admin-table-filter-bar">
            <label class="block min-w-0 lg:col-span-2">
                <span class="mb-2 block text-sm font-semibold text-gray-700">Search appeals</span>
                <input type="search" name="search" value="{{ $search }}" placeholder="User, email or appeal reason" class="min-h-11 w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-600 focus:ring-purple-600">
            </label>
            <label class="block min-w-0">
                <span class="mb-2 block text-sm font-semibold text-gray-700">Appeal status</span>
                <select name="status" class="min-h-11 w-full rounded-xl border-gray-300 bg-white px-3 py-2 text-sm focus:border-purple-600 focus:ring-purple-600">
                    <option value="all" @selected($status === '')>All statuses</option>
                    @foreach(['pending_review' => 'Pending review', 'clarification_requested' => 'Awaiting clarification', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end gap-2">
                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">Apply</button>
                <a href="{{ route('admin.moderation-appeals.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-4 sm:px-6">
            <h3 class="font-semibold text-gray-900">{{ $status === 'pending_review' ? 'Awaiting your review' : 'Appeals' }}</h3>
            <span class="text-sm text-gray-600">{{ number_format($appeals->total()) }} {{ $appeals->total() === 1 ? 'appeal' : 'appeals' }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr>
                    <th class="px-4 py-3 sm:px-6">Appeal</th><th class="px-4 py-3 sm:px-6">User</th><th class="px-4 py-3 sm:px-6">Status</th><th class="px-4 py-3 sm:px-6">Submitted</th><th class="px-4 py-3 text-right sm:px-6">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($appeals as $appeal)
                    <tr class="hover:bg-purple-50/40">
                        <td class="whitespace-nowrap px-4 py-4 text-sm font-semibold text-gray-600 sm:px-6">#{{ $appeal->id }}</td>
                        <td class="px-4 py-4 sm:px-6"><p class="max-w-xs break-words text-sm font-semibold text-gray-900">{{ $appeal->user?->name ?? 'Unknown user' }}</p><p class="max-w-xs break-all text-xs text-gray-500">{{ $appeal->user?->email ?? 'No email' }}</p></td>
                        <td class="px-4 py-4 sm:px-6">@include('admin.moderation.partials.appeal-status', ['appealStatusValue' => $appeal->status])</td>
                        <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 sm:px-6">{{ $appeal->submitted_at?->format('M d, Y g:i A') ?? 'Not available' }}</td>
                        <td class="px-4 py-4 text-right sm:px-6"><a href="{{ route('admin.moderation-appeals.show', $appeal) }}" class="inline-flex min-h-11 items-center justify-center whitespace-nowrap rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100">{{ $appeal->status === 'pending_review' ? 'Review appeal' : 'View appeal' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center">
                        <p class="font-semibold text-gray-900">{{ $search === '' && $status === 'pending_review' ? 'No pending appeals' : 'No appeals match your filters' }}</p>
                        <p class="mt-2 text-sm text-gray-500">{{ $search === '' && $status === 'pending_review' ? 'You are up to date. Previous appeals remain available in the full queue.' : 'Try a different search or status to find the appeal.' }}</p>
                        <a href="{{ route('admin.moderation-appeals.index', ['status' => 'all']) }}" data-testid="all-appeals" class="mt-4 inline-flex min-h-11 items-center rounded-xl border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-50">{{ $search === '' && $status === 'pending_review' ? 'View all appeals' : 'Clear filters and view all appeals' }}</a>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @include('admin.partials.table-pagination-footer', ['paginator' => $appeals->links()])
    </section>
</div>
@endsection
