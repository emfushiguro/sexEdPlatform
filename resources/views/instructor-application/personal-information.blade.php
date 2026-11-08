<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Your application</h2>
            <p class="mt-4 max-w-sm text-white/80">Keep your personal information accurate before identity evidence starts.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-purple-900">Personal information</h1>
        <p class="mt-1 text-sm text-gray-600">Only Cavite addresses are supported for this workflow.</p>
    </div>
    @if ($errors->any())
        <div id="personal-information-error-summary" data-error-summary role="alert" tabindex="-1" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if ($correctionRequested)
        <p class="mb-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900" role="status">Update your personal information, then start a replacement identity verification from your application dashboard.</p>
    @endif
    <form method="POST" action="{{ route('instructor-application.personal-information.update') }}" data-instructor-form @if ($errors->any()) aria-describedby="personal-information-error-summary" @endif x-data="{
        loading: false,
        city: @js(old('city_code', $user->profile?->city_code ?? '')),
        barangays: @js($barangays->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])->values()),
        birthdate: @js(old('birthdate', optional($user->birthdate)->format('Y-m-d'))),
        age: null,
        calculateAge() {
            if (!this.birthdate) {
                this.age = null;
                return;
            }

            const today = new Date();
            const birth = new Date(this.birthdate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            this.age = age >= 0 && age < 120 ? age : null;
        },
        async loadBarangays() { this.barangays = await (await fetch('/api/barangays/' + encodeURIComponent(this.city))).json(); }
    }" x-init="calculateAge()" @submit="loading = true">
        @csrf @method('PUT')
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="first_name" class="block text-sm font-medium text-gray-700">First name *</label><input id="first_name" name="first_name" required value="{{ old('first_name', $user->first_name) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
                <div><label for="last_name" class="block text-sm font-medium text-gray-700">Last name *</label><input id="last_name" name="last_name" required value="{{ old('last_name', $user->last_name) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            </div>
            <div><label for="middle_initial" class="block text-sm font-medium text-gray-700">Middle name or initial</label><input id="middle_initial" name="middle_initial" value="{{ old('middle_initial', $user->middle_initial) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix <span class="font-normal text-gray-400">(Optional)</span></label>
                    <select id="suffix" name="suffix" class="mt-1 w-full rounded-xl border-gray-300">
                        @php $suffix = old('suffix', $user->suffix); @endphp
                        <option value="">-- None --</option>
                        @foreach (['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $value)
                            <option value="{{ $value }}" @selected($suffix === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="birthdate" class="block text-sm font-medium text-gray-700">Birthdate *</label>
                    <input id="birthdate" type="date" name="birthdate" required x-model="birthdate" @change="calculateAge()"
                           min="{{ now()->subYears(100)->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}"
                           value="{{ old('birthdate', optional($user->birthdate)->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-300">
                    <div x-show="age !== null" x-cloak data-instructor-age-indicator class="mt-2 text-sm" role="status" aria-live="polite">
                        <p x-show="age >= 18" class="font-medium text-green-600">You are <strong x-text="age"></strong> years old — Eligible to apply!</p>
                        <p x-show="age < 18" class="font-medium text-orange-600">You are <strong x-text="age"></strong> years old — you must be at least 18 to apply.</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="city_code" class="block text-sm font-medium text-gray-700">Cavite city or municipality *</label><select id="city_code" name="city_code" required data-dependent-select data-dependent-target="barangay_code" x-model="city" @change="loadBarangays()" class="mt-1 w-full rounded-xl border-gray-300"><option value="">Select a city</option>@foreach ($cities as $city)<option value="{{ $city->code }}">{{ $city->name }}</option>@endforeach</select></div>
                <div><label for="barangay_code" class="block text-sm font-medium text-gray-700">Barangay *</label><select id="barangay_code" name="barangay_code" required class="mt-1 w-full rounded-xl border-gray-300"><option value="">Select a barangay</option><template x-for="barangay in barangays" :key="barangay.code"><option :value="barangay.code" x-text="barangay.name"></option></template></select></div>
            </div>
            <div><label for="address_line" class="block text-sm font-medium text-gray-700">Address line</label><input id="address_line" name="address_line" value="{{ old('address_line', $user->profile?->address_line) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                    @php $gender = old('gender', $user->profile?->gender); @endphp
                    <select id="gender" name="gender" class="mt-1 w-full rounded-xl border-gray-300">
                        <option value="">Select (optional)</option>
                        <option value="male" @selected($gender === 'male')>Male</option>
                        <option value="female" @selected($gender === 'female')>Female</option>
                        <option value="prefer_not_to_say" @selected($gender === 'prefer_not_to_say')>Prefer not to say</option>
                    </select>
                </div>
                <div><label for="contact" class="block text-sm font-medium text-gray-700">Contact</label><input id="contact" name="contact" value="{{ old('contact', $user->profile?->contact) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            </div>
            <button type="submit" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white disabled:cursor-wait disabled:opacity-60">
                <span x-show="!loading">Save personal information</span>
                <span x-show="loading" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Saving…
                </span>
            </button>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </div>
    </form>
</x-auth-split-layout>
