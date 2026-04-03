@extends('layouts.admin')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('content')

{{-- Stat Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
 @php
 $cards = [
 ['label'=>'Total Users', 'value'=>$stats['total'], 'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'ring'=>'ring-brand-200 ', 'bg'=>'bg-brand-50 ', 'color'=>'text-brand-600 '],
 ['label'=>'Active Users', 'value'=>$stats['active'], 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'ring'=>'ring-success-200 ', 'bg'=>'bg-success-50 ', 'color'=>'text-success-600 '],
 ['label'=>'Learners', 'value'=>$stats['learners'],'icon'=>'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'ring'=>'ring-purple-200 ', 'bg'=>'bg-purple-50 ', 'color'=>'text-purple-600 '],
 ['label'=>'Premium Users', 'value'=>$stats['premium'], 'icon'=>'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'ring'=>'ring-warning-200 ', 'bg'=>'bg-warning-50 ', 'color'=>'text-warning-600 '],
 ];
 @endphp
 @foreach($cards as $c)
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5 ring-1 {{ $c['ring'] }}">
 <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
 <svg class="w-5 h-5 {{ $c['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/></svg>
 </div>
 <p class="text-2xl font-bold text-gray-900 ">{{ $c['value'] }}</p>
 <p class="text-xs text-gray-400 mt-0.5">{{ $c['label'] }}</p>
 </div>
 @endforeach
</div>

{{-- Table Card --}}
<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">All Users</h3>
 <a href="{{ route('admin.users.create') }}"
 class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
 Create User
 </a>
 </div>
 @include('admin.partials.table-filter-bar', ['label' => 'Users Filters', 'hint' => 'Search by name/email, role, and status'])
 <form method="GET" class="px-6 py-4 border-b border-gray-100 ">
 <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
 <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..."
 class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 <select name="role" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="">All Roles</option>
 <option value="learner" @selected(request('role')=='learner')>Learner</option>
 <option value="instructor" @selected(request('role')=='instructor')>Instructor</option>
 <option value="counselor" @selected(request('role')=='counselor')>Counselor</option>
 <option value="clinic" @selected(request('role')=='clinic')>Clinic</option>
 <option value="organization" @selected(request('role')=='organization')>Organization</option>
 <option value="admin" @selected(request('role')=='admin')>Admin</option>
 </select>
 <select name="status" class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option value="">All Status</option>
 <option value="active" @selected(request('status')=='active')>Active</option>
 <option value="inactive" @selected(request('status')=='inactive')>Inactive</option>
 <option value="suspended" @selected(request('status')=='suspended')>Suspended</option>
 </select>
 <div class="flex gap-2">
 <button type="submit" class="flex-1 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium transition-colors">Filter</button>
 <a href="{{ route('admin.users.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 hover:bg-gray-50 transition-colors">Clear</a>
 </div>
 </div>
 </form>
 @include('admin.partials.row-actions', ['actions' => ['View', 'Edit', 'Deactivate/Reactivate', 'Send Reset Link']])
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
 <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @php
 $roleMap = ['learner'=>'bg-brand-50 text-brand-700 ','instructor'=>'bg-purple-50 text-purple-700 ','counselor'=>'bg-success-50 text-success-700 ','clinic'=>'bg-teal-50 text-teal-700 ','organization'=>'bg-indigo-50 text-indigo-700 ','admin'=>'bg-error-50 text-error-700 '];
 $statusMap = ['active'=>'bg-success-50 text-success-700 ','suspended'=>'bg-error-50 text-error-700 ','inactive'=>'bg-gray-100 text-gray-500 '];
 @endphp
 @forelse($users as $user)
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-5 py-3">
 <div class="flex items-center gap-3">
 <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-xs font-bold flex-shrink-0">
 {{ strtoupper(substr($user->name, 0, 1)) }}
 </div>
 <div>
 <p class="text-sm font-semibold text-gray-900 ">{{ $user->name }}</p>
 <p class="text-xs text-gray-400 ">{{ $user->email }}</p>
 </div>
 </div>
 </td>
 <td class="px-5 py-3">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleMap[$user->role] ?? 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($user->role) }}</span>
 </td>
 <td class="px-5 py-3">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusMap[$user->status] ?? 'bg-gray-100 text-gray-500 ' }}">{{ ucfirst($user->status) }}</span>
 </td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $user->created_at->format('M d, Y') }}</td>
 <td class="px-5 py-3">
 <div class="flex items-center justify-end gap-1">
 <a href="{{ route('admin.users.show', $user) }}" title="View" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
 </a>
 <a href="{{ route('admin.users.edit', $user) }}" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:bg-warning-50 hover:text-warning-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
 </a>
 @if($user->id !== auth()->id())
 <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?')">
 @csrf @method('DELETE')
 <button type="submit" title="Delete" class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
 </button>
 </form>
 @endif
 </div>
 </td>
 </tr>
 @empty
 <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400 ">No users found.</td></tr>
 @endforelse
 </tbody>
 </table>
 </div>
 @if($users->hasPages())
 <div class="px-6 py-4 border-t border-gray-100 ">{{ $users->withQueryString()->links() }}</div>
 @endif
</div>
@endsection
