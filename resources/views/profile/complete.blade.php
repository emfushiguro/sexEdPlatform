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

    <x-wizard-stepper />

    <div class="mb-5">
        <h2 class="text-2xl font-bold text-purple-900">Complete Your Profile</h2>
        <p class="mt-1 text-sm text-gray-500">Welcome, {{ Auth::user()->first_name ?? 'Learner' }}! Just a few details to get started.</p>
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

    <form method="POST" action="{{ route('profile.store') }}"
          x-data="{
              username: '{{ old('username', $learnerProfile?->username) }}',
              usernameMessage: '',
              usernameState: 'idle',
              usernameTimer: null,
              cityCode: '{{ old('city_code', $learnerProfile?->city_code) }}',
              selectedBarangayCode: '{{ old('barangay_code', $learnerProfile?->barangay_code) }}',
              barangays: [],
              loading: false,
              normalizeUsername() {
                  this.username = this.username.toLowerCase().replace(/\s+/g, '');
              },
              usernameFormatError() {
                  if (!this.username) {
                      return '';
                  }

                  if (this.username.length < 3 || this.username.length > 30) {
                      return 'Username must be between 3 and 30 characters.';
                  }

                  if (!/^[a-z0-9_-]+$/.test(this.username)) {
                      return 'Use only lowercase letters, numbers, underscores, and hyphens.';
                  }

                  return '';
              },
              scheduleUsernameCheck() {
                  clearTimeout(this.usernameTimer);
                  this.normalizeUsername();

                  if (!this.username) {
                      this.usernameState = 'idle';
                      this.usernameMessage = '';
                      return;
                  }

                  const formatError = this.usernameFormatError();
                  if (formatError) {
                      this.usernameState = 'error';
                      this.usernameMessage = formatError;
                      return;
                  }

                  this.usernameState = 'checking';
                  this.usernameTimer = setTimeout(() => this.checkUsernameAvailability(), 350);
              },
              async checkUsernameAvailability() {
                  try {
                      const response = await fetch('{{ route('profile.username-availability') }}?username=' + encodeURIComponent(this.username), {
                          headers: { 'Accept': 'application/json' },
                          credentials: 'same-origin',
                      });

                      const payload = await response.json().catch(() => ({}));
                      const available = Boolean(payload?.available);
                      const message = payload?.message || '';

                      this.usernameState = available ? 'success' : 'error';
                      this.usernameMessage = message;
                  } catch (error) {
                      this.usernameState = 'error';
                      this.usernameMessage = 'Unable to validate username right now. Please try again.';
                  }
              },
              async loadBarangays(code) {
                  code = String(code || '').trim();

                  if (!code) {
                      this.barangays = [];
                      this.selectedBarangayCode = '';
                      return;
                  }

                  this.cityCode = code;

                  this.loading = true;
                  try {
                      const res = await fetch('/api/barangays/' + encodeURIComponent(code), {
                          headers: { 'Accept': 'application/json' },
                      });

                      if (!res.ok) {
                          throw new Error('Failed to load barangays');
                      }

                      const data = await res.json();
                      this.barangays = Array.isArray(data) ? data : [];

                      if (this.selectedBarangayCode && !this.barangays.some((b) => b.code === this.selectedBarangayCode)) {
                          this.selectedBarangayCode = '';
                      }
                  } catch (error) {
                      this.barangays = [];
                      this.selectedBarangayCode = '';
                      console.error(error);
                  } finally {
                      this.loading = false;
                  }
              }
          }"
          x-init="if (cityCode) loadBarangays(cityCode); scheduleUsernameCheck()">
        @csrf

        <div class="space-y-3">

            {{-- Row 1: Username + Gender --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                        Username <span class="text-red-500">*</span>
                    </label>
                    <input id="username" name="username" type="text" required
                                    x-model="username"
                                    @input="scheduleUsernameCheck()"
                                    @blur="scheduleUsernameCheck()"
                           placeholder="e.g., cool_learner_123"
                              pattern="^[a-z0-9_\-]{3,30}$" minlength="3" maxlength="30"
                           class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                          <p class="mt-1 text-xs text-gray-500">3-30 chars: lowercase letters, numbers, _ and -</p>
                          <p x-show="usernameMessage" x-cloak class="mt-1 text-xs"
                              :class="{
                                    'text-gray-500': usernameState === 'checking',
                                    'text-emerald-600': usernameState === 'success',
                                    'text-red-600': usernameState === 'error'
                              }"
                              x-text="usernameMessage"></p>
                    @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

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
            </div>

            {{-- Row 2: City + Barangay --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="city_code" class="block text-sm font-medium text-gray-700 mb-1">
                        City / Municipality <span class="text-red-500">*</span>
                    </label>
                    <select id="city_code" name="city_code" required
                            x-model="cityCode"
                            @change="loadBarangays($event.target.value)"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                        <option value="">Select city</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->code }}"
                                {{ old('city_code', $learnerProfile?->city_code) === $city->code ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('city_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="barangay_code" class="block text-sm font-medium text-gray-700 mb-1">
                        Barangay <span class="text-red-500">*</span>
                    </label>
                    <select id="barangay_code" name="barangay_code" required
                            x-model="selectedBarangayCode"
                            :disabled="!cityCode || loading"
                            class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition disabled:opacity-50">
                        <option value="" x-text="!cityCode ? 'Select city first' : (loading ? 'Loading...' : 'Select barangay')"></option>
                        <template x-for="b in barangays" :key="b.code">
                            <option :value="b.code" x-text="b.name"></option>
                        </template>
                        @if(old('barangay_code', $learnerProfile?->barangay_code))
                            @foreach(collect($barangays ?? [])->filter() as $barangay)
                                <option value="{{ $barangay->code }}"
                                    {{ old('barangay_code', $learnerProfile?->barangay_code) === $barangay->code ? 'selected' : '' }}>
                                    {{ $barangay->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @error('barangay_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Bio (full width) --}}
            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">
                    Bio <span class="text-gray-400 font-normal text-xs">(Optional)</span>
                </label>
                <textarea id="bio" name="bio" rows="3" maxlength="500"
                          placeholder="Tell us a bit about yourself…"
                          class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition resize-none">{{ old('bio', $learnerProfile?->bio) }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
                @error('bio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <button type="submit"
                        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                        class="w-full flex items-center justify-center gap-2 py-3.5 px-6 font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                    Complete Profile & Start Learning →
                </button>
            </div>

        </div>
    </form>

</x-auth-split-layout>
