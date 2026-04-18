{{-- Alpine store — shared state between panel and main content --}}
@if($showSuccess)
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('verifyFlow', { showForm: false });
    });
</script>
@endif

<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            @if($showSuccess)
                {{-- "Check your inbox" panel (shown during countdown) --}}
                <div x-data x-show="!$store.verifyFlow?.showForm"
                     x-transition:leave="transition ease-in duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <div class="mb-6">
                        <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                        <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Email Verified!</h2>
                    <p class="text-white/80 text-lg max-w-xs">Setting up your profile…</p>
                </div>
                {{-- "One last step!" panel (shown when profile form appears) --}}
                <div x-data x-show="$store.verifyFlow?.showForm" x-cloak
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="mb-6">
                        <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                        <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
                    </div>
                    <h2 class="text-4xl font-bold text-white mb-4 leading-tight">One last step!</h2>
                    <p class="text-white/80 text-lg max-w-xs">Personalize your learning experience</p>
                </div>
            @else
                <div class="mb-6">
                    <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                    <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
                </div>
                <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Check your inbox</h2>
                <p class="text-white/80 text-lg max-w-xs">We sent a link to your Gmail address</p>
            @endif
        </div>
    </x-slot>

    {{-- Wizard Stepper --}}
    @if($showSuccess)
        @php
            $successSteps = [
                ['label' => 'Personal Info', 'isCompleted' => true,  'isActive' => false, 'isUpcoming' => false],
                ['label' => 'Account Info',  'isCompleted' => true,  'isActive' => false, 'isUpcoming' => false],
                ['label' => 'Verify Email',  'isCompleted' => true,  'isActive' => false, 'isUpcoming' => false],
                ['label' => 'Profile',       'isCompleted' => false, 'isActive' => true,  'isUpcoming' => false],
            ];
        @endphp
        <x-wizard-stepper :steps="$successSteps" />
    @else
        <x-wizard-stepper />
    @endif

    @if($showSuccess)
        {{-- SUCCESS STATE: countdown → then inline profile form --}}
        <div x-data="{
            countdown: 3,
            progress: 100,
            init() {
                const interval = setInterval(() => {
                    this.countdown--;
                    this.progress = (this.countdown / 3) * 100;
                    if (this.countdown <= 0) {
                        clearInterval(interval);
                        Alpine.store('verifyFlow').showForm = true;
                    }
                }, 1000);
            }
        }" x-init="init()">

            {{-- Countdown animation (hidden once form appears) --}}
            <div x-show="!$store.verifyFlow?.showForm"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="text-center py-4">

                {{-- Success icon --}}
                <div class="mx-auto mb-5 w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-800 mb-2">Email Verified!</h3>
                <p class="text-gray-500 text-sm mb-6 max-w-xs mx-auto">
                    Your email has been confirmed. Loading your profile setup in
                    <strong x-text="countdown" class="text-brand-purple-primary"></strong>
                    second<span x-show="countdown !== 1">s</span>&hellip;
                </p>

                {{-- Progress bar --}}
                <div class="w-full bg-gray-100 rounded-full h-2 mb-5">
                    <div class="h-2 rounded-full transition-all duration-1000 ease-linear"
                         style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                         :style="'width: ' + progress + '%'"></div>
                </div>

                <button type="button"
                        @click="Alpine.store('verifyFlow').showForm = true"
                        class="inline-flex items-center gap-2 text-sm font-medium hover:underline"
                        style="color: #730DB1;">
                    Continue now &rarr;
                </button>
            </div>

            {{-- Inline Profile Form (slides in once countdown ends) --}}
            <div x-show="$store.verifyFlow?.showForm"
                 x-cloak
                 x-transition:enter="transition ease-out duration-400"
                 x-transition:enter-start="opacity-0 translate-y-6"
                 x-transition:enter-end="opacity-100 translate-y-0">

                <div class="mb-5">
                    <h2 class="text-2xl font-bold text-purple-900">Complete Your Profile</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Welcome, {{ Auth::user()->first_name ?? 'Learner' }}! Just a few details to get started.
                    </p>
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
                          cityCode: '{{ old('city_code', $learnerProfile?->city_code) }}',
                          selectedBarangayCode: '{{ old('barangay_code', $learnerProfile?->barangay_code) }}',
                          barangays: [],
                          loading: false,
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
                      x-init="if (cityCode) loadBarangays(cityCode)">
                    @csrf

                    <div class="space-y-3">

                        {{-- Row 1: Username + Gender --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                                    Username <span class="text-red-500">*</span>
                                </label>
                                <input id="username" name="username" type="text" required
                                       value="{{ old('username', $learnerProfile?->username) }}"
                                       placeholder="e.g., cool_learner_123"
                                        pattern="^[a-z0-9_\-]{3,30}$" minlength="3" maxlength="30"
                                       class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition">
                                <p class="mt-1 text-xs text-gray-500">3–30 chars: letters, numbers, _ or -</p>
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

                        {{-- Bio --}}
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
                                Complete Profile &amp; Start Learning &rarr;
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    @else
        {{-- WAITING STATE: Inbox-style verification guidance --}}
        <div class="space-y-4"
             x-data="{
                pollingHandle: null,
                async checkVerificationStatus() {
                    try {
                        const response = await fetch(@js(route('verification.status')), {
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        if (payload && payload.verified) {
                            window.location.reload();
                        }
                    } catch (error) {
                        console.error(error);
                    }
                },
                init() {
                    this.pollingHandle = setInterval(() => this.checkVerificationStatus(), 5000);
                    this.checkVerificationStatus();
                }
             }"
             x-init="init()">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('status') == 'verification-link-sent')
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ __('A fresh verification email has been sent. Check your inbox and spam folder.') }}
                </div>
            @endif

            @if (session('verification_error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('verification_error') }}
                </div>
            @endif

            <section class="max-w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <header class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Email Preview</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">Verify Your Email To Continue</h3>
                </header>

                <div class="px-4 py-4">
                    <article class="rounded-xl border border-sky-100 bg-sky-50/70 px-4 py-3">
                        <p class="mt-3 text-sm text-gray-600">Open the email and click the verification link to activate your account.</p>
                    </article>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a href="https://mail.google.com"
                           class="inline-flex w-full items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Open Gmail
                        </a>

                        <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                            @csrf
                            <button type="submit"
                                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                                    class="inline-flex w-full items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
                                Resend Verification Email
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <div>
                @if(session('is_parent_registration'))
                    <a href="{{ route('parent.register.account') }}"
                       class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
                        &larr; Back to Account Info
                    </a>
                @else
                    <a href="{{ route('register.account') }}"
                       class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
                        &larr; Back to Account Info
                    </a>
                @endif
            </div>
        </div>
    @endif
</x-auth-split-layout>
