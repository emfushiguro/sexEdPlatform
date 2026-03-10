<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">One last step!</h2>
            <p class="text-white/80 text-lg max-w-xs">Personalize your learning experience</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Personal Info', 'active' => false, 'done' => true],
        ['label' => 'Account Info',  'active' => false, 'done' => true],
        ['label' => 'Verify Email',  'active' => false, 'done' => true],
        ['label' => 'Profile',       'active' => true,  'done' => false],
    ]" />

    <div class="mb-5">
        <h2 class="text-2xl font-bold text-purple-900">Complete Your Profile</h2>
        <p class="mt-1 text-sm text-gray-500">Welcome, {{ Auth::user()->first_name ?? Auth::user()->name }}! Just a few details to get started.</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-5 rounded-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(Auth::user()->email_verified_at)
        <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-3 py-2 mb-5">
            <svg class="w-4 h-4 text-green-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-xs text-green-800"><strong>Email verified:</strong> {{ Auth::user()->email }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.store') }}"
          x-data="{
              cityCode: '{{ old('city_code', $learnerProfile?->city_code) }}',
              barangays: [],
              loading: false,
              async loadBarangays(code) {
                  if (!code) { this.barangays = []; return; }
                  this.loading = true;
                  try {
                      const res = await fetch('/api/barangays/' + code);
                      this.barangays = await res.json();
                  } finally { this.loading = false; }
              }
          }"
          x-init="if (cityCode) loadBarangays(cityCode)">
        @csrf

        <div class="space-y-4">

            {{-- Username --}}
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                    Username <span class="text-red-500">*</span>
                </label>
                <input id="username" name="username" type="text" required
                       value="{{ old('username', $learnerProfile?->username) }}"
                       placeholder="e.g., cool_learner_123"
                       pattern="[a-z0-9_-]+" minlength="3" maxlength="30"
                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                <p class="mt-1 text-xs text-gray-500">3â€“30 characters: lowercase letters, numbers, underscores, hyphens</p>
                @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Gender --}}
            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                <select id="gender" name="gender"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    <option value="">Select (optional)</option>
                    <option value="male"              {{ old('gender', $learnerProfile?->gender) === 'male'              ? 'selected' : '' }}>Male</option>
                    <option value="female"            {{ old('gender', $learnerProfile?->gender) === 'female'            ? 'selected' : '' }}>Female</option>
                    <option value="prefer_not_to_say" {{ old('gender', $learnerProfile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                </select>
                @error('gender')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- City --}}
            <div>
                <label for="city_code" class="block text-sm font-medium text-gray-700 mb-1">
                    Municipality / City (Cavite) <span class="text-red-500">*</span>
                </label>
                <select id="city_code" name="city_code" required
                        x-model="cityCode"
                        @change="loadBarangays(cityCode)"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                    <option value="">Select your municipality / city</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->code }}"
                            {{ old('city_code', $learnerProfile?->city_code) === $city->code ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
                @error('city_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Barangay --}}
            <div>
                <label for="barangay_code" class="block text-sm font-medium text-gray-700 mb-1">
                    Barangay <span class="text-red-500">*</span>
                </label>
                <select id="barangay_code" name="barangay_code" required
                        :disabled="!cityCode || loading"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition disabled:opacity-50">
                    <option value="">
                        <template x-if="!cityCode">Select city first</template>
                        <template x-if="cityCode && loading">Loadingâ€¦</template>
                        <template x-if="cityCode && !loading">Select your barangay</template>
                    </option>
                    <template x-for="b in barangays" :key="b.code">
                        <option :value="b.code"
                                :selected="b.code === '{{ old('barangay_code', $learnerProfile?->barangay_code) }}'"
                                x-text="b.name"></option>
                    </template>
                    {{-- Pre-load existing barangay when editing --}}
                    @if(old('barangay_code', $learnerProfile?->barangay_code))
                        @foreach($barangays ?? [] as $barangay)
                            <option value="{{ $barangay->code }}"
                                {{ old('barangay_code', $learnerProfile?->barangay_code) === $barangay->code ? 'selected' : '' }}>
                                {{ $barangay->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('barangay_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Bio --}}
            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">
                    Bio <span class="text-gray-400 font-normal text-xs">(Optional)</span>
                </label>
                <textarea id="bio" name="bio" rows="3" maxlength="500"
                          placeholder="Tell us a bit about yourselfâ€¦"
                          class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition resize-none">{{ old('bio', $learnerProfile?->bio) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
                @error('bio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <button type="submit"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                        class="w-full flex items-center justify-center gap-2 px-8 py-3 text-sm font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                    Complete Profile &amp; Start Learning â†’
                </button>
            </div>

        </div>
    </form>

</x-auth-split-layout>
