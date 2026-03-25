@extends('layouts.admin')

@section('title', 'Instructor Application')
@section('page-title', 'Instructor Application Review')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8 space-y-6" x-data="{ approveOpen: false, rejectOpen: false }">
 <div class="rounded-2xl border border-gray-200 bg-white p-6">
 <h2 class="text-lg font-semibold text-gray-900">Applicant Information</h2>
 <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700">
 <p><span class="font-medium">Name:</span> {{ $application->user->name }}</p>
 <p><span class="font-medium">Email:</span> {{ $application->user->email }}</p>
 <p><span class="font-medium">Applied:</span> {{ $application->created_at->format('M d, Y h:i A') }}</p>
 <p><span class="font-medium">Status:</span> {{ ucfirst($application->status) }}</p>
 </div>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-6">
 <h2 class="text-lg font-semibold text-gray-900">Professional Background</h2>
 <p class="mt-2 text-sm text-gray-700 whitespace-pre-wrap">{{ $application->bio }}</p>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-6 space-y-3">
 <h2 class="text-lg font-semibold text-gray-900">Tier 1 Documents</h2>
 <div class="text-sm space-y-2">
 <p>Government ID: <a class="text-brand-600 hover:text-brand-700" href="{{ asset('storage/' . $application->government_id_path) }}" target="_blank">View</a></p>
 <p>Clearance: <a class="text-brand-600 hover:text-brand-700" href="{{ asset('storage/' . $application->clearance_path) }}" target="_blank">View</a></p>
 </div>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-6 space-y-3">
 <h2 class="text-lg font-semibold text-gray-900">Tier 2 Documents</h2>
 <div class="text-sm space-y-2">
 @if($application->teaching_credential_path)
 <p>Teaching Credential: <a class="text-brand-600 hover:text-brand-700" href="{{ asset('storage/' . $application->teaching_credential_path) }}" target="_blank">View</a></p>
 @endif
 @if($application->sexed_certificate_path)
 <p>Sex Ed Certificate: <a class="text-brand-600 hover:text-brand-700" href="{{ asset('storage/' . $application->sexed_certificate_path) }}" target="_blank">View</a></p>
 @endif
 @if($application->professional_license_path)
 <p>Professional License: <a class="text-brand-600 hover:text-brand-700" href="{{ asset('storage/' . $application->professional_license_path) }}" target="_blank">View</a></p>
 @endif
 </div>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-6">
 <h2 class="text-lg font-semibold text-gray-900">Learner Data Snapshot</h2>
 <ul class="mt-3 list-disc pl-6 text-sm text-gray-700 space-y-1">
 <li>Enrolled modules: {{ $snapshot['enrolled_modules_count'] }}</li>
 <li>Certificates earned: {{ $snapshot['certificates_earned'] }}</li>
 <li>Gamification level: {{ $snapshot['gamification_level'] ?? 0 }}</li>
 <li>Gamification score: {{ $snapshot['gamification_score'] ?? 0 }}</li>
 <li>Subscription status: {{ $snapshot['subscription_status'] }}</li>
 </ul>
 </div>

 <div class="rounded-2xl border border-gray-200 bg-white p-6">
 @if($application->status === 'pending')
 <div class="flex flex-wrap items-center gap-3">
 <button @click="approveOpen = true" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve Application</button>
 <button @click="rejectOpen = true" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Reject Application</button>
 </div>
 @elseif($application->status === 'approved')
 <p class="text-sm text-emerald-700">Approved by {{ $application->approvedBy?->name ?? 'N/A' }} on {{ optional($application->approved_at)->format('M d, Y h:i A') }}.</p>
 @else
 <p class="text-sm text-rose-700">Rejected by {{ $application->approvedBy?->name ?? 'N/A' }} on {{ optional($application->approved_at)->format('M d, Y h:i A') }}.</p>
 <p class="mt-2 text-sm text-gray-700"><span class="font-medium">Reason:</span> {{ $application->rejection_reason }}</p>
 @endif
 </div>

 @include('admin.instructor-applications._approve-modal', ['application' => $application])
 @include('admin.instructor-applications._reject-modal', ['application' => $application])
</div>
@endsection
