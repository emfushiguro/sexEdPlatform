@extends('layouts.learner-app')

@section('title', 'Connector Status')

@section('content')
<section class="mx-auto max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-sm" x-data="{ showWithdrawModal: false }">
    <p class="text-xs font-semibold uppercase tracking-wider text-purple-700">Connector Status</p>
    <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $connector->name }}</h1>

    <div class="mt-6 rounded-xl border p-5">
        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide
            {{ $connector->status === 'verified' ? 'bg-green-100 text-green-700' : ($connector->status === 'rejected' ? 'bg-red-100 text-red-700' : ($connector->status === 'suspended' ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700')) }}">
            {{ $connector->status }}
        </span>

        @if($connector->status === 'pending')
            <p class="mt-4 text-sm text-gray-600">Your connector registration is waiting for admin review. Workspace actions unlock after approval.</p>
            <button type="button" @click="showWithdrawModal = true" class="mt-4 rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Withdraw Application</button>
        @elseif($connector->status === 'verified')
            <p class="mt-4 text-sm text-gray-600">This connector is verified and ready for workspace use.</p>
            <a href="{{ route('connector.dashboard', $connector) }}" class="mt-4 inline-flex rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Open Dashboard</a>
        @elseif($connector->status === 'rejected')
            <p class="mt-4 text-sm text-gray-600">This connector was rejected. Reason: {{ $connector->rejection_reason }}</p>
        @elseif($connector->status === 'withdrawn')
            <p class="mt-4 text-sm text-gray-600">This connector application was withdrawn before an admin decision.</p>
        @else
            <p class="mt-4 text-sm text-gray-600">This connector is suspended. Reason: {{ $connector->rejection_reason }}</p>
        @endif
    </div>

    @if($connector->status === 'pending')
        <div
            x-cloak
            x-show="showWithdrawModal"
            class="fixed inset-0 z-[100000] flex items-center justify-center bg-gray-900/50 px-4"
            role="dialog"
            aria-modal="true"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.outside="showWithdrawModal = false">
                <h2 class="text-lg font-bold text-gray-900">Withdraw Application</h2>
                <p class="mt-3 text-sm text-gray-600">This will cancel the pending connector application before admin review. You can submit a new application later if needed.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showWithdrawModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                    <form method="POST" action="{{ route('connector.withdraw', $connector) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Withdrawal</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</section>
@endsection
