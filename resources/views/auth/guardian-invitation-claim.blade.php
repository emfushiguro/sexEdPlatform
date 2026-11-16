<x-auth-split-layout :showTabs="false">
    <h1 class="text-2xl font-bold text-purple-900">Claim this invitation</h1>
    <p class="mt-3 text-sm leading-6 text-gray-600">Continue as <strong>{{ $guardian->name }}</strong> ({{ $guardian->email }}). This account will own the invitation. Relationship and dependent identity checks still follow.</p>
    @if($errors->any())
        <p class="mt-4 text-sm text-rose-700" role="alert">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('minor.registration.claim', $token) }}" class="mt-6 space-y-5">
        @csrf
        <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="confirm_claim" value="1" required class="mt-1 rounded border-gray-300 text-purple-700 focus:ring-purple-500">I recognize this request and want to help the dependent using this guardian account.</label>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-900">Claim and continue</button>
    </form>
    <a href="{{ url('/') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-purple-800">Return home</a>
</x-auth-split-layout>
