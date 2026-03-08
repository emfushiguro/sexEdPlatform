@extends('layouts.instructor')
@section('title', $user->name)
@section('page-title', $user->name)
@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('instructor.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Users
    </a>
    <a href="{{ route('instructor.users.edit', $user) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
        Edit User
    </a>
</div>

<div class="space-y-5">
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Basic Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Basic Information</h3>
                            
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->name }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Role</label>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300">
                                {{ ucfirst($user->role) }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</label>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($user->status === 'active') bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300
                                @elseif($user->status === 'suspended') bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300
                                @else bg-gray-100 dark:bg-gray-500/20 text-gray-800 dark:text-gray-300 @endif">
                                {{ ucfirst($user->status) }}
                            </span>
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email Verified</label>
                        <p class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->email_verified_at ? 'bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-500/20 text-red-800 dark:text-red-300' }}">
                                {{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- System Information -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">System Information</h3>
                    
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User ID</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">#{{ $user->id }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Created At</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Last Updated</label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->updated_at->format('M d, Y h:i A') }}</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Assigned Roles (Spatie)</label>
                        <div class="mt-1 flex flex-wrap gap-1">
                            @forelse($user->roles as $role)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 dark:bg-purple-500/20 text-purple-800 dark:text-purple-300">
                                    {{ $role->name }}
                                </span>
                            @empty
                                <span class="text-sm text-gray-500 dark:text-gray-400">No roles assigned</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-Specific Information -->
    @if($user->role === 'learner' && $user->gamification)
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Gamification Stats</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-purple-50 dark:bg-purple-500/10 rounded-xl">
                    <div class="text-sm text-purple-600 dark:text-purple-400">Level</div>
                    <div class="text-2xl font-bold text-purple-900 dark:text-purple-300">{{ $user->gamification->level }}</div>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-blue-500/10 rounded-xl">
                    <div class="text-sm text-blue-600 dark:text-blue-400">Total Score</div>
                    <div class="text-2xl font-bold text-blue-900 dark:text-blue-300">{{ $user->gamification->score }}</div>
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-500/10 rounded-xl">
                    <div class="text-sm text-green-600 dark:text-green-400">Streak</div>
                    <div class="text-2xl font-bold text-green-900 dark:text-green-300">{{ $user->gamification->streak_count }} days</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex justify-end gap-3">
        <a href="{{ route('instructor.users.edit', $user) }}" class="px-5 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
            Edit User
        </a>
        @if($user->id !== auth()->id())
        <form action="{{ route('instructor.users.destroy', $user) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium shadow-theme-xs transition-colors"
                onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                Delete User
            </button>
        </form>
        @endif
    </div>
</div>

@endsection
