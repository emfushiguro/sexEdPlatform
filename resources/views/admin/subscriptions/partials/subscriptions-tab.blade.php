<!-- Subscriptions Management Tab -->
<div class="bg-white rounded-lg shadow">
    <!-- Filters -->
    <div class="p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Name or email..." 
                       class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="past_due" {{ request('status') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan</label>
                <select name="plan_id" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-medium transition">
                    🔍 Filter
                </button>
                <a href="{{ route('admin.subscriptions.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-medium transition">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Subscriptions List -->
    <div class="overflow-x-auto">
        @if(isset($subscriptions) && $subscriptions->count() > 0)
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($subscriptions as $subscription)
                        <tr class="hover:bg-gray-50 {{ is_object($subscription->plan) && $subscription->plan->hasFeature('test_mode') ? 'bg-yellow-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $subscription->user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $subscription->user->email }}</div>
                                        @if(is_object($subscription->plan) && $subscription->plan->hasFeature('test_mode'))
                                            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">TEST USER</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $subscription->getPlanLabel() }}
                                    @if(is_object($subscription->plan) && $subscription->plan->hasFeature('duration_minutes'))
                                        <span class="text-xs text-orange-600">({{ $subscription->plan->getFeatureValue('duration_minutes') }}min)</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ $subscription->getPlanLabel() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $subscription->getStatusColor() === 'green' ? 'bg-green-100 text-green-800' : 
                                       ($subscription->getStatusColor() === 'red' ? 'bg-red-100 text-red-800' : 
                                        ($subscription->getStatusColor() === 'orange' ? 'bg-orange-100 text-orange-800' : 
                                         'bg-gray-100 text-gray-800')) }}">
                                    {{ $subscription->getStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>Start: {{ $subscription->start_date->format('M d, Y') }}</div>
                                <div>End: {{ $subscription->end_date ? $subscription->end_date->format('M d, Y') : 'N/A' }}</div>
                                @if($subscription->isActive() && $subscription->end_date)
                                    <div class="text-xs text-blue-600">{{ $subscription->daysUntilExpiry() }} days left</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ₱{{ number_format($subscription->getAmount(), 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                @if($subscription->status === 'pending')
                                    <form method="POST" action="{{ route('admin.subscriptions.quick-action') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="activate_subscription">
                                        <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                                        <button type="submit" class="text-green-600 hover:text-green-900">Activate</button>
                                    </form>
                                @endif

                                @if($subscription->canCancel())
                                    <form method="POST" action="{{ route('admin.subscriptions.quick-action') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="cancel_subscription">
                                        <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">
                                        <button type="submit" class="text-red-600 hover:text-red-900"
                                                onclick="return confirm('Cancel this subscription?')">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No subscriptions found</h3>
                <p class="text-gray-500 mb-4">Get started by creating your first subscription.</p>
                <a href="{{ route('admin.subscriptions.index', ['tab' => 'plans']) }}"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded font-medium transition">
                    Add a Plan
                </a>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if(isset($subscriptions) && $subscriptions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $subscriptions->appends(request()->query())->links() }}
        </div>
    @endif
</div>