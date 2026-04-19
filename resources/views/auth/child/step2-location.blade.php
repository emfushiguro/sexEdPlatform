<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Logo" class="h-20 w-auto mx-auto mb-3">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Conscious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Where are you?</h2>
            <p class="text-white/80 text-lg max-w-xs">Location helps us show age-appropriate content for your area.</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Set Up Info',    'active' => false, 'done' => true],
        ['label' => 'Where Are You?', 'active' => true,  'done' => false],
        ['label' => 'Login Details',  'active' => false, 'done' => false],
        ['label' => 'All Set!',       'active' => false, 'done' => false],
    ]" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-purple-900">Child's Location</h1>
        <p class="mt-1 text-sm text-gray-600">Select the city and barangay where your child lives.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
            <h3 class="text-sm font-medium text-red-800 mb-1">Please correct the following errors:</h3>
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('parent.create-child.location.store') }}"
          x-data="{
              cityCode: '{{ old('city_code', $preFilledCity ?? '') }}',
              selectedBarangayCode: '{{ old('barangay_code', $preFilledBarangay ?? '') }}',
              barangays: [],
              loading: false,
              lastRequestToken: '',
              loadError: '',
              handleCityChange(code) {
                  this.cityCode = String(code || '').trim();
                  this.selectedBarangayCode = '';
                  this.barangays = [];
                  this.loadError = '';
                  this.loadBarangays(this.cityCode);
              },
              canSubmit() {
                  return Boolean(this.cityCode)
                      && Boolean(this.selectedBarangayCode)
                      && !this.loading
                      && this.loadError === '';
              },
              async loadBarangays(code) {
                  code = String(code || '').trim();

                  if (!code) {
                      this.barangays = [];
                      this.selectedBarangayCode = '';
                      this.loadError = '';
                      return;
                  }

                  this.cityCode = code;
                  this.loadError = '';

                  const requestToken = `${Date.now()}-${Math.random()}`;
                  this.lastRequestToken = requestToken;
                  this.loading = true;
                  try {
                      const res = await fetch('/api/barangays/' + encodeURIComponent(code), {
                          headers: { 'Accept': 'application/json' },
                      });

                      if (!res.ok) {
                          throw new Error('Failed to load barangays');
                      }

                      const data = await res.json();

                      if (requestToken !== this.lastRequestToken) {
                          return;
                      }

                      this.barangays = Array.isArray(data) ? data : [];

                      if (this.selectedBarangayCode && !this.barangays.some((b) => b.code === this.selectedBarangayCode)) {
                          this.selectedBarangayCode = '';
                      }
                  } catch (error) {
                      if (requestToken !== this.lastRequestToken) {
                          return;
                      }

                      this.barangays = [];
                      this.selectedBarangayCode = '';
                      this.loadError = 'Unable to load barangays. Please select the city again.';
                      console.error(error);
                  } finally {
                      if (requestToken === this.lastRequestToken) {
                          this.loading = false;
                      }
                  }
              }
          }"
          x-init="if (cityCode) loadBarangays(cityCode)">
        @csrf

        @php
            $locationPrefill = !empty($preFilledCity ?? '');
            $locSelectClass  = 'w-full px-3 py-2 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition ' .
                ($locationPrefill ? 'bg-purple-50 border border-purple-300' : 'bg-gray-50 border border-gray-200');
        @endphp

        @if($preFilledCity ?? false)
            <div class="flex items-center gap-2 bg-purple-50 border border-purple-200 rounded-lg px-3 py-2 mb-5">
                <svg class="w-4 h-4 text-purple-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-purple-800">Pre-filled from your location — assuming same household. Modify if needed.</p>
            </div>
        @endif

        {{-- City --}}
        <div class="mb-4">
            <label for="city_code" class="block text-sm font-medium text-gray-700 mb-1">
                Municipality / City (Cavite) <span class="text-red-500">*</span>
            </label>
            <select id="city_code" name="city_code" required
                    x-model="cityCode"
                    @change="handleCityChange($event.target.value)"
                    class="{{ $locSelectClass }}">
                <option value="">Select municipality / city</option>
                @foreach($cities as $city)
                    <option value="{{ $city->code }}" {{ old('city_code', $preFilledCity ?? '') == $city->code ? 'selected' : '' }}>
                        {{ $city->name }}
                    </option>
                @endforeach
            </select>
            @error('city_code')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Barangay --}}
        <div class="mb-6">
            <label for="barangay_code" class="block text-sm font-medium text-gray-700 mb-1">
                Barangay <span class="text-red-500">*</span>
            </label>
            <select id="barangay_code" name="barangay_code" required
                    x-model="selectedBarangayCode"
                    class="{{ $locSelectClass }}"
                    :disabled="!cityCode || loading">
                <option value="" x-text="!cityCode ? 'Select a city first' : (loading ? 'Loading...' : 'Select barangay')"></option>
                <template x-for="b in barangays" :key="b.code">
                    <option :value="b.code" x-text="b.name"></option>
                </template>
            </select>
            <p x-show="loadError" x-cloak class="mt-1 text-xs text-red-600" x-text="loadError"></p>
            @error('barangay_code')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <a href="{{ route('parent.create-child') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back</a>
            <button type="submit"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                    :disabled="!canSubmit()"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3.5 font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60">
                Continue — Login Details →
            </button>
        </div>
    </form>

</x-auth-split-layout>
