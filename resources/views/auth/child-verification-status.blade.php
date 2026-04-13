<x-auth-split-layout :showTabs="false">
    @php
        $childReasonRaw = (string) ($verification->verification_rejection_reason ?? '');
        $allowedReasonTags = '<p><br><strong><b><em><i><u><ul><ol><li><a>';
        $childRejectionReasonHtml = trim((string) strip_tags(
            str_replace("\xC2\xA0", ' ', html_entity_decode($childReasonRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            $allowedReasonTags
        ));
        $childRejectionReasonText = trim((string) preg_replace('/\s+/u', ' ', strip_tags($childRejectionReasonHtml)));
    @endphp

    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Child Verification</h2>
            <p class="text-white/80 text-lg max-w-xs">This child account is under moderation review.</p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Account Status</h2>
        <p class="mt-1 text-sm text-gray-600">A platform administrator must approve this child account before access is granted.</p>
    </div>

    @if(($verification->verification_status ?? 'pending') === 'rejected')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mb-4">
            <p class="font-semibold">Verification result: Rejected</p>
            <div class="mt-1 break-words">
                <span class="font-medium">Reason:</span>
                @if($childRejectionReasonText !== '')
                    <div class="mt-1 [&_p]:m-0 [&_a]:underline [&_a]:break-all">{!! $childRejectionReasonHtml !!}</div>
                @else
                    <span> No reason provided by administrator.</span>
                @endif
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            Please ask your parent/guardian to submit corrected child verification details.
        </p>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            <p class="font-semibold">Verification result: Pending Review</p>
            <p class="mt-1">Your parent/guardian will be notified once this account is approved.</p>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            Learning modules are temporarily unavailable until verification is complete.
        </p>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
            Log Out
        </button>
    </form>
</x-auth-split-layout>
