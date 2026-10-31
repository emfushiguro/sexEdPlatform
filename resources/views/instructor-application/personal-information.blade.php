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
    <form method="POST" action="{{ route('instructor-application.personal-information.update') }}" data-instructor-form @if ($errors->any()) aria-describedby="personal-information-error-summary" @endif x-data="{ city: @js(old('city_code', $user->profile?->city_code ?? '')), barangays: @js($barangays->map(fn ($item) => ['code' => $item->code, 'name' => $item->name])->values()), async loadBarangays() { this.barangays = await (await fetch('/api/barangays/' + encodeURIComponent(this.city))).json(); } }">
        @csrf @method('PUT')
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="first_name" class="block text-sm font-medium text-gray-700">First name *</label><input id="first_name" name="first_name" required value="{{ old('first_name', $user->first_name) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
                <div><label for="last_name" class="block text-sm font-medium text-gray-700">Last name *</label><input id="last_name" name="last_name" required value="{{ old('last_name', $user->last_name) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            </div>
            <div><label for="middle_initial" class="block text-sm font-medium text-gray-700">Middle name or initial</label><input id="middle_initial" name="middle_initial" value="{{ old('middle_initial', $user->middle_initial) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            <div><label for="birthdate" class="block text-sm font-medium text-gray-700">Birthdate *</label><input id="birthdate" type="date" name="birthdate" required value="{{ old('birthdate', optional($user->birthdate)->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="city_code" class="block text-sm font-medium text-gray-700">Cavite city or municipality *</label><select id="city_code" name="city_code" required data-dependent-select data-dependent-target="barangay_code" x-model="city" @change="loadBarangays()" class="mt-1 w-full rounded-xl border-gray-300"><option value="">Select a city</option>@foreach ($cities as $city)<option value="{{ $city->code }}">{{ $city->name }}</option>@endforeach</select></div>
                <div><label for="barangay_code" class="block text-sm font-medium text-gray-700">Barangay *</label><select id="barangay_code" name="barangay_code" required class="mt-1 w-full rounded-xl border-gray-300"><option value="">Select a barangay</option><template x-for="barangay in barangays" :key="barangay.code"><option :value="barangay.code" x-text="barangay.name"></option></template></select></div>
            </div>
            <div><label for="address_line" class="block text-sm font-medium text-gray-700">Address line</label><input id="address_line" name="address_line" value="{{ old('address_line', $user->profile?->address_line) }}" class="mt-1 w-full rounded-xl border-gray-300"></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label for="gender" class="block text-sm font-medium text-gray-700">Gender</label><input id="gender" name="gender" value="{{ old('gender', $user->profile?->gender) }}" class="mt-1 w-full rounded-xl border-gray-300"></div><div><label for="contact" class="block text-sm font-medium text-gray-700">Contact</label><input id="contact" name="contact" value="{{ old('contact', $user->profile?->contact) }}" class="mt-1 w-full rounded-xl border-gray-300"></div></div>
            <button class="w-full rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white">Save personal information</button>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </div>
    </form>
</x-auth-split-layout>
