@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8 space-y-6">
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="rounded-2xl border border-amber-200 bg-white p-5">
 <p class="text-xs uppercase tracking-wide text-amber-600">Pending</p>
 <p class="mt-2 text-3xl font-bold text-gray-900">{{ $pendingCount }}</p>
 </div>
 <div class="rounded-2xl border border-emerald-200 bg-white p-5">
 <p class="text-xs uppercase tracking-wide text-emerald-600">Approved</p>
 <p class="mt-2 text-3xl font-bold text-gray-900">{{ $approvedCount }}</p>
 </div>
 <div class="rounded-2xl border border-rose-200 bg-white p-5">
 <p class="text-xs uppercase tracking-wide text-rose-600">Rejected</p>
 <p class="mt-2 text-3xl font-bold text-gray-900">{{ $rejectedCount }}</p>
 </div>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-5">
 <div class="mb-4 flex gap-2">
 <a href="{{ route('admin.instructor-applications.index', ['status' => 'pending']) }}" class="rounded-full px-4 py-1.5 text-sm {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700' }}">Pending</a>
 <a href="{{ route('admin.instructor-applications.index', ['status' => 'approved']) }}" class="rounded-full px-4 py-1.5 text-sm {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700' }}">Approved</a>
 <a href="{{ route('admin.instructor-applications.index', ['status' => 'rejected']) }}" class="rounded-full px-4 py-1.5 text-sm {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700' }}">Rejected</a>
 </div>

 <div class="overflow-x-auto">
 <table class="min-w-full divide-y divide-gray-200 text-sm">
 <thead>
 <tr class="text-left text-gray-600">
 <th class="px-3 py-2">Name</th>
 <th class="px-3 py-2">Email</th>
 <th class="px-3 py-2">Date</th>
 <th class="px-3 py-2">Status</th>
 <th class="px-3 py-2"></th>
 </tr>
 </thead>
 <tbody class="divide-y divide-gray-100">
 @forelse($applications as $application)
 <tr>
 <td class="px-3 py-2">{{ $application->user->name }}</td>
 <td class="px-3 py-2">{{ $application->user->email }}</td>
 <td class="px-3 py-2">{{ $application->created_at->format('M d, Y') }}</td>
 <td class="px-3 py-2">
 <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($application->status) }}</span>
 </td>
 <td class="px-3 py-2 text-right">
 <a href="{{ route('admin.instructor-applications.show', $application) }}" class="text-brand-600 hover:text-brand-700 font-medium">View</a>
 </td>
 </tr>
 @empty
 <tr>
 <td colspan="5" class="px-3 py-8 text-center text-gray-500">No applications found for this tab.</td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>

 <div class="mt-4">{{ $applications->links() }}</div>
 </div>
</div>
@endsection
