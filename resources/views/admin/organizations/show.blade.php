@extends('layouts.admin')
@section('title', 'Organization Details')
@section('page-title', 'Organization Details')
@section('content')

<div class="mb-5">
 <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Organizations
 </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
 <div class="xl:col-span-2 space-y-5">
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-start gap-4 mb-6">
 <div class="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center text-indigo-600 text-2xl font-bold flex-shrink-0">D</div>
 <div class="flex-1">
 <h2 class="text-xl font-bold text-gray-900 mb-1">De La Salle University - Manila</h2>
 <div class="flex items-center gap-2">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-50 text-brand-700 ">School</span>
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 ">Active Partner</span>
 </div>
 </div>
 </div>
 <div class="grid grid-cols-2 sm:grid-cols-3 gap-5 pt-4 border-t border-gray-100 ">
 <div><p class="text-xs text-gray-400 mb-0.5">Location</p><p class="text-sm font-semibold text-gray-900 ">Manila, NCR</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Partnership Since</p><p class="text-sm font-semibold text-gray-900 ">Jan 2024</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Members</p><p class="text-sm font-semibold text-gray-900 ">340</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Contact Person</p><p class="text-sm font-semibold text-gray-900 ">Mr. Jose Reyes</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Email</p><p class="text-sm font-semibold text-gray-900 ">jreyes@dlsu.edu.ph</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Phone</p><p class="text-sm font-semibold text-gray-900 ">+63 2 524 4611</p></div>
 </div>
 </div>

 {{-- Members Table --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
 <h3 class="text-base font-semibold text-gray-900 ">Members</h3>
 <span class="text-sm text-gray-400">340 registered</span>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @foreach([['name'=>'Juan dela Cruz','role'=>'Learner','joined'=>'Feb 2024'],['name'=>'Maria Santos','role'=>'Instructor','joined'=>'Jan 2024'],['name'=>'Pedro Reyes','role'=>'Learner','joined'=>'Mar 2024']] as $m)
 <tr class="hover:bg-gray-50 ">
 <td class="px-5 py-3 text-sm font-medium text-gray-900 ">{{ $m['name'] }}</td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $m['role'] }}</td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $m['joined'] }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 </div>
 </div>

 {{-- Actions --}}
 <div>
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <h3 class="text-sm font-semibold text-gray-700 mb-4">Actions</h3>
 <div class="space-y-2">
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
 <svg class="w-4 h-4 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
 Edit Details
 </button>
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
 <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
 Send Announcement
 </button>
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-error-200 text-sm text-error-700 hover:bg-error-50 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
 Suspend Partnership
 </button>
 </div>
 </div>
 </div>
</div>
@endsection
