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
    @include('admin.moderation.partials.workspace-styles')
    <div class="moderation-workspace space-y-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.moderation-suspensions.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back to Suspension Dashboard
            </a>
        </div>

        @if($temporarySuspensionDuration['can_change'])
        <section id="change-duration" class="rounded-[30px] border border-brand-100 bg-white p-6 shadow-theme-xs">
            <h2 class="text-xl font-bold text-gray-900">Change duration</h2>
            <p class="mt-2 text-sm text-gray-600">Enter the total duration from the original start. This does not restart the suspension.</p>
            <p class="mt-2 text-sm text-gray-600">Current end: {{ $suspension->ends_at->format('M d, Y g:i A T') }}</p>
            @if($errors->any())<div role="alert" class="mt-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('admin.moderation-suspensions.duration.update', $suspension) }}" class="mt-4 space-y-4"
                x-data="{ amount: {{ Illuminate\Support\Js::from(old('duration_amount', $temporarySuspensionDuration['amount'] ?? 1)) }}, unit: {{ Illuminate\Support\Js::from(old('duration_unit', $temporarySuspensionDuration['unit'] ?? 'hours')) }}, start: {{ Illuminate\Support\Js::from($suspension->starts_at->toIso8601String()) }} }"
                data-confirm-submit data-confirm-title="Change suspension duration?" data-confirm-icon="warning" data-confirm-button="Confirm duration change"
                :data-confirm-text="'New end time: ' + new Date(new Date(start).getTime() + Number(amount) * (unit === 'days' ? 86400000 : 3600000)).toLocaleString(undefined, {timeZoneName: 'short'})">
                @csrf @method('PATCH')
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm font-semibold">Total duration
                        <input name="duration_amount" type="number" min="1" :max="unit === 'hours' ? 8760 : 365" step="1" required x-model="amount" class="mt-1 w-full rounded-xl border-gray-300">
                    </label>
                    <label class="block text-sm font-semibold">Unit
                        <select name="duration_unit" x-model="unit" class="mt-1 w-full rounded-xl border-gray-300"><option value="hours">Hours</option><option value="days">Days</option></select>
                    </label>
                </div>
                <p class="text-sm text-gray-700">Proposed end (your local time): <span x-text="Number(amount) > 0 ? new Date(new Date(start).getTime() + Number(amount) * (unit === 'days' ? 86400000 : 3600000)).toLocaleString(undefined, {timeZoneName: 'short'}) : 'Enter a duration'"></span></p>
                <label class="block text-sm font-semibold">Reason (admin audit only)
                    <textarea name="reason" required minlength="10" maxlength="2000" rows="3" class="mt-1 w-full rounded-xl border-gray-300">{{ old('reason') }}</textarea>
                </label>
                <button class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Review duration change</button>
            </form>
        </section>
        @endif

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
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500">Action</th>
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
                                <td class="px-6 py-4 text-right"><a href="{{ route('admin.moderation-appeals.show', $appeal) }}" data-testid="history-appeal" class="inline-flex min-h-11 items-center whitespace-nowrap rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-brand-100">{{ $appeal->status === 'pending_review' ? 'Review appeal' : 'View appeal' }}</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No appeals have been submitted for this suspension.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
