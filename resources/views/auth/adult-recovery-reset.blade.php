<x-auth-split-layout :showTabs="false">
    <x-slot name="panel"><div class="flex h-full flex-col items-center justify-center p-12 text-center"><img src="{{ asset('/media/Logo.png') }}" alt="" class="mb-4 h-20"><h2 class="text-4xl font-bold text-white">Recover your account</h2><p class="mt-4 text-white/80">Use one of your private recovery codes to choose a new password.</p></div></x-slot>
    <h1 class="text-2xl font-bold text-purple-900">Use a recovery code</h1><p class="mt-2 text-sm text-gray-600">For adult dependent accounts with previously saved recovery codes.</p>
    @if($errors->any())<div role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('password.recovery-code.reset') }}" class="mt-5 space-y-4">@csrf
        <div><label for="username" class="block text-sm font-semibold">Username</label><input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50"></div>
        <div><label for="code" class="block text-sm font-semibold">Recovery code</label><input id="code" name="code" required autocomplete="off" class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50"></div>
        <div><label for="password" class="block text-sm font-semibold">New password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50"><p class="mt-1 text-xs text-gray-600">At least 8 characters, with uppercase and lowercase letters, a number and a symbol.</p></div>
        <div><label for="password_confirmation" class="block text-sm font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1 w-full rounded-xl border-gray-200 bg-gray-50"></div>
        <button class="w-full rounded-xl bg-purple-800 px-5 py-3 font-semibold text-white">Reset password</button>
    </form><a class="mt-5 inline-block text-sm font-semibold text-purple-700 underline" href="{{ route('password.request') }}">Back to password recovery</a>
</x-auth-split-layout>
