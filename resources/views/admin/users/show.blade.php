@extends('layouts.admin')

@section('content')
<div class="container mx-auto py-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold">User Details</h1>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow font-semibold">
                Edit User
            </a>
            @if($user->id !== auth()->id())
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded shadow font-semibold" 
                    onclick="return confirm('Are you sure you want to delete this user?')">
                    Delete User
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Name</label>
                <p class="text-lg font-semibold">{{ $user->name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Email</label>
                <p class="text-lg">{{ $user->email }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Role</label>
                <p>
                    @php
                        $roleColors = [
                            'learner' => 'bg-blue-100 text-blue-800',
                            'instructor' => 'bg-purple-100 text-purple-800',
                            'counselor' => 'bg-green-100 text-green-800',
                            'clinic' => 'bg-teal-100 text-teal-800',
                            'organization' => 'bg-indigo-100 text-indigo-800',
                            'admin' => 'bg-red-100 text-red-800',
                        ];
                        $roleColor = $roleColors[$user->role] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-block {{ $roleColor }} text-sm px-3 py-1 rounded font-semibold">{{ ucfirst($user->role) }}</span>
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                <p>
                    @if($user->status=='active')
                        <span class="inline-block bg-green-100 text-green-800 text-sm px-3 py-1 rounded font-semibold">Active</span>
                    @elseif($user->status=='suspended')
                        <span class="inline-block bg-red-100 text-red-800 text-sm px-3 py-1 rounded font-semibold">Suspended</span>
                    @else
                        <span class="inline-block bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded font-semibold">{{ ucfirst($user->status) }}</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Joined</label>
                <p class="text-lg">{{ $user->created_at->format('M d, Y') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Email Verified</label>
                <p class="text-lg">
                    @if($user->email_verified_at)
                        <span class="text-green-600">✓ Verified</span>
                    @else
                        <span class="text-red-600">✗ Not Verified</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-green-600">₱{{ number_format($stats['total_payments'], 2) }}</div>
            <div class="text-sm text-gray-500">Total Payments</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-blue-600">{{ $stats['completed_modules'] }}</div>
            <div class="text-sm text-gray-500">Completed Modules</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-indigo-600">{{ $stats['quiz_attempts'] }}</div>
            <div class="text-sm text-gray-500">Quiz Attempts</div>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <div class="text-lg font-bold text-yellow-600">{{ $stats['certificates'] }}</div>
            <div class="text-sm text-gray-500">Certificates</div>
        </div>
    </div>

    <!-- Subscription Info -->
    @if($user->subscription)
    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Subscription</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Plan</label>
                <p class="text-lg font-semibold">{{ ucfirst($user->subscription->plan) }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Status</label>
                <p>
                    @if($user->subscription->status == 'active')
                        <span class="inline-block bg-green-100 text-green-800 text-sm px-3 py-1 rounded font-semibold">Active</span>
                    @else
                        <span class="inline-block bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded font-semibold">{{ ucfirst($user->subscription->status) }}</span>
                    @endif
                </p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Ends</label>
                <p class="text-lg">{{ $user->subscription->end_date->format('M d, Y') }}</p>
            </div>
        </div>
    </div>
    @else
    <div class="bg-gray-50 rounded shadow p-6 mb-6 text-center">
        <p class="text-gray-500">No active subscription</p>
    </div>
    @endif

    <!-- Recent Payments -->
    @if($user->payments->count() > 0)
    <div class="bg-white rounded shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">Recent Payments</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 border text-left text-sm font-semibold">Date</th>
                        <th class="px-4 py-2 border text-left text-sm font-semibold">Amount</th>
                        <th class="px-4 py-2 border text-left text-sm font-semibold">Method</th>
                        <th class="px-4 py-2 border text-left text-sm font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($user->payments->take(5) as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="border px-4 py-2 text-sm">{{ $payment->created_at->format('M d, Y H:i') }}</td>
                        <td class="border px-4 py-2 text-sm font-semibold">₱{{ number_format($payment->amount, 2) }}</td>
                        <td class="border px-4 py-2 text-sm">{{ ucfirst($payment->method) }}</td>
                        <td class="border px-4 py-2">
                            @if($payment->status=='completed')
                                <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Completed</span>
                            @elseif($payment->status=='failed')
                                <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded">Failed</span>
                            @else
                                <span class="inline-block bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">{{ ucfirst($payment->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Gamification -->
    @if($user->gamification)
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-bold mb-4">Gamification</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Points</label>
                <p class="text-2xl font-bold text-blue-600">{{ $user->gamification->points }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Level</label>
                <p class="text-2xl font-bold text-green-600">{{ $user->gamification->level }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500 mb-1">Streak</label>
                <p class="text-2xl font-bold text-orange-600">{{ $user->gamification->streak }} days</p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
