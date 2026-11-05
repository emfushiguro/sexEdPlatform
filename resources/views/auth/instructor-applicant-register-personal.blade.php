<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Be Our Partner</h2>
            <p class="mt-4 max-w-sm text-white/80">Create an applicant account to begin the two-stage instructor review.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-purple-900">Personal information</h1>
        <p class="mt-1 text-sm text-gray-600">You must be at least 18. Your address is limited to Cavite for this application.</p>
    </div>

    @if ($errors->any())
        <div id="applicant-registration-error-summary" data-error-summary role="alert" tabindex="-1" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('instructor-application.register.personal.store') }}" data-instructor-form @if ($errors->any()) aria-describedby="applicant-registration-error-summary" @endif x-data="{
        city: @js(old('city_code', $personalInfo['city_code'] ?? '')),
        barangays: @js($barangays->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])->values()),
        birthdate: @js(old('birthdate', $personalInfo['birthdate'] ?? '')),
        age: null,
        loading: false,
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
        async loadBarangays() {
            this.barangays = [];
            if (!this.city) return;
            const response = await fetch('/api/barangays/' + encodeURIComponent(this.city));
            this.barangays = await response.json();
        }
    }" x-init="calculateAge()" @submit="loading = true">
        @csrf
        <div class="space-y-4">
            <div class="grid items-start gap-4 sm:grid-cols-2">
                <div class="min-w-0">
                    <label for="first_name" class="flex min-h-10 items-end text-sm font-medium text-gray-700">First name <span class="ml-1 text-red-600">*</span></label>
                    <input id="first_name" name="first_name" required autofocus autocomplete="given-name" value="{{ old('first_name', $personalInfo['first_name'] ?? '') }}" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-first_name">
                    @error('first_name')<p id="error-first_name" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="min-w-0">
                    <label for="last_name" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Last name <span class="ml-1 text-red-600">*</span></label>
                    <input id="last_name" name="last_name" required autocomplete="family-name" value="{{ old('last_name', $personalInfo['last_name'] ?? '') }}" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-last_name">
                    @error('last_name')<p id="error-last_name" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="grid items-start gap-4 sm:grid-cols-2">
                <div class="min-w-0">
                    <label for="middle_initial" class="flex min-h-10 items-end text-sm font-medium leading-5 text-gray-700">Middle name or initial <span class="ml-1 text-gray-500">(optional)</span></label>
                    <input id="middle_initial" name="middle_initial" autocomplete="additional-name" value="{{ old('middle_initial', $personalInfo['middle_initial'] ?? '') }}" class="mt-1 w-full rounded-xl border-gray-300">
                </div>
                <div class="min-w-0">
                    <label for="suffix" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Suffix <span class="ml-1 text-gray-500">(optional)</span></label>
                    <select id="suffix" name="suffix" autocomplete="honorific-suffix" class="mt-1 w-full rounded-xl border-gray-300">
                        @php $suffix = old('suffix', $personalInfo['suffix'] ?? ''); @endphp
                        <option value="">-- None --</option>
                        @foreach (['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $value)
                            <option value="{{ $value }}" @selected($suffix === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="min-w-0">
                <label for="birthdate" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Birthdate <span class="ml-1 text-red-600">*</span></label>
                <input id="birthdate" type="date" name="birthdate" required autocomplete="bday" x-model="birthdate" @change="calculateAge()" value="{{ old('birthdate', $personalInfo['birthdate'] ?? '') }}" min="{{ now()->subYears(100)->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-birthdate">
                <div x-show="age !== null" x-cloak data-instructor-age-indicator class="mt-2 text-sm" role="status" aria-live="polite">
                    <p x-show="age >= 18" class="font-medium text-green-600">You are <strong x-text="age"></strong> years old — Eligible to apply!</p>
                    <p x-show="age < 18" class="font-medium text-orange-600">You are <strong x-text="age"></strong> years old — you must be at least 18 to apply.</p>
                </div>
                @error('birthdate')<p id="error-birthdate" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="grid items-start gap-4 sm:grid-cols-2">
                <div class="min-w-0">
                    <label for="city_code" class="flex min-h-10 items-end text-sm font-medium leading-5 text-gray-700">Cavite city or municipality <span class="ml-1 text-red-600">*</span></label>
                    <select id="city_code" name="city_code" required data-dependent-select data-dependent-target="barangay_code" x-model="city" @change="loadBarangays()" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-city_code">
                        <option value="">Select a city or municipality</option>
                        @foreach ($cities as $cityOption)<option value="{{ $cityOption->code }}">{{ $cityOption->name }}</option>@endforeach
                    </select>
                    @error('city_code')<p id="error-city_code" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="min-w-0">
                    <label for="barangay_code" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Barangay <span class="ml-1 text-red-600">*</span></label>
                    <select id="barangay_code" name="barangay_code" required class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-barangay_code">
                        <option value="">Select a barangay</option>
                        <template x-for="barangay in barangays" :key="barangay.code"><option :value="barangay.code" x-text="barangay.name" :selected="barangay.code === @js(old('barangay_code', $personalInfo['barangay_code'] ?? ''))"></option></template>
                    </select>
                    @error('barangay_code')<p id="error-barangay_code" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="min-w-0">
                <label for="address_line" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Address line <span class="ml-1 text-gray-500">(optional)</span></label>
                <input id="address_line" name="address_line" autocomplete="street-address" value="{{ old('address_line', $personalInfo['address_line'] ?? '') }}" class="mt-1 w-full rounded-xl border-gray-300">
            </div>
            <div class="grid items-start gap-4 sm:grid-cols-2">
                <div class="min-w-0">
                    <label for="gender" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Gender <span class="ml-1 text-gray-500">(optional)</span></label>
                    @php $gender = old('gender', $personalInfo['gender'] ?? ''); @endphp
                    <select id="gender" name="gender" autocomplete="sex" class="mt-1 w-full rounded-xl border-gray-300">
                        <option value="">Select (optional)</option>
                        <option value="male" @selected($gender === 'male')>Male</option>
                        <option value="female" @selected($gender === 'female')>Female</option>
                        <option value="prefer_not_to_say" @selected($gender === 'prefer_not_to_say')>Prefer not to say</option>
                    </select>
                </div>
                <div class="min-w-0">
                    <label for="contact" class="flex min-h-10 items-end text-sm font-medium text-gray-700">Contact number <span class="ml-1 text-gray-500">(optional)</span></label>
                    <input id="contact" name="contact" autocomplete="tel" value="{{ old('contact', $personalInfo['contact'] ?? '') }}" class="mt-1 w-full rounded-xl border-gray-300">
                </div>
            </div>
            <button type="submit" :disabled="loading" class="flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white disabled:cursor-wait disabled:opacity-50">
                <span x-show="!loading">Continue</span>
                <span x-show="loading" class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Saving…
                </span>
            </button>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </div>
    </form>
</x-auth-split-layout>
