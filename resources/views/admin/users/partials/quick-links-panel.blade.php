<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Quick Links</h3>
    <div class="space-y-2">
        <a href="{{ route('admin.payments.index') }}?search={{ $user->email }}" class="block px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">View Payments</a>
        <a href="{{ route('admin.subscribers.index') }}?search={{ $user->email }}" class="block px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">View Subscriptions</a>
        @if($user->id !== auth()->id())
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full px-4 py-2.5 rounded-lg border border-rose-200 text-sm text-rose-700 hover:bg-rose-50 transition-colors">Delete User</button>
            </form>
        @endif
    </div>
</div>
