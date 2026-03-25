@extends('layouts.admin')
@section('title', 'Seminar Details')
@section('page-title', 'Seminar Details')
@section('content')

<div class="mb-5">
 <a href="{{ route('admin.seminars.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
 Back to Seminars
 </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
 <div class="xl:col-span-2 space-y-5">
 {{-- Seminar Info Card --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-6">
 <div class="flex items-start justify-between mb-5">
 <div>
 <h2 class="text-xl font-bold text-gray-900 mb-1">Understanding Reproductive Health</h2>
 <div class="flex items-center gap-2 flex-wrap">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-brand-50 text-brand-700 ">Online</span>
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-50 text-warning-700 ">Upcoming</span>
 </div>
 </div>
 <button class="p-1.5 rounded-lg text-gray-400 hover:bg-warning-50 hover:text-warning-600 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
 </button>
 </div>
 <p class="text-sm text-gray-600 mb-5">A comprehensive seminar covering essential aspects of reproductive health education for Filipino youth, including anatomy, consent, STI prevention, and healthy relationships.</p>
 <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-gray-100 ">
 <div><p class="text-xs text-gray-400 mb-0.5">Date</p><p class="text-sm font-semibold text-gray-900 ">Jul 15, 2025</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Time</p><p class="text-sm font-semibold text-gray-900 ">10:00 AM PHT</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Presenter</p><p class="text-sm font-semibold text-gray-900 ">Dr. Maria Santos</p></div>
 <div><p class="text-xs text-gray-400 mb-0.5">Capacity</p><p class="text-sm font-semibold text-gray-900 ">120 / 200</p></div>
 </div>
 </div>

 {{-- Registrants Table --}}
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
 <h3 class="text-base font-semibold text-gray-900 ">Registrants</h3>
 <span class="text-sm text-gray-400">120 registered</span>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Registered</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendance</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @foreach([['name'=>'Juan dela Cruz','role'=>'Learner','date'=>'Jul 1, 2025','att'=>'Registered'],['name'=>'Maria Reyes','role'=>'Instructor','date'=>'Jul 2, 2025','att'=>'Confirmed'],['name'=>'Ana Bautista','role'=>'Learner','date'=>'Jul 3, 2025','att'=>'Registered']] as $reg)
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-5 py-3">
 <div class="flex items-center gap-2.5">
 <div class="w-7 h-7 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 text-xs font-bold">{{ substr($reg['name'],0,1) }}</div>
 <p class="text-sm font-medium text-gray-900 ">{{ $reg['name'] }}</p>
 </div>
 </td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $reg['role'] }}</td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $reg['date'] }}</td>
 <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 ">{{ $reg['att'] }}</span></td>
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
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg bg-brand-50 border border-brand-200 text-sm font-medium text-brand-700 hover:bg-brand-100 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
 Send Reminder Email
 </button>
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
 <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
 Export Registrants CSV
 </button>
 <button class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-error-200 text-sm text-error-700 hover:bg-error-50 transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
 Cancel Seminar
 </button>
 </div>
 </div>
 </div>
</div>
@endsection
