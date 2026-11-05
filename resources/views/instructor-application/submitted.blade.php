<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Application received</h2>
            <p class="mt-4 max-w-sm text-white/80">An administrator will review your Stage Two submission.</p>
        </div>
    </x-slot>

    <div x-data class="mx-auto max-w-2xl rounded-2xl border border-emerald-200 bg-white p-8 text-center">
        @if(!empty($application->application_metadata['document_change_request']['slots']))
        <h1 class="text-2xl font-bold text-amber-800">Document changes requested</h1>
        <p class="mt-3 text-sm text-gray-600">Please replace the requested documents so your application review can continue.</p>
        <a href="{{ route('instructor-application.documents.requested', $application) }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white">Review requested documents</a>
        @else
        <h1 class="text-2xl font-bold text-emerald-700">Application submitted</h1>
        <p class="mt-3 text-sm text-gray-600">Your identity review and instructor application remain separate decisions.</p>
        <div class="mt-6 rounded-xl bg-emerald-50 p-4 text-left text-sm text-emerald-900">
            <h2 class="font-semibold">What happens next</h2>
            <ul class="mt-2 list-disc space-y-1 pl-5"><li>Your qualifications are queued for review.</li><li>You will receive a notification when a decision is made.</li></ul>
        </div>
        @endif
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('instructor-application.dashboard') }}" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white">Back to application hub</a>
            <button type="button" data-testid="withdraw-application-trigger" @click="$refs.withdrawDialog.showModal()" class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">Withdraw application</button>
        </div>

        <dialog x-ref="withdrawDialog" data-testid="withdraw-application-dialog" @click.self="$refs.withdrawDialog.close()" aria-labelledby="withdraw-application-title" aria-describedby="withdraw-application-description" class="m-auto w-[min(92vw,28rem)] rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
            <div class="p-6 text-left">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">Confirm withdrawal</p>
                <h2 id="withdraw-application-title" class="mt-2 text-xl font-bold text-gray-900">Withdraw your application?</h2>
                <p id="withdraw-application-description" class="mt-2 text-sm leading-6 text-gray-600">Your current instructor application will be withdrawn. You can apply again later.</p>
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="$refs.withdrawDialog.close()" class="min-h-11 rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500">Cancel</button>
                    <form action="{{ route('instructor-application.application.withdraw') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="min-h-11 rounded-xl bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2">Withdraw application</button>
                    </form>
                </div>
            </div>
        </dialog>
    </div>
</x-auth-split-layout>
