<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Almost there</h2>
            <p class="mt-4 max-w-sm text-white/80">Use an email you can verify. You can resume the application after signing in.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-purple-900">Create applicant account</h1>
        <p class="mt-1 text-sm text-gray-600">We will send a normal email verification link before identity review.</p>
    </div>

    @if ($errors->any())
        <div id="applicant-account-error-summary" data-error-summary role="alert" tabindex="-1" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('instructor-application.register.account.store') }}" data-instructor-form @if ($errors->any()) aria-describedby="applicant-account-error-summary" @endif @submit="loading = true" x-data="{ loading: false }">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email <span class="text-red-600">*</span></label>
                <input id="email" type="email" name="email" required autofocus autocomplete="username" value="{{ old('email') }}" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-email">
                @error('email')<p id="error-email" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-600">*</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-gray-300" aria-describedby="error-password">
                @error('password')<p id="error-password" class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password <span class="text-red-600">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-gray-300">
            </div>
            <button type="submit" :disabled="loading" class="w-full rounded-xl bg-purple-700 px-5 py-3 font-semibold text-white disabled:opacity-50">Create applicant account</button>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
            <a class="block text-center text-sm text-purple-700" href="{{ route('instructor-application.register.personal') }}">Back to personal information</a>
        </div>
    </form>
</x-auth-split-layout>
