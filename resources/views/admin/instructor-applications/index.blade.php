@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-8">
	<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
		<article class="rounded-[28px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5 shadow-theme-xs">
			<p class="text-xs font-semibold uppercase tracking-[0.24em] text-amber-600">Pending</p>
			<p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($pendingCount) }}</p>
			<p class="mt-2 text-sm text-gray-500">Applications currently waiting for review.</p>
		</article>
		<article class="rounded-[28px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-lime-50 p-5 shadow-theme-xs">
			<p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Approved</p>
			<p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($approvedCount) }}</p>
			<p class="mt-2 text-sm text-gray-500">Learners upgraded to instructor status.</p>
		</article>
		<article class="rounded-[28px] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-orange-50 p-5 shadow-theme-xs sm:col-span-2 xl:col-span-1">
			<p class="text-xs font-semibold uppercase tracking-[0.24em] text-rose-600">Rejected</p>
			<p class="mt-3 text-3xl font-bold text-gray-900">{{ number_format($rejectedCount) }}</p>
			<p class="mt-2 text-sm text-gray-500">Applications needing revision before re-apply.</p>
		</article>
	</section>

	<section class="overflow-hidden rounded-[30px] border border-gray-200 bg-white shadow-theme-xs">
		<div class="border-b border-gray-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_32%),linear-gradient(180deg,#ffffff_0%,#f8fafc_100%)] px-6 py-6">
			@include('admin.partials.table-filter-bar', ['label' => 'Instructor Applications Filters', 'hint' => 'Search applicant names, usernames, education, and professional background.'])

			<div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
				<div>
					<p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Review Queue</p>
					<h2 class="mt-2 text-xl font-bold text-gray-900">Instructor Applications Table</h2>
					<p class="mt-1 text-sm text-gray-500">Use the filters below to focus on specific applicants and review outcomes.</p>
				</div>

				<form method="GET" action="{{ route('admin.instructor-applications.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
					<input type="hidden" name="status" value="{{ $status }}">
					<label class="block sm:col-span-2">
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
						<input type="text"
							   name="search"
							   value="{{ $search ?? '' }}"
							   placeholder="Name, username, education, background"
							   class="w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
					</label>
					<div class="sm:col-span-2 xl:col-span-2">
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
						<div class="grid grid-cols-3 gap-2">
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'pending', 'search' => $search]) }}" class="rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'approved', 'search' => $search]) }}" class="rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Approved</a>
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'rejected', 'search' => $search]) }}" class="rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Rejected</a>
						</div>
					</div>
					<div class="sm:col-span-2 xl:col-span-4 flex gap-2">
						<button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">Apply Filters</button>
						<a href="{{ route('admin.instructor-applications.index', ['status' => $status]) }}" class="rounded-2xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
					</div>
				</form>
			</div>
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200">
				<thead class="bg-gray-50">
					<tr>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Applicant</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Username</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Location</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Educational Background</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Professional Background</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date Applied</th>
						<th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
						<th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100 bg-white">
					@forelse($applications as $application)
						@php
							$profile = $application->user->learnerProfile;
							$location = collect([
								$profile?->city?->name,
								$profile?->barangayLocation?->name ?? $profile?->barangay,
							])->filter()->implode(', ');
						@endphp
						<tr class="transition hover:bg-sky-50/40">
							<td class="px-6 py-4">
								<p class="text-sm font-semibold text-gray-900">{{ $application->user->name }}</p>
								<p class="text-xs text-gray-500">{{ $application->user->email }}</p>
							</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $profile?->username ?? 'N/A' }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $location !== '' ? $location : 'Not set' }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $application->educational_background ?: 'Not provided' }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit((string) $application->bio, 100) }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $application->created_at->format('M d, Y') }}</td>
							<td class="px-6 py-4">
								<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
									{{ ucfirst($application->status) }}
								</span>
							</td>
							<td class="px-6 py-4 text-right">
								<a href="{{ route('admin.instructor-applications.show', $application) }}" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="View application">
									<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
										<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
									</svg>
								</a>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="8" class="px-6 py-14 text-center">
								<div class="mx-auto max-w-sm">
									<div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400">
										<svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-1a4 4 0 00-4-4h-1m-4 5H4v-1a4 4 0 014-4h5m0 5v-1a4 4 0 00-4-4H8m5 5h1a4 4 0 004-4v-1m-5-5a3 3 0 11-6 0 3 3 0 016 0z" />
										</svg>
									</div>
									<h3 class="mt-4 text-sm font-semibold text-gray-900">No applications found for this status</h3>
									<p class="mt-1 text-sm text-gray-500">Try switching tabs or widening your search query.</p>
								</div>
							</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="border-t border-gray-100 px-6 py-4">
			{{ $applications->links() }}
		</div>
	</section>
</div>
@endsection
