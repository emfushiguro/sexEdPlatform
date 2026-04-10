@extends('layouts.admin')

@section('title', 'Commission Settings')
@section('page-title', 'Commission Settings')

@section('content')
    <div class="space-y-6" x-data="commissionWizardPage({
        storeUrl: @js(route('admin.monetization.commission-settings.store')),
        updateUrl: @js(route('admin.monetization.commission-settings.update', ['commissionPolicy' => '__ID__'])),
    })">
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Commission Settings</h2>
                    <p class="mt-1 text-sm text-gray-500">Configure global and per-instructor commission rules through a guided wizard.</p>
                </div>
                <button type="button" @click="openCreate()" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    New Commission Policy
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-xs">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-gray-500">Active and Historical Policies</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">No.</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scope</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Commission</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Tax Basis</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Effective</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($policies as $policy)
                            @php
                                $effectiveFrom = optional($policy->effective_from)->format('Y-m-d\TH:i');
                                $effectiveTo = optional($policy->effective_to)->format('Y-m-d\TH:i');
                            @endphp
                            <tr>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">
                                    @if($policy->scope_type === 'global')
                                        Global Default Commission
                                    @else
                                        Instructor Override: {{ $policy->instructor?->name ?? 'Unknown' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ number_format((float) $policy->commission_percent, 2) }}%</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ strtoupper((string) $policy->tax_basis) }}</td>
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
                                <td class="px-6 py-3 text-right">
                                    <button type="button"
                                            @click='openEdit(@js([
                                                "id" => $policy->id,
                                                "scope_type" => $policy->scope_type,
                                                "scope_id" => $policy->scope_id,
                                                "commission_percent" => (string) $policy->commission_percent,
                                                "tax_basis" => $policy->tax_basis,
                                                "refund_policy" => $policy->refund_policy,
                                                "is_active" => (bool) $policy->is_active,
                                                "effective_from" => $effectiveFrom,
                                                "effective_to" => $effectiveTo,
                                            ]))'
                                            class="inline-flex h-9 items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-semibold text-amber-700 hover:bg-amber-100">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">No commission policies configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="closeWizard()">
            <div class="absolute inset-0 bg-gray-900/50" @click="closeWizard()"></div>

            <div class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-lg font-bold text-gray-900" x-text="mode === 'edit' ? 'Update Commission Policy' : 'Create Commission Policy'"></h3>
                        <button type="button" @click="closeWizard()" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">&times;</button>
                    </div>
                    <div class="mt-3 flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <span :class="step >= 1 ? 'text-indigo-600' : ''">1. Scope</span>
                        <span :class="step >= 2 ? 'text-indigo-600' : ''">2. Commission Rules</span>
                        <span :class="step >= 3 ? 'text-indigo-600' : ''">3. Effective Window</span>
                    </div>
                </div>

                <form method="POST" :action="formAction()" class="p-6 space-y-6">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div x-show="step === 1" class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Scope Type</label>
                            <select name="scope_type" x-model="form.scope_type" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                                <option value="global">Global Default Commission</option>
                                <option value="instructor">Instructor Override</option>
                            </select>
                        </div>
                        <div x-show="form.scope_type === 'instructor'" x-cloak>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Instructor Override</label>
                            <select name="scope_id" x-model="form.scope_id" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                                <option value="">Select Instructor</option>
                                @foreach($instructors as $instructor)
                                    <option value="{{ $instructor->id }}">{{ $instructor->name }} ({{ $instructor->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div x-show="step === 2" x-cloak class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Commission Percent</label>
                            <input type="number" name="commission_percent" step="0.01" min="0" max="100" x-model="form.commission_percent" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Tax Basis</label>
                            <select name="tax_basis" x-model="form.tax_basis" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                                <option value="gross">Gross</option>
                                <option value="net">Net</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Refund Policy</label>
                            <select name="refund_policy" x-model="form.refund_policy" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                                <option value="disabled">Disabled</option>
                                <option value="platform_absorbs">Platform Absorbs</option>
                                <option value="proportional">Proportional</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <input type="checkbox" id="policy_is_active" name="is_active" value="1" x-model="form.is_active">
                            <label for="policy_is_active" class="text-sm text-gray-700">Active</label>
                        </div>
                    </div>

                    <div x-show="step === 3" x-cloak class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Effective From</label>
                            <input type="datetime-local" name="effective_from" x-model="form.effective_from" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Effective To</label>
                            <input type="datetime-local" name="effective_to" x-model="form.effective_to" class="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                            <p><span class="font-semibold">Scope:</span> <span x-text="form.scope_type === 'global' ? 'Global Default Commission' : 'Instructor Override'"></span></p>
                            <p class="mt-1"><span class="font-semibold">Commission:</span> <span x-text="form.commission_percent"></span>%</p>
                            <p class="mt-1"><span class="font-semibold">Tax Basis:</span> <span x-text="form.tax_basis.toUpperCase()"></span></p>
                            <p class="mt-1"><span class="font-semibold">Refund Policy:</span> <span x-text="form.refund_policy.replace(/_/g, ' ')"></span></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                        <button type="button" @click="prevStep()" x-show="step > 1" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back</button>
                        <span x-show="step === 1" class="w-16"></span>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="closeWizard()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="button" x-show="step < 3" @click="nextStep()" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Continue</button>
                            <button type="submit" x-show="step === 3" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" x-text="mode === 'edit' ? 'Update Policy' : 'Save Policy'"></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function commissionWizardPage(config) {
            return {
                open: false,
                mode: 'create',
                step: 1,
                policyId: null,
                routes: config,
                form: {
                    scope_type: 'global',
                    scope_id: '',
                    commission_percent: '10.00',
                    tax_basis: 'gross',
                    refund_policy: 'disabled',
                    is_active: true,
                    effective_from: '{{ now()->format('Y-m-d\\TH:i') }}',
                    effective_to: '',
                },
                openCreate() {
                    this.mode = 'create';
                    this.step = 1;
                    this.policyId = null;
                    this.form = {
                        scope_type: 'global',
                        scope_id: '',
                        commission_percent: '10.00',
                        tax_basis: 'gross',
                        refund_policy: 'disabled',
                        is_active: true,
                        effective_from: '{{ now()->format('Y-m-d\\TH:i') }}',
                        effective_to: '',
                    };
                    this.open = true;
                },
                openEdit(policy) {
                    this.mode = 'edit';
                    this.step = 1;
                    this.policyId = policy.id;
                    this.form = {
                        scope_type: policy.scope_type || 'global',
                        scope_id: policy.scope_id ? String(policy.scope_id) : '',
                        commission_percent: policy.commission_percent || '10.00',
                        tax_basis: policy.tax_basis || 'gross',
                        refund_policy: policy.refund_policy || 'disabled',
                        is_active: Boolean(policy.is_active),
                        effective_from: policy.effective_from || '',
                        effective_to: policy.effective_to || '',
                    };
                    this.open = true;
                },
                closeWizard() {
                    this.open = false;
                },
                nextStep() {
                    if (this.step < 3) {
                        this.step += 1;
                    }
                },
                prevStep() {
                    if (this.step > 1) {
                        this.step -= 1;
                    }
                },
                formAction() {
                    if (this.mode === 'edit' && this.policyId) {
                        return this.routes.updateUrl.replace('__ID__', this.policyId);
                    }

                    return this.routes.storeUrl;
                },
            };
        }
    </script>
@endsection
