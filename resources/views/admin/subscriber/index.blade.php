@extends('layouts.admin')

@section('title', 'Subscription Management Center')

@section('content')

<div class="max-w-7xl mx-auto px-4 py-8">
 <!-- Header with Quick Actions -->
 <div class="flex justify-between items-start mb-8">
 <div>
 <h1 class="text-2xl font-bold text-gray-900 ">Subscription Management</h1>
 <p class="text-sm text-gray-500 mt-1">Manage subscriber lifecycle, plan pricing, and entitlements.</p>
 </div>
 <div>
 <a href="{{ route('admin.subscribers.create-plan') }}" class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 transition-colors">
 Create Plan
 </a>
 </div>
 </div>

 <!-- Combined Statistics -->
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
 <!-- Subscription Stats -->
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center gap-3">
 <span class="w-10 h-10 rounded-full bg-brand-50 flex items-center justify-center">
 <svg class="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
 </span>
 <div>
 <p class="text-xs font-semibold text-gray-400 ">Total Subscribers</p>
 <p class="text-2xl font-bold text-gray-900 ">{{ $subscriptionStats['total'] ?? 0 }}</p>
 </div>
 </div>
 </div>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center gap-3">
 <span class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center">
 <svg class="w-6 h-6 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 </span>
 <div>
 <p class="text-xs font-semibold text-gray-400 ">Active</p>
 <p class="text-2xl font-bold text-gray-900 ">{{ $subscriptionStats['active'] ?? 0 }}</p>
 </div>
 </div>
 </div>
 <!-- Plan Stats -->
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center gap-3">
 <span class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center">
 <svg class="w-6 h-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"/></svg>
 </span>
 <div>
 <p class="text-xs font-semibold text-gray-400 ">Total Plans</p>
 <p class="text-2xl font-bold text-gray-900 ">{{ $planStats['total'] ?? 0 }}</p>
 </div>
 </div>
 </div>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center gap-3">
 <span class="w-10 h-10 rounded-full bg-success-50 flex items-center justify-center">
 <svg class="w-6 h-6 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
 </span>
 <div>
 <p class="text-xs font-semibold text-gray-400 ">Active Plans</p>
 <p class="text-2xl font-bold text-gray-900 ">{{ $planStats['active'] ?? 0 }}</p>
 </div>
 </div>
 </div>
 @if(isset($subscriptionStats['expiring_soon']))
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-center gap-3">
 <span class="w-10 h-10 rounded-full bg-warning-50 flex items-center justify-center">
 <svg class="w-6 h-6 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 </span>
 <div>
 <p class="text-xs font-semibold text-gray-400 ">Expiring Soon</p>
 <p class="text-2xl font-bold text-gray-900 ">{{ $subscriptionStats['expiring_soon'] }}</p>
 </div>
 </div>
 </div>
 @endif
 </div>

 <!-- Filter Form -->
 @include('admin.partials.table-filter-bar', ['label' => 'Subscribers Filters', 'hint' => 'Filter by status, plan, and user search'])
 <form method="GET" action="" class="mb-6 flex flex-wrap gap-4 items-end">
 <div>
 <label for="status" class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
 <select name="status" id="status" class="rounded-lg border-gray-300 text-sm">
 <option value="">All</option>
 <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
 <option value="trialing" {{ request('status') == 'trialing' ? 'selected' : '' }}>Trialing</option>
 <option value="grace_period" {{ request('status') == 'grace_period' ? 'selected' : '' }}>Grace Period</option>
 <option value="scheduled_cancel" {{ request('status') == 'scheduled_cancel' ? 'selected' : '' }}>Scheduled Cancel</option>
 <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
 <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
 <option value="past_due" {{ request('status') == 'past_due' ? 'selected' : '' }}>Past Due</option>
 </select>
 </div>
 <div>
 <label for="plan_id" class="block text-xs font-semibold text-gray-500 mb-1">Plan</label>
 <select name="plan_id" id="plan_id" class="rounded-lg border-gray-300 text-sm">
 <option value="">All</option>
 @foreach($plans as $plan)
 <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
 @endforeach
 </select>
 </div>
 <div>
 <label for="search" class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
 <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Name or Email" class="rounded-lg border-gray-300 text-sm" />
 </div>
 <div>
 <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-semibold shadow-theme-xs transition">Filter</button>
 <a href="{{ route('admin.subscribers.index') }}" class="ml-2 px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-semibold transition">Reset</a>
 </div>
 </form>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6 mb-8">
 <div class="flex items-center justify-between mb-4">
 <h2 class="text-xl font-bold text-gray-900 ">Subscribers</h2>
 <span class="px-3 py-1 rounded-full bg-brand-50 text-brand-600 text-xs font-bold">
 {{ $subscriptions->total() ?? $subscriptions->count() }} total
 </span>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-200 ">
 <thead>
 <tr>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">#</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Subscriber</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Email</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Status</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Plan</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Expires</th>
 <th class="px-6 py-3 bg-brand-50 text-left text-xs font-bold text-brand-700 uppercase tracking-wider">Actions</th>
 </tr>
 </thead>
 <tbody class="bg-white divide-y divide-gray-200 ">
 @forelse($subscriptions as $subscription)
 <tr class="hover:bg-brand-50 transition">
 <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-500 ">
 {{ (($subscriptions->currentPage() - 1) * $subscriptions->perPage()) + $loop->iteration }}
 </td>
 <td class="px-6 py-4 whitespace-nowrap">
 <div class="flex items-center gap-3">
 <span class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold text-sm">
 {{ strtoupper(substr($subscription->user?->name ?? '?', 0, 1)) }}
 </span>
 <span class="text-sm font-semibold text-gray-900 ">{{ $subscription->user?->name ?? '—' }}</span>
 </div>
 </td>
 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 ">{{ $subscription->user?->email ?? '—' }}</td>
 <td class="px-6 py-4 whitespace-nowrap">
 @php
 $statusColors = [
 'active' => 'bg-success-100 text-success-700 ',
 'trialing' => 'bg-brand-100 text-brand-700 ',
 'cancelled' => 'bg-error-100 text-error-700 ',
 'expired' => 'bg-gray-100 text-gray-600 ',
 'past_due' => 'bg-warning-100 text-warning-700 ',
 ];
 $statusKey = is_object($subscription->status) ? $subscription->status->value : (string) $subscription->status;
 $statusColor = $statusColors[$statusKey] ?? 'bg-gray-100 text-gray-600';
 @endphp
 <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full {{ $statusColor }}">
 {{ ucfirst($statusKey) }}
 </span>
 </td>
 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 ">{{ $subscription->plan?->name ?? ucfirst($subscription->plan ?? 'N/A') }}</td>
 <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 ">
 {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('M d, Y') : '—' }}
 </td>
 <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
 <div class="flex items-center gap-1">
 <a href="{{ route('admin.subscribers.show', $subscription->id) }}" title="Timeline" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 </a>
 </div>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="7" class="px-6 py-10 text-center text-gray-400 ">
 <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
 No subscribers found.
 </td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 @if($subscriptions instanceof \Illuminate\Pagination\LengthAwarePaginator && $subscriptions->hasPages())
 <div class="mt-4 flex items-center justify-between gap-4">
 <p class="text-xs text-gray-500 ">
 Showing {{ $subscriptions->firstItem() }}-{{ $subscriptions->lastItem() }} of {{ $subscriptions->total() }}
 </p>
 {{ $subscriptions->withQueryString()->links() }}
 </div>
 @endif
 </div>
