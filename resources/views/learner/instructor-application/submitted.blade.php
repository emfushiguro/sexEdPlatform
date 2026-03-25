@extends('layouts.learner-app')

@section('title', 'Application Submitted')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="rounded-2xl border border-emerald-200 bg-white p-8 text-center">
        <h1 class="text-2xl font-bold text-emerald-700">Application Submitted Successfully</h1>
        <p class="mt-3 text-sm text-gray-600">Thank you for applying. Our admin team will review your documents and notify you when a decision is made.</p>

        <div class="mt-6 rounded-xl bg-emerald-50 p-4 text-left">
            <h2 class="text-sm font-semibold text-emerald-700">What happens next</h2>
            <ul class="mt-2 list-disc pl-5 text-sm text-emerald-900 space-y-1">
                <li>Your application is queued for review.</li>
                <li>An admin will verify your submitted documents.</li>
                <li>You will receive a notification after approval or rejection.</li>
            </ul>
        </div>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4" x-data="{ showWithdrawModal: false }">
            <a href="{{ route('learner.dashboard') }}" class="inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 hover:shadow-md transition-all">Back to Dashboard</a>
            
            <button type="button" @click="showWithdrawModal = true" class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-100 hover:text-red-700 transition-colors">
                Withdraw Application
            </button>

            {{-- Withdraw Confirmation Modal --}}
            <div x-show="showWithdrawModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true" style="backdrop-filter: blur(2px);">
                {{-- Backdrop --}}
                <div x-show="showWithdrawModal" x-transition.opacity class="fixed inset-0 bg-gray-900/60 transition-opacity" @click="showWithdrawModal = false"></div>

                {{-- Panel --}}
                <div x-show="showWithdrawModal" 
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-sm transform overflow-hidden rounded-xl bg-white p-6 text-left shadow-xl transition-all z-50">
                    
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <div class="text-center">
                        <h3 class="text-lg font-bold text-gray-900" id="modal-title">Withdraw Application?</h3>
                        <p class="mt-2 text-sm text-gray-500">Are you sure you want to withdraw your instructor application? This action cannot be undone and you will need to re-upload your documents if you apply again.</p>
                    </div>

                    <div class="mt-6 flex flex-col-reverse sm:flex-row items-center justify-center gap-3">
                        <button type="button" @click="showWithdrawModal = false" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            Cancel
                        </button>
                        <form action="{{ route('learner.instructor.withdraw') }}" method="POST" class="w-full sm:w-auto">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full inline-flex justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Yes, Withdraw
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
