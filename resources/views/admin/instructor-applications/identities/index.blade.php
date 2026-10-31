@extends('layouts.admin')

@section('title', 'Instructor Identity Review')
@section('page-title', 'Instructor Identity Review')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('admin.instructor-applications.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">← Instructor Applications</a>
            <h1 class="mt-3 text-2xl font-bold text-gray-900">Identity Review</h1>
            <p class="mt-1 text-sm text-gray-600">Stage One review for direct instructor applicants.</p>
        </div>
        <div class="flex gap-2 text-sm">
            <a href="{{ route('admin.instructor-applications.identities.index', ['status' => 'pending_review']) }}" @if($status === 'pending_review') aria-current="page" @endif class="rounded-full px-3 py-1.5 {{ $status === 'pending_review' ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-700' }}">Pending ({{ $pendingCount }})</a>
            <a href="{{ route('admin.instructor-applications.identities.index', ['status' => 'action_required']) }}" @if($status === 'action_required') aria-current="page" @endif class="rounded-full px-3 py-1.5 {{ $status === 'action_required' ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-700' }}">Action required ({{ $actionRequiredCount }})</a>
            <a href="{{ route('admin.instructor-applications.identities.index', ['status' => 'approved']) }}" @if($status === 'approved') aria-current="page" @endif class="rounded-full px-3 py-1.5 {{ $status === 'approved' ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-700' }}">Approved</a>
            <a href="{{ route('admin.instructor-applications.identities.index', ['status' => 'rejected']) }}" @if($status === 'rejected') aria-current="page" @endif class="rounded-full px-3 py-1.5 {{ $status === 'rejected' ? 'bg-brand-700 text-white' : 'bg-gray-100 text-gray-700' }}">Rejected</a>
        </div>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-4">Applicant</th>
                        <th class="px-5 py-4">Source</th>
                        <th class="px-5 py-4">Persona</th>
                        <th class="px-5 py-4">Round</th>
                        <th class="px-5 py-4">Submitted</th>
                        <th class="px-5 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($cases as $case)
                        @php($attempt = $case->personaInquiries->first())
                        <tr>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-gray-900">{{ $case->user?->full_name ?? $case->user?->name }}</p>
                                <p class="text-gray-500">{{ $case->user?->email }}</p>
                            </td>
                            <td class="px-5 py-4 capitalize">{{ str_replace('_', ' ', $case->source) }}</td>
                            <td class="px-5 py-4">
                                <span class="font-medium">{{ $attempt?->persona_status ?: 'Not started' }}</span>
                                @if($case->sandbox_manual_review)<span class="ml-1 text-amber-700">· Sandbox review</span>@endif
                            </td>
                            <td class="px-5 py-4">{{ $case->submission_round }}</td>
                            <td class="px-5 py-4">{{ $case->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.instructor-applications.identities.show', $case) }}" class="font-semibold text-brand-700 hover:underline">Open review</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No identity cases match this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($cases->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">{{ $cases->links() }}</div>
        @endif
    </section>
</div>
@endsection
