@extends('layouts.instructor-app')

@section('content')
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if(($isRestricted ?? false) === true)
                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                            <p class="text-sm font-semibold text-rose-900">Module actions are temporarily restricted</p>
                            <p class="mt-1 text-xs text-rose-700">{{ $restrictionMessage }}</p>
                            <p class="mt-1 text-xs text-rose-700">Restriction ends: {{ optional($restrictionProfile?->restriction_ends_at)->toDayDateTimeString() ?? 'until further notice' }}</p>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('instructor.modules.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Thumbnail Image</label>
                            <input type="file" name="thumbnail" accept="image/*"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                            <p class="mt-1 text-sm text-gray-500">Recommended: 800x450px, max 2MB</p>
                            @error('thumbnail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Age Bracket <span class="text-red-500">*</span></label>
                            <select name="age_bracket" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select target age group</option>
                                <option value="kids" {{ old('age_bracket') === 'kids' ? 'selected' : '' }}>Kids (5-12 years)</option>
                                <option value="teens" {{ old('age_bracket') === 'teens' ? 'selected' : '' }}>Teens (13-17 years)</option>
                                <option value="adults" {{ old('age_bracket') === 'adults' ? 'selected' : '' }}>Adults (18+ years)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the age group this module is designed for</p>
                            @error('age_bracket')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Enrollment Mode <span class="text-red-500">*</span></label>
                            <div class="space-y-3">
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="radio" name="enrollment_mode" value="auto" 
                                        {{ old('enrollment_mode', 'auto') === 'auto' ? 'checked' : '' }}
                                        class="mt-1 h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Open Enrollment (Auto-approve)</span>
                                        <p class="text-sm text-gray-500">Learners can enroll immediately and access module content right away</p>
                                    </div>
                                </label>
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition">
                                    <input type="radio" name="enrollment_mode" value="manual" 
                                        {{ old('enrollment_mode') === 'manual' ? 'checked' : '' }}
                                        class="mt-1 h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300">
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Manual Approval (Gated Access)</span>
                                        <p class="text-sm text-gray-500">You must review and approve each enrollment request before learners can access content</p>
                                    </div>
                                </label>
                            </div>
                            @error('enrollment_mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Access Type <span class="text-red-500">*</span></label>
                            <select name="access_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" required>
                                <option value="free" {{ old('access_type', 'free') === 'free' ? 'selected' : '' }}>Free</option>
                                <option value="paid" {{ old('access_type') === 'paid' ? 'selected' : '' }}>Paid</option>
                            </select>
                            @error('access_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mb-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Price Amount (PHP)</label>
                                <input type="number" name="price_amount" min="0.01" step="0.01" value="{{ old('price_amount') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('price_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Price Currency</label>
                                <input type="text" name="price_currency" value="{{ old('price_currency', 'PHP') }}" maxlength="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
                                @error('price_currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        @if(!empty($effectiveCommissionPolicy))
                            <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3">
                                <p class="text-sm font-semibold text-indigo-900">
                                    Platform commission currently applied to your paid modules: {{ number_format((float) $effectiveCommissionPolicy['commission_percent'], 2) }}%
                                </p>
                                <p class="mt-1 text-xs text-indigo-800">
                                    Estimated net earnings per sale: Price - (Price x {{ number_format((float) $effectiveCommissionPolicy['commission_percent'], 2) }}%).
                                </p>
                            </div>
                        @endif

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Enrollment Limit</label>
                            <input type="number" name="enrollment_limit" min="1" max="20" value="{{ old('enrollment_limit') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500" placeholder="Leave empty for unlimited">
                            <p class="mt-1 text-xs text-gray-500">Temporary cap: up to 20 learners per module.</p>
                            @error('enrollment_limit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex items-center justify-end gap-4 mt-6">
                            <button type="submit" name="action" value="draft" 
                                @disabled(($isRestricted ?? false) === true)
                                class="bg-gray-600 hover:bg-gray-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-2 px-6 rounded transition">
                                 Save as Draft
                            </button>
                            <button type="submit" name="action" value="publish" 
                                @disabled(($isRestricted ?? false) === true)
                                class="bg-brand-600 hover:bg-brand-700 disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed text-white font-bold py-2 px-6 rounded transition">
                                Create & Publish
                            </button>
                        </div>
                    </form>
                </div>
            </div>
@endsection
