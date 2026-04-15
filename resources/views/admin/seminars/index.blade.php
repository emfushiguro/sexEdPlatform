@extends('layouts.admin')
@section('title', 'Seminars & Events')
@section('page-title', 'Seminars & Events')
@section('content')

<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 text-sm">
 <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 Seminar management backend coming soon. This is a UI preview.
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
 @foreach([['label'=>'Total Seminars','value'=>'24','bg'=>'bg-brand-50 ','color'=>'text-brand-600 '],['label'=>'Upcoming','value'=>'8','bg'=>'bg-warning-50 ','color'=>'text-warning-600 '],['label'=>'Completed','value'=>'14','bg'=>'bg-success-50 ','color'=>'text-success-600 '],['label'=>'Registrations','value'=>'342','bg'=>'bg-brand-50 ','color'=>'text-brand-600 ']] as $c)
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
 <span class="text-lg font-bold {{ $c['color'] }}">{{ $c['value'] }}</span>
 </div>
 <p class="text-xs text-gray-400 ">{{ $c['label'] }}</p>
 </div>
 @endforeach
</div>

{{-- Table --}}
<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">All Seminars</h3>
 <a href="{{ route('admin.seminars.create') }}"
 class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
 New Seminar
 </a>
 </div>
 <div class="px-6 py-3 border-b border-gray-100 ">
 <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
 <input type="text" placeholder="Search seminars..." class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
 <select class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option>All Types</option><option>Online</option><option>In-Person</option><option>Hybrid</option>
 </select>
 <select class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option>All Status</option><option>Upcoming</option><option>Ongoing</option><option>Completed</option><option>Cancelled</option>
 </select>
 </div>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Time</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Presenter</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Capacity</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
 <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @php
 $mock = [
 ['title'=>'Understanding Reproductive Health','date'=>'Jul 15, 2025 10:00 AM','type'=>'Online','presenter'=>'Dr. Maria Santos','cap'=>'120/200','status'=>'upcoming'],
 ['title'=>'Teen Consent & Boundaries Workshop','date'=>'Jul 20, 2025 2:00 PM','type'=>'In-Person','presenter'=>'Prof. Josef Reyes','cap'=>'45/50','status'=>'upcoming'],
 ['title'=>'HIV/AIDS Awareness Forum','date'=>'Jun 28, 2025 9:00 AM','type'=>'Hybrid','presenter'=>'Dr. Ana Lim','cap'=>'98/100','status'=>'completed'],
 ];
 $typeMap = ['Online'=>'bg-brand-50 text-brand-700 ','In-Person'=>'bg-success-50 text-success-700 ','Hybrid'=>'bg-brand-50 text-brand-700 '];
 $stMap = ['upcoming'=>'bg-warning-50 text-warning-700 ','ongoing'=>'bg-success-50 text-success-700 ','completed'=>'bg-gray-100 text-gray-500 ','cancelled'=>'bg-error-50 text-error-700 '];
 @endphp
 @foreach($mock as $s)
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-5 py-3"><p class="text-sm font-semibold text-gray-900 ">{{ $s['title'] }}</p></td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $s['date'] }}</td>
 <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeMap[$s['type']] ?? 'bg-gray-100 text-gray-500' }}">{{ $s['type'] }}</span></td>
 <td class="px-5 py-3 text-sm text-gray-700 ">{{ $s['presenter'] }}</td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $s['cap'] }}</td>
 <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $stMap[$s['status']] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($s['status']) }}</span></td>
 <td class="px-5 py-3 text-right">
 <a href="{{ route('admin.seminars.show', 1) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors inline-flex">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
 </a>
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
</div>
@endsection
