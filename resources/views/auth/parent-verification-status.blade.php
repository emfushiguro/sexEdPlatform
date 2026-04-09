<x-auth-split-layout :showTabs="false">
    @php
        $status = $user->parent_verification_status ?? 'pending';
        $isApproved = $isApproved ?? ($status === 'approved');
        $showApprovedModal = (bool) session('show_parent_approved_modal', false);
    @endphp

    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="mb-6">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="h-20 w-auto mx-auto mb-3 drop-shadow-lg">
                <p class="text-white/90 font-semibold tracking-wide text-sm uppercase">Concious Connections</p>
            </div>
            <h2 class="text-4xl font-bold text-white mb-4 leading-tight">
                {{ $isApproved ? 'Verification Approved' : 'Parent Verification' }}
            </h2>
            <p class="text-white/80 text-lg max-w-xs">
                {{ $isApproved
                    ? 'Your parent account is approved. You can start learning or create a child account.'
                    : 'Your account is being reviewed by an administrator.' }}
            </p>
        </div>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Verification Status</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ $isApproved
                ? 'Your parent verification is complete. Choose what you want to do next.'
                : 'We are reviewing your parent/guardian identity document.' }}
        </p>
    </div>

    @if($isApproved)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 mb-4">
            <p class="font-semibold">Verification result: Approved</p>
            <p class="mt-1">Your parent account is now active. You can continue learning and manage child accounts.</p>
        </div>
    @elseif($status === 'rejected')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 mb-4">
            <p class="font-semibold">Verification result: Rejected</p>
            <p class="mt-1">Reason: {{ $user->parent_verification_rejection_reason ?: 'No reason provided by administrator.' }}</p>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            Please contact support or register again with a valid government-issued ID.
        </p>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 mb-4">
            <p class="font-semibold">Verification result: Pending Review</p>
            <p class="mt-1">You will receive an email once your parent account has been approved.</p>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            While this is pending, child account creation and parent management features are disabled.
        </p>
    @endif

    <div class="space-y-3" x-data="{ showApprovedModal: {{ $isApproved && $showApprovedModal ? 'true' : 'false' }} }">
        @if($isApproved)
            <a href="{{ route('learner.dashboard') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                Start Learning
            </a>

            <a href="{{ route('parent.create-child') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
               style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                Create Child Account
            </a>

            <div x-cloak
                 x-show="showApprovedModal"
                 x-transition.opacity
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                     @click.away="showApprovedModal = false">
                    <h3 class="text-xl font-bold text-purple-900">Parent Verification Approved</h3>
                    <p class="mt-2 text-sm text-gray-600">Your parent account is now active. Choose what you want to do next.</p>

                    <div class="mt-5 space-y-3">
                        <a href="{{ route('learner.dashboard') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #0EA5E9, #2563EB);">
                            Start Learning
                        </a>

                        <a href="{{ route('parent.create-child') }}"
                           class="w-full inline-flex justify-center items-center px-6 py-3 text-base font-medium rounded-xl text-white transition"
                           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            Create Child Account
                        </a>
                    </div>

                </div>
            </div>
        @else
            <a href="https://mail.google.com"
               target="_blank"
               rel="noopener"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Open Gmail Inbox
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition">
                Log Out
            </button>
        </form>
    </div>
</x-auth-split-layout>
