<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Forgot your password?</h2>
            <p class="text-white/80 text-lg max-w-xs">No worries — we'll send a reset link to your email.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Password Reset</h2>
        <p class="mt-1 text-sm text-gray-500">
            Enter the email address linked to your account and we'll send you a reset link.
        </p>
    </div>

    {{-- Session Status (link sent confirmation) --}}
    @if (session('status'))
        <div class="mb-5 bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm text-green-700 font-medium">{{ __(session('status')) }}</p>
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="mb-5 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <ul class="text-sm text-red-700 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-5">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                Email Address <span class="text-red-500">*</span>
            </label>
            <input id="email" name="email" type="email" required autofocus
                   value="{{ old('email') }}"
                   placeholder="your@gmail.com"
                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200">
        </div>

        <button type="submit"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                class="w-full flex items-center justify-center gap-2 py-3.5 px-6 font-semibold text-white rounded-xl shadow-md hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200">
            Send Reset Link
        </button>

        <div class="mt-6 text-center">
            <a href="{{ route('learner.login') }}"
               class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
                &larr; Back to Login
            </a>
        </div>
    </form>

</x-auth-split-layout>
