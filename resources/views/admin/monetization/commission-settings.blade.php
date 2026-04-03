@extends('layouts.admin')

@section('title', 'Commission Settings')
@section('page-title', 'Commission Settings')

@section('content')
    <div class="space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Please fix the highlighted fields.</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="text-lg font-semibold text-gray-900">Commission Settings</h2>
            <p class="mt-1 text-sm text-gray-500">Configure global default and per-instructor commission rules for module sales.</p>

            <form method="POST" action="{{ route('admin.monetization.commission-settings.store') }}" class="mt-6 grid gap-4 md:grid-cols-2">
                @csrf

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Scope Type</label>
                    <select name="scope_type" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        <option value="global" @selected(old('scope_type') === 'global')>Global Default Commission</option>
                        <option value="instructor" @selected(old('scope_type') === 'instructor')>Instructor Override</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Override</label>
                    <select name="scope_id" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Select Instructor</option>
                        @foreach($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected((string) old('scope_id') === (string) $instructor->id)>
                                {{ $instructor->name }} ({{ $instructor->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Commission Percent</label>
                    <input type="number" name="commission_percent" step="0.01" min="0" max="100"
                           value="{{ old('commission_percent', '10.00') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Tax Basis</label>
                    <select name="tax_basis" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        <option value="gross" @selected(old('tax_basis', 'gross') === 'gross')>Gross</option>
                        <option value="net" @selected(old('tax_basis') === 'net')>Net</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Refund Policy</label>
                    <select name="refund_policy" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        <option value="disabled" @selected(old('refund_policy', 'disabled') === 'disabled')>Disabled</option>
                        <option value="platform_absorbs" @selected(old('refund_policy') === 'platform_absorbs')>Platform Absorbs</option>
                        <option value="proportional" @selected(old('refund_policy') === 'proportional')>Proportional</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', true))>
                    <label for="is_active" class="text-sm text-gray-700">Active</label>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Effective From</label>
                    <input type="datetime-local" name="effective_from"
                           value="{{ old('effective_from', now()->format('Y-m-d\TH:i')) }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Effective To</label>
                    <input type="datetime-local" name="effective_to"
                           value="{{ old('effective_to') }}"
                           class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Save Commission Policy
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Active and Historical Policies</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tax Basis</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Effective</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($policies as $policy)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    @if($policy->scope_type === 'global')
                                        Global Default Commission
                                    @else
                                        Instructor Override: {{ $policy->instructor?->name ?? 'Unknown' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ number_format((float) $policy->commission_percent, 2) }}%</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ strtoupper($policy->tax_basis) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    {{ optional($policy->effective_from)->format('M d, Y H:i') }}
                                    @if($policy->effective_to)
                                        - {{ optional($policy->effective_to)->format('M d, Y H:i') }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $policy->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $policy->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No commission policies configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
