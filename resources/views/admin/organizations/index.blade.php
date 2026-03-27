@extends('layouts.admin')
@section('title', 'Organizations')
@section('page-title', 'Organizations')
@section('content')

<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 text-sm">
 <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 Organization management backend coming soon. This is a UI preview.
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
 @foreach([['label'=>'Partner Orgs','value'=>'28','bg'=>'bg-brand-50 ','color'=>'text-brand-600 '],['label'=>'Schools','value'=>'12','bg'=>'bg-purple-50 ','color'=>'text-purple-600 '],['label'=>'Clinics / NGOs','value'=>'10','bg'=>'bg-success-50 ','color'=>'text-success-600 '],['label'=>'Active Agreements','value'=>'22','bg'=>'bg-warning-50 ','color'=>'text-warning-600 ']] as $c)
 <div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs p-5">
 <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
 <span class="text-lg font-bold {{ $c['color'] }}">{{ $c['value'] }}</span>
 </div>
 <p class="text-xs text-gray-400 ">{{ $c['label'] }}</p>
 </div>
 @endforeach
</div>

<div class="rounded-2xl bg-white border border-gray-200 shadow-theme-xs overflow-hidden">
 <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 ">
 <h3 class="text-base font-semibold text-gray-900 ">Partner Organizations</h3>
 <button class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
 Add Organization
 </button>
 </div>
 <div class="px-6 py-3 border-b border-gray-100 ">
 <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
 <input type="text" placeholder="Search organizations..." class="px-3 py-2 rounded-lg border border-gray-200 bg-transparent text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
 <select class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option>All Types</option><option>School</option><option>Clinic</option><option>NGO</option><option>Government</option><option>Corporate</option>
 </select>
 <select class="px-3 py-2 rounded-lg border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
 <option>All Regions</option><option>NCR</option><option>Region I</option><option>Region III</option><option>Region IV-A</option><option>Region VII</option>
 </select>
 </div>
 </div>
 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-100 ">
 <thead class="bg-gray-50 ">
 <tr>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Organization</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Location</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Members</th>
 <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
 <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100 ">
 @php
 $orgs = [
 ['name'=>'De La Salle University - Manila','type'=>'School','loc'=>'Manila, NCR','members'=>'340','status'=>'active'],
 ['name'=>'UP Padayon Public Health Clinic','type'=>'Clinic','loc'=>'Quezon City, NCR','members'=>'28','status'=>'active'],
 ['name'=>'Likhaan Center for Women','type'=>'NGO','loc'=>'Cubao, NCR','members'=>'85','status'=>'active'],
 ['name'=>'Cebu City Health Department','type'=>'Government','loc'=>'Cebu City, Region VII','members'=>'52','status'=>'pending'],
 ];
 $typeColor = ['School'=>'bg-brand-50 text-brand-700 ','Clinic'=>'bg-success-50 text-success-700 ','NGO'=>'bg-purple-50 text-purple-700 ','Government'=>'bg-indigo-50 text-indigo-700 ','Corporate'=>'bg-gray-100 text-gray-600 '];
 @endphp
 @foreach($orgs as $org)
 <tr class="hover:bg-gray-50 transition-colors">
 <td class="px-5 py-3">
 <div class="flex items-center gap-3">
 <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold flex-shrink-0">{{ substr($org['name'],0,1) }}</div>
 <p class="text-sm font-semibold text-gray-900 ">{{ $org['name'] }}</p>
 </div>
 </td>
 <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColor[$org['type']] ?? 'bg-gray-100 text-gray-500' }}">{{ $org['type'] }}</span></td>
 <td class="px-5 py-3 text-sm text-gray-500 ">{{ $org['loc'] }}</td>
 <td class="px-5 py-3 text-sm font-semibold text-gray-900 ">{{ $org['members'] }}</td>
 <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $org['status'] === 'active' ? 'bg-success-50 text-success-700 ' : 'bg-warning-50 text-warning-700 ' }}">{{ ucfirst($org['status']) }}</span></td>
 <td class="px-5 py-3 text-right">
 <a href="{{ route('admin.organizations.show', 1) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 transition-colors inline-flex">
 <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
 </a>
 </td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
</div>
@endsection