</div>

@if(session('success'))
<script>
 document.addEventListener('DOMContentLoaded', function() {
 // Show success notification
 const notification = document.createElement('div');
 notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
 notification.textContent = @json(session('success'));
 document.body.appendChild(notification);
 
 setTimeout(() => {
 notification.remove();
 }, 5000);
 });
</script>
@endif

@if(session('error'))
<script>
 document.addEventListener('DOMContentLoaded', function() {
 // Show error notification
 const notification = document.createElement('div');
 notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
 notification.textContent = @json(session('error'));
 document.body.appendChild(notification);
 
 setTimeout(() => {
 notification.remove();
 }, 5000);
 });
</script>
@endif



<!-- Create Plan Modal -->



<script>
// Auto-open modal if session flag is set
@if(session('openModal'))
document.addEventListener('DOMContentLoaded', function() {
 openModal('{{ session('openModal') }}Modal');
});
@endif

// Auto-open modal if there are validation errors
@if($errors->any() && old('_token'))
document.addEventListener('DOMContentLoaded', function() {
 @if(old('name') !== null || old('price') !== null)
 openModal('createPlanModal');
 @endif
});
@endif

function openModal(modalId) {
 document.getElementById(modalId).classList.remove('hidden');
 document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
 document.getElementById(modalId).classList.add('hidden');
 document.body.style.overflow = 'auto';
}

/**
 * Trial days preview helper — shows a live hint below the trial_days input
 * indicating exactly what text will appear on the plan card.
 * previewId = id of the <p> hint container
 * days = current input value
 */
function updateTrialPreview(previewId, days) {
 const hint = document.getElementById(previewId);
 const textEl = document.getElementById(previewId + 'Text');
 const d = parseInt(days, 10);
 if (!hint) return;
 if (!d || d <= 0) {
 hint.classList.add('hidden');
 return;
 }
 let label;
 if (d >= 365) {
 label = d + '-Day Access (Annual)';
 } else if (d >= 28) {
 label = d + '-Day Access (Monthly)';
 } else {
 label = d + '-Day Access';
 }
 if (textEl) textEl.textContent = label;
 hint.classList.remove('hidden');
}

// Close modal on backdrop click
document.querySelectorAll('[id$="Modal"]').forEach(modal => {
 modal.addEventListener('click', function(e) {
 if (e.target === this) {
 closeModal(this.id);
 }
 });
});

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
 if (e.key === 'Escape') {
 document.querySelectorAll('[id$="Modal"]').forEach(modal => {
 if (!modal.classList.contains('hidden')) {
 closeModal(modal.id);
 }
 });
 }
});
</script>
@endsection