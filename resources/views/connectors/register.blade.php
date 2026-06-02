@extends('layouts.learner-app')

@section('title', 'Register Connector')

@section('content')
<section class="max-w-4xl p-6 mx-auto bg-white border border-gray-200 shadow-sm rounded-2xl">
    <div class="mb-6">
        <p class="text-xs font-semibold tracking-wider text-purple-700 uppercase">Connectors</p>
        <h1 class="text-2xl font-bold text-gray-900">Register a connector</h1>
        <p class="mt-1 text-sm text-gray-500">Apply your existing connector</p>
    </div>

    <form
        method="POST"
        action="{{ route('connectors.store') }}"
        class="grid gap-5 sm:grid-cols-2"
        x-data="{
            cityCode: @js(old('city_code', '')),
            selectedBarangayCode: @js(old('barangay_code', '')),
            barangays: @js($barangays),
            isLoadingBarangays: false,
            async loadBarangays(cityCode) {
                this.cityCode = cityCode;
                this.selectedBarangayCode = '';
                this.barangays = [];

                if (!cityCode) {
                    return;
                }

                this.isLoadingBarangays = true;

                try {
                    const response = await fetch('/api/barangays/' + encodeURIComponent(cityCode));
                    this.barangays = response.ok ? await response.json() : [];
                } finally {
                    this.isLoadingBarangays = false;
                }
            }
        }"
    >
        @csrf
        <label class="sm:col-span-2">
            <span class="text-sm font-semibold text-gray-700">Connector / Organization Name</span>
            <input name="name" value="{{ old('name') }}" class="w-full mt-1 border-gray-300 rounded-lg" required>
            @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Category</span>
            <select name="category" class="w-full mt-1 border-gray-300 rounded-lg" required>
                <option value="">Select category</option>
                @foreach($categories as $value => $label)
                    <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Contact Number</span>
            <input name="contact_number" value="{{ old('contact_number') }}" class="w-full mt-1 border-gray-300 rounded-lg" required>
            @error('contact_number') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Organization Email</span>
            <input type="email" name="organization_email" value="{{ old('organization_email') }}" class="w-full mt-1 border-gray-300 rounded-lg">
            @error('organization_email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Website / Social Link</span>
            <input type="url" name="website_url" value="{{ old('website_url') }}" class="w-full mt-1 border-gray-300 rounded-lg">
            @error('website_url') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Cavite City / Municipality</span>
            <select name="city_code" x-model="cityCode" @change="loadBarangays($event.target.value)" class="w-full mt-1 border-gray-300 rounded-lg" required>
                <option value="">Select city</option>
                @foreach($cities as $city)
                    <option value="{{ $city->code }}" @selected(old('city_code') === $city->code)>{{ $city->name }}</option>
                @endforeach
            </select>
            @error('city_code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label>
            <span class="text-sm font-semibold text-gray-700">Barangay</span>
            <select name="barangay_code" x-model="selectedBarangayCode" class="w-full mt-1 border-gray-300 rounded-lg" required :disabled="!cityCode || isLoadingBarangays">
                <option value="">Select barangay</option>
                <option value="" x-show="isLoadingBarangays">Loading barangays...</option>
                <template x-for="barangay in barangays" :key="barangay.code">
                    <option :value="barangay.code" x-text="barangay.name"></option>
                </template>
            </select>
            @error('barangay_code') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="sm:col-span-2">
            <span class="text-sm font-semibold text-gray-700">Address Line</span>
            <input name="address_line" value="{{ old('address_line') }}" class="w-full mt-1 border-gray-300 rounded-lg" required>
            @error('address_line') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="sm:col-span-2">
            <span class="text-sm font-semibold text-gray-700">Organization Description</span>
            <textarea name="description" rows="3" class="w-full mt-1 border-gray-300 rounded-lg">{{ old('description') }}</textarea>
        </label>

        <label class="sm:col-span-2">
            <span class="text-sm font-semibold text-gray-700">Verification Notes</span>
            <textarea name="verification_notes" rows="3" class="w-full mt-1 border-gray-300 rounded-lg">{{ old('verification_notes') }}</textarea>
        </label>

        <div class="sm:col-span-2">
            <button class="rounded-lg bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800">Submit for Review</button>
        </div>
    </form>
</section>
@endsection
