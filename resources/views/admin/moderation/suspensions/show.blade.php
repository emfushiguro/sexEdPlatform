@extends('layouts.admin')

@section('title', 'Suspension Details')
@section('page-title', 'Suspension Details')

@php
    $severityRaw = $suspension->enforcementAction?->severity_level;
    $severityValue = is_object($severityRaw) ? $severityRaw->value : (string) ($severityRaw ?? 'unknown');
    $actionTypeRaw = $suspension->enforcementAction?->action_type;
    $actionTypeValue = is_object($actionTypeRaw) ? $actionTypeRaw->value : (string) ($actionTypeRaw ?? 'n/a');
@endphp

@section('content')
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.moderation-suspensions.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Suspension Dashboard
            </a>
        </div>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <h2 class="text-xl font-bold text-gray-900">Suspension Record #{{ $suspension->id }}</h2>
                <p class="mt-1 text-sm text-gray-600">Case {{ $suspension->moderationCase?->case_reference_code ?? 'N/A' }} with enforcement and appeal timeline.</p>
            </div>

            <div class="grid gap-4 px-6 py-6 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">User</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $suspension->user?->name ?? 'Unknown user' }}</p>
                    <p class="text-xs text-gray-500">{{ $suspension->user?->email ?? 'No email' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Role</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($suspension->user?->role ?? 'unknown')) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Action</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', ucfirst($actionTypeValue)) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Severity</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $severityValue)) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Trigger</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) ($suspension->enforcementAction?->trigger_type ?? 'manual')) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ ucfirst((string) $suspension->status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Appeal Status</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', (string) $suspension->appeal_status) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Duration</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ optional($suspension->starts_at)?->format('M d, Y g:i A') ?? '-' }}
                        -
                        {{ optional($suspension->ends_at)?->format('M d, Y g:i A') ?? 'Permanent' }}
                    </p>
                </div>
            </div>

            @if(!empty($suspension->notes))
                <div class="border-t border-gray-100 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Suspension Notes</p>
                    <p class="mt-2 text-sm text-gray-700">{{ $suspension->notes }}</p>
                </div>
            @endif
        </section>

        <section class="rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-brand-100 px-6 py-5">
                <h3 class="text-lg font-bold text-gray-900">Appeal History</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-brand-50/45">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Appeal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Submitted</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Reviewed By</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Messages</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($appeals as $appeal)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit((string) $appeal->appeal_reason, 120) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-700">{{ str_replace('_', ' ', (string) $appeal->status) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ optional($appeal->submitted_at)?->format('M d, Y g:i A') ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $appeal->reviewedByAdmin?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $appeal->threadMessages->count() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No appeals have been submitted for this suspension.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
