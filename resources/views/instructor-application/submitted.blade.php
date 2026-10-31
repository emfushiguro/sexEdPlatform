<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Application received</h2>
            <p class="mt-4 max-w-sm text-white/80">An administrator will review your Stage Two submission.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-2xl border border-emerald-200 bg-white p-8 text-center">
        <h1 class="text-2xl font-bold text-emerald-700">Application submitted</h1>
        <p class="mt-3 text-sm text-gray-600">Your identity review and instructor application remain separate decisions.</p>
        <div class="mt-6 rounded-xl bg-emerald-50 p-4 text-left text-sm text-emerald-900">
            <h2 class="font-semibold">What happens next</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5"><li>Your qualifications are queued for review.</li><li>You will receive a notification when a decision is made.</li></ul>
        </div>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('instructor-application.dashboard') }}" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white">Back to application hub</a>
            <form action="{{ route('instructor-application.application.withdraw') }}" method="POST">@csrf @method('DELETE')<button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700">Withdraw application</button></form>
        </div>
    </div>
</x-auth-split-layout>
