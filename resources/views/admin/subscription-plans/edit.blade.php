@extends('layouts.admin')

@section('title', 'Edit: ' . $subscriptionPlan->name)
@section('page-title', 'Edit Plan')

@section('content')
    {{-- Back link --}}
    <div class="mb-5">
        <a
            href="{{ route('admin.subscription-plans.show', $subscriptionPlan) }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-500 transition-colors hover:text-brand-500"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to {{ $subscriptionPlan->name }}
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs">
            <h2 class="mb-6 text-lg font-semibold text-gray-900">Edit Plan Details</h2>

            <form method="POST" action="{{ route('admin.subscription-plans.update', $subscriptionPlan) }}" x-data="planForm()">
                @csrf
                @method('PUT')

                @if($errors->any())
                    <div class="mb-5 rounded-xl border border-error-200 bg-error-50 p-4">
                        <p class="mb-1 text-sm font-medium text-error-700">Please fix the following errors:</p>
                        <ul class="list-inside list-disc space-y-1 text-sm text-error-600">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Name --}}
                <div class="mb-5">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Plan Name <span class="text-error-500">*</span></label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $subscriptionPlan->name) }}"
                        required
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 @error('name') border-error-400 @enderror"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="mb-5">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        name="description"
                        rows="3"
                        class="w-full resize-none rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                    >{{ old('description', $subscriptionPlan->description) }}</textarea>
                </div>

                {{-- Price & Trial --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Price (₱/month) <span class="text-error-500">*</span></label>
                        <input
                            type="number"
                            name="price"
                            value="{{ old('price', $subscriptionPlan->price) }}"
                            min="0"
                            step="0.01"
                            required
                            class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30 @error('price') border-error-400 @enderror"
                        />
                        @error('price')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Trial Days</label>
                        <input
                            type="number"
                            name="trial_days"
                            value="{{ old('trial_days', $subscriptionPlan->trial_days ?? 0) }}"
                            min="0"
                            max="365"
                            class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                        />
                    </div>
                </div>

                {{-- Max Modules & Sort Order --}}
                <div class="mb-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Max Modules <span class="text-xs text-gray-400">(0 = unlimited)</span></label>
                        <input
                            type="number"
                            name="max_modules"
                            value="{{ old('max_modules', $subscriptionPlan->max_modules ?? 0) }}"
                            min="0"
                            class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Sort Order</label>
                        <input
                            type="number"
                            name="sort_order"
                            value="{{ old('sort_order', $subscriptionPlan->sort_order) }}"
                            min="0"
                            class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                        />
                    </div>
                </div>

                {{-- Features --}}
                <div class="mb-5">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Features</label>
                    <div x-ref="featureList" class="space-y-2">
                        @php
                            $existingFeatures = old('feature_keys', $subscriptionPlan->features ?? ['']);
                            if (empty($existingFeatures)) {
                                $existingFeatures = [''];
                            }
                        @endphp
                        @foreach($existingFeatures as $feature)
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    name="feature_keys[]"
                                    value="{{ $feature }}"
                                    placeholder="e.g. Unlimited quizzes"
                                    class="flex-1 rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                                />
                                <button
                                    type="button"
                                    @click="removeRow($el.closest('div'))"
                                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-error-50 hover:text-error-500"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button
                        type="button"
                        @click="addRow"
                        class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-brand-500 hover:text-brand-600"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Feature
                    </button>
                </div>

                {{-- Active toggle --}}
                <div class="mb-6 flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0"/>
                    <input
                        type="checkbox"
                        name="is_active"
                        id="is_active"
                        value="1"
                        {{ old('is_active', $subscriptionPlan->is_active) ? 'checked' : '' }}
                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500"
                    />
                    <label for="is_active" class="text-sm font-medium text-gray-700">Active (visible to learners)</label>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-3">
                    <button
                        type="submit"
                        class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-theme-xs transition-colors hover:bg-brand-600"
                    >
                        Save Changes
                    </button>
                    <a
                        href="{{ route('admin.subscription-plans.show', $subscriptionPlan) }}"
                        class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                    >
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function planForm() {
    return {
        addRow() {
            const list = this.$refs.featureList;
            const div = document.createElement('div');
            div.className = 'flex gap-2';
            div.innerHTML = `
                <input type="text" name="feature_keys[]" placeholder="e.g. Unlimited quizzes"
                    class="flex-1 rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-500/30"/>
                <button type="button" onclick="this.closest('div').remove()"
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            list.appendChild(div);
        },
        removeRow(el) {
            if (this.$refs.featureList.children.length > 1) {
                el.remove();
            }
        }
    };
}
</script>
@endpush
