@extends('layouts.admin')

@section('title', 'Instructor Applications')
@section('page-title', 'Instructor Applications')

@section('content')
@php
	 $initialReviewApplicationId = old('review_application_id', $focusApplicationId ?? null);
	 $initialReviewApplicationId = $initialReviewApplicationId ? (int) $initialReviewApplicationId : null;
@endphp

<div class="mx-auto max-w-7xl px-4 py-8"
	  x-data="{
			activeReview: @js($initialReviewApplicationId),
			documentPreviewOpen: false,
			documentPreviewUrl: '',
			documentPreviewTitle: '',
			documentPreviewType: 'file',
			expandedModules: {},
			openReview(id) {
				this.activeReview = id;
			},
			closeReview() {
				this.activeReview = null;
			},
			openDocumentPreview(url, title, type) {
				this.documentPreviewUrl = url;
				this.documentPreviewTitle = title;
				this.documentPreviewType = type;
				this.documentPreviewOpen = true;
			},
			closeDocumentPreview() {
				this.documentPreviewOpen = false;
				this.documentPreviewUrl = '';
				this.documentPreviewTitle = '';
				this.documentPreviewType = 'file';
			},
			toggleFinishedModules(id) {
				this.expandedModules[id] = !this.expandedModules[id];
			}
	  }">
	<div :class="(activeReview !== null || documentPreviewOpen) ? 'blur-[2px] scale-[0.995] pointer-events-none select-none' : ''" class="space-y-8 transition duration-300 ease-out">
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

				<form method="GET" action="{{ route('admin.instructor-applications.index') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-12 xl:items-end">
					<input type="hidden" name="status" value="{{ $status }}">
					<label class="block sm:col-span-2 xl:col-span-5">
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Search</span>
						<input type="text"
							   name="search"
							   value="{{ $search ?? '' }}"
							   placeholder="Name, username, education, background"
							   class="h-[46px] w-full rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
					</label>
					<div class="sm:col-span-2 xl:col-span-4">
						<span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Status</span>
						<div class="grid grid-cols-3 gap-2 sm:max-w-md xl:max-w-none">
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'pending', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'pending' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Pending</a>
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'approved', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'approved' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Approved</a>
							<a href="{{ route('admin.instructor-applications.index', ['status' => 'rejected', 'search' => $search]) }}" class="inline-flex h-[46px] items-center justify-center rounded-xl px-3 py-2 text-center text-sm font-semibold transition {{ $status === 'rejected' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">Rejected</a>
						</div>
					</div>
					<div class="sm:col-span-2 xl:col-span-3 flex flex-col gap-2 sm:flex-row xl:justify-end">
						<button type="submit" class="inline-flex h-[46px] items-center justify-center rounded-2xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">Apply Filters</button>
						<a href="{{ route('admin.instructor-applications.index', ['status' => $status]) }}" class="inline-flex h-[46px] items-center justify-center rounded-2xl border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Reset</a>
					</div>
				</form>
			</div>
		</div>

		<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200">
				<thead class="bg-gray-50">
					<tr>
						<th data-testid="applications-col-applicant" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Applicant</th>
						<th data-testid="applications-col-location" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Location</th>
						<th data-testid="applications-col-educational-background" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Educational Background</th>
						<th data-testid="applications-col-date-applied" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Date Applied</th>
						<th data-testid="applications-col-status" class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Status</th>
						<th data-testid="applications-col-actions" class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.2em] text-gray-500">Actions</th>
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
							<td class="px-6 py-4 text-sm text-gray-700">{{ $location !== '' ? $location : 'Not set' }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $application->educational_background_label ?: 'Not provided' }}</td>
							<td class="px-6 py-4 text-sm text-gray-700">{{ $application->created_at->format('M d, Y') }}</td>
							<td class="px-6 py-4">
								<span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
									{{ ucfirst($application->status) }}
								</span>
							</td>
							<td class="px-6 py-4 text-right">
								<button type="button"
										data-testid="review-application-button-{{ $application->id }}"
										@click="openReview({{ $application->id }})"
										class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
										title="Review Application"
										aria-label="Review Application">
										<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
											<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
										</svg>
								</button>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="6" class="px-6 py-14 text-center">
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

	@foreach($applications as $application)
		@php
			$profile = $application->user->learnerProfile;
			$location = collect([
				$profile?->city?->name,
				$profile?->barangayLocation?->name ?? $profile?->barangay,
			])->filter()->implode(', ');
			$completedModules = $application->user->moduleEnrollments;
			$tierOneDocuments = [
				['label' => 'Government ID', 'path' => $application->government_id_path],
				['label' => 'Verification Document (NBI/Police Clearance)', 'path' => $application->clearance_path],
			];
			$tierTwoDocuments = [
				['label' => 'Teaching Credential', 'path' => $application->teaching_credential_path],
				['label' => 'Sex Education Certificate', 'path' => $application->sexed_certificate_path],
				['label' => 'Professional License', 'path' => $application->professional_license_path],
			];
			$defaultReasonCode = old('review_application_id') == $application->id ? old('rejection_reason_code') : '';
			$defaultReasonNote = old('review_application_id') == $application->id ? old('rejection_reason_note') : '';
			$rejectPanelDefaultOpen = old('review_application_id') == $application->id && ($errors->has('rejection_reason_code') || $errors->has('rejection_reason_note'));
		@endphp

		<div x-show="activeReview === {{ $application->id }}"
			 x-cloak
			 data-testid="application-review-modal-{{ $application->id }}"
			 @keydown.escape.window="if (activeReview === {{ $application->id }}) closeReview()"
			 class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 lg:p-8">
			<div x-show="activeReview === {{ $application->id }}"
				 x-transition.opacity
				 class="fixed inset-0 bg-gray-900/45 backdrop-blur-lg transition-opacity"
				 @click="closeReview()"></div>

			<div x-show="activeReview === {{ $application->id }}"
				 x-transition:enter="ease-out duration-300"
				 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
				 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
				 x-transition:leave="ease-in duration-200"
				 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
				 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
				 class="relative z-50 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">

				<div class="border-b border-gray-100 bg-gray-50/80 px-6 py-4">
					<div class="flex items-center justify-between">
						<div>
							<p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Application Review</p>
							<h2 class="mt-1 text-xl font-bold text-gray-900">{{ $application->user->name }}</h2>
							<p class="text-sm text-gray-500">Application #{{ $application->id }}</p>
						</div>
						<button type="button" @click="closeReview()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
							<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
							</svg>
						</button>
					</div>
				</div>

				<div class="max-h-[80vh] space-y-6 overflow-y-auto bg-white px-6 py-6">
					<section class="rounded-2xl border border-gray-200 bg-white p-5">
						<h3 class="text-base font-bold text-gray-900">Section 1 - Application Information</h3>
						<div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-2">
							<p><span class="font-semibold">Applicant Name:</span> {{ $application->user->name }}</p>
							<p><span class="font-semibold">Email Address:</span> {{ $application->user->email }}</p>
							<p><span class="font-semibold">Application Status:</span> <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $application->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($application->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($application->status) }}</span></p>
							<p><span class="font-semibold">Date Applied:</span> {{ $application->created_at->format('M d, Y h:i A') }}</p>
							<p><span class="font-semibold">Username:</span> {{ $profile?->username ?? 'N/A' }}</p>
							<p><span class="font-semibold">Location:</span> {{ $location !== '' ? $location : 'Not set' }}</p>
							<p><span class="font-semibold">Educational Background:</span> {{ $application->educational_background_label ?: 'Not provided' }}</p>
							<p><span class="font-semibold">Professional Background:</span> {{ $application->bio ?: 'Not provided' }}</p>
						</div>
					</section>

					<section class="rounded-2xl border border-gray-200 bg-white p-5">
						<h3 class="text-base font-bold text-gray-900">Section 2 - Submitted Documents</h3>

						<div class="mt-4 space-y-6">
							<div>
								<p class="text-sm font-semibold text-gray-800">Tier 1 Documents</p>
								<div class="mt-3 grid gap-4 md:grid-cols-2">
									@foreach($tierOneDocuments as $document)
										@if(! empty($document['path']))
											@php
												$documentPath = (string) $document['path'];
												$documentUrl = asset('storage/' . $documentPath);
												$extension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
												$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
												$isPdf = $extension === 'pdf';
											@endphp
											<div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
												<div class="flex items-center justify-between gap-3">
													<p class="text-sm font-semibold text-gray-900">{{ $document['label'] }}</p>
													<span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-bold uppercase text-gray-600">{{ $extension ?: 'file' }}</span>
												</div>

												@if($isImage)
													<img src="{{ $documentUrl }}" alt="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200 object-cover">
												@elseif($isPdf)
													<iframe src="{{ $documentUrl }}#toolbar=0&navpanes=0" title="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200"></iframe>
												@else
													<p class="mt-3 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-500">Inline preview is not available for this file type.</p>
												@endif

												<div class="mt-3 flex gap-2">
													<button type="button"
															@click="openDocumentPreview(@js($documentUrl), @js($document['label']), @js($isImage ? 'image' : ($isPdf ? 'pdf' : 'file')))"
															class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
															title="Preview Document"
															aria-label="Preview Document">
														<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
														</svg>
													</button>
													<a href="{{ $documentUrl }}" download class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100" title="Download Document" aria-label="Download Document">
														<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
														</svg>
													</a>
												</div>
											</div>
										@endif
									@endforeach
								</div>
							</div>

							<div>
								<p class="text-sm font-semibold text-gray-800">Tier 2 Documents</p>
								<div class="mt-3 grid gap-4 md:grid-cols-2">
									@foreach($tierTwoDocuments as $document)
										@if(! empty($document['path']))
											@php
												$documentPath = (string) $document['path'];
												$documentUrl = asset('storage/' . $documentPath);
												$extension = strtolower(pathinfo($documentPath, PATHINFO_EXTENSION));
												$isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
												$isPdf = $extension === 'pdf';
											@endphp
											<div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4">
												<div class="flex items-center justify-between gap-3">
													<p class="text-sm font-semibold text-gray-900">{{ $document['label'] }}</p>
													<span class="rounded-full bg-gray-200 px-2 py-0.5 text-[11px] font-bold uppercase text-gray-600">{{ $extension ?: 'file' }}</span>
												</div>

												@if($isImage)
													<img src="{{ $documentUrl }}" alt="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200 object-cover">
												@elseif($isPdf)
													<iframe src="{{ $documentUrl }}#toolbar=0&navpanes=0" title="{{ $document['label'] }}" class="mt-3 h-44 w-full rounded-lg border border-gray-200"></iframe>
												@else
													<p class="mt-3 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-500">Inline preview is not available for this file type.</p>
												@endif

												<div class="mt-3 flex gap-2">
													<button type="button"
															@click="openDocumentPreview(@js($documentUrl), @js($document['label']), @js($isImage ? 'image' : ($isPdf ? 'pdf' : 'file')))"
															class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100"
															title="Preview Document"
															aria-label="Preview Document">
														<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
														</svg>
													</button>
													<a href="{{ $documentUrl }}" download class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-100" title="Download Document" aria-label="Download Document">
														<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10l5 5 5-5" />
															<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15V3" />
														</svg>
													</a>
												</div>
											</div>
										@endif
									@endforeach

									@if(collect($tierTwoDocuments)->every(fn ($doc) => empty($doc['path'])))
										<div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-500 md:col-span-2">
											No Tier 2 documents were submitted.
										</div>
									@endif
								</div>
							</div>
						</div>
					</section>

					<section class="rounded-2xl border border-gray-200 bg-white p-5">
						<h3 class="text-base font-bold text-gray-900">Section 3 - Learner Data Snapshot</h3>
						<div class="mt-4 grid grid-cols-1 gap-3 text-sm text-gray-700 md:grid-cols-3">
							<div class="rounded-xl border border-sky-100 bg-sky-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wider text-sky-600">Total Enrolled Modules</p>
								<p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->enrolled_modules_count ?? 0) }}</p>
							</div>
							<div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Total Finished Modules</p>
								<p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->finished_modules_count ?? 0) }}</p>
							</div>
							<div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
								<p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Total Certificates Earned</p>
								<p class="mt-2 text-2xl font-bold text-gray-900">{{ (int) ($application->user->certificates_earned_count ?? 0) }}</p>
							</div>
						</div>

						<div class="mt-4 rounded-xl border border-gray-200 bg-gray-50/60 p-4">
							<button type="button"
									@click="toggleFinishedModules({{ $application->id }})"
									class="flex w-full items-center justify-between text-left text-sm font-semibold text-gray-800">
								<span>Finished Modules Breakdown</span>
								<svg class="h-4 w-4 transition-transform" :class="expandedModules[{{ $application->id }}] ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
								</svg>
							</button>

							<div x-show="expandedModules[{{ $application->id }}]" x-cloak class="mt-3">
								@if($completedModules->isEmpty())
									<p class="text-sm text-gray-500">No completed modules yet.</p>
								@else
									<ul class="space-y-2">
										@foreach($completedModules as $enrollment)
											<li class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
												<div class="font-medium text-gray-900">{{ $enrollment->module?->title ?? 'Untitled Module' }}</div>
												<div class="text-xs text-gray-500">Finished {{ optional($enrollment->completed_at)->format('M d, Y h:i A') }}</div>
											</li>
										@endforeach
									</ul>
								@endif
							</div>
						</div>
					</section>

					<section class="rounded-2xl border border-gray-200 bg-white p-5"
							 x-data="{ approvePanel: false, rejectPanel: @js($rejectPanelDefaultOpen), selectedCode: @js($defaultReasonCode), chars: {{ strlen((string) $defaultReasonNote) }} }">
						@if($application->status === 'pending')
							<div class="flex flex-wrap items-center gap-3">
								<button type="button" @click="approvePanel = !approvePanel; rejectPanel = false" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Approve Application</button>
								<button type="button" @click="rejectPanel = !rejectPanel; approvePanel = false" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Reject Application</button>
							</div>

							<form method="POST" action="{{ route('admin.instructor-applications.approve', $application) }}" x-show="approvePanel" x-cloak class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
								@csrf
								<p class="text-sm text-emerald-900">Confirm approval to transition this learner into an instructor account.</p>
								<div class="mt-3 flex justify-end gap-2">
									<button type="button" @click="approvePanel = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Cancel</button>
									<button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm Approval</button>
								</div>
							</form>

							<form method="POST" action="{{ route('admin.instructor-applications.reject', $application) }}" x-show="rejectPanel" x-cloak class="mt-4 space-y-4 rounded-xl border border-rose-200 bg-rose-50 p-4">
								@csrf
								<input type="hidden" name="review_application_id" value="{{ $application->id }}">

								<div>
									<label class="block text-sm font-medium text-gray-700" for="rejection_reason_code_{{ $application->id }}">Reason category</label>
									<select id="rejection_reason_code_{{ $application->id }}" name="rejection_reason_code" x-model="selectedCode" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
										<option value="" disabled>Select a reason</option>
										@foreach(\App\Enums\InstructorApplicationRejectionReason::cases() as $reason)
											<option value="{{ $reason->value }}">{{ $reason->label() }}</option>
										@endforeach
									</select>
									@error('rejection_reason_code')
										<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
									@enderror
								</div>

								<div>
									<label class="block text-sm font-medium text-gray-700" for="rejection_reason_note_{{ $application->id }}">Custom note <span class="text-xs text-gray-500">(required when reason is Other)</span></label>
									<textarea id="rejection_reason_note_{{ $application->id }}"
											  name="rejection_reason_note"
											  rows="4"
											  x-bind:required="selectedCode === 'other'"
											  x-on:input="chars = $event.target.value.length"
											  class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
											  placeholder="Add specific guidance the learner can use to improve and reapply.">{{ $defaultReasonNote }}</textarea>
									<div class="mt-1 flex justify-between text-xs text-gray-500">
										<span x-show="selectedCode === 'other'" x-cloak>Required for Other reason</span>
										<span x-show="selectedCode !== 'other'" x-cloak>Optional but recommended for clarity</span>
										<span x-text="chars + ' characters'"></span>
									</div>
									@error('rejection_reason_note')
										<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>
									@enderror
								</div>

								<div class="rounded-lg bg-white/80 p-3 text-xs text-rose-800">Learners receive this rationale in their notification. Keep wording respectful, specific, and actionable.</div>

								<div class="flex justify-end gap-2">
									<button type="button" @click="rejectPanel = false" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm">Cancel</button>
									<button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Submit Rejection</button>
								</div>
							</form>
						@elseif($application->status === 'approved')
							<p class="text-sm text-emerald-700">Approved by {{ $application->approvedBy?->name ?? 'N/A' }} on {{ optional($application->approved_at)->format('M d, Y h:i A') }}.</p>
						@else
							@php
								$reasonCode = $application->rejection_reason_code;
								$reasonLabel = $reasonCode
									? (\App\Enums\InstructorApplicationRejectionReason::tryFrom($reasonCode)?->label() ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $reasonCode)))
									: null;
							@endphp
							<p class="text-sm text-rose-700">Rejected by {{ $application->approvedBy?->name ?? 'N/A' }} on {{ optional($application->approved_at)->format('M d, Y h:i A') }}.</p>
							@if($reasonLabel)
								<p class="mt-2 text-sm text-gray-700"><span class="font-medium">Reason Category:</span> {{ $reasonLabel }}</p>
							@endif
							@if($application->rejection_reason_note)
								<p class="mt-1 text-sm text-gray-700"><span class="font-medium">Admin Note:</span> {{ $application->rejection_reason_note }}</p>
							@elseif($application->rejection_reason)
								<p class="mt-1 text-sm text-gray-700"><span class="font-medium">Reason:</span> {{ $application->rejection_reason }}</p>
							@endif
						@endif
					</section>
				</div>
			</div>
		</div>
	@endforeach

	<div x-show="documentPreviewOpen"
		 x-cloak
		 @keydown.escape.window="if (documentPreviewOpen) closeDocumentPreview()"
		 class="fixed inset-0 z-[70] flex items-center justify-center p-4 sm:p-6 lg:p-8">
		<div x-show="documentPreviewOpen"
			 x-transition.opacity
			 class="fixed inset-0 bg-gray-900/45 backdrop-blur-lg"
			 @click="closeDocumentPreview()"></div>

		<div x-show="documentPreviewOpen"
			 x-transition:enter="ease-out duration-300"
			 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
			 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
			 x-transition:leave="ease-in duration-200"
			 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
			 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
			 class="relative z-50 w-full max-w-5xl overflow-hidden rounded-2xl bg-white shadow-2xl">
			<div class="border-b border-gray-100 bg-gray-50/80 px-6 py-4">
				<div class="flex items-center justify-between">
					<div>
						<p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-600">Document Preview</p>
						<h2 class="mt-1 text-base font-bold text-gray-900" x-text="documentPreviewTitle"></h2>
					</div>
					<button type="button" @click="closeDocumentPreview()" class="rounded-full p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
						<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>
				</div>
			</div>

			<div class="max-h-[80vh] space-y-4 overflow-y-auto bg-white px-6 py-6">
				<template x-if="documentPreviewType === 'image'">
					<img :src="documentPreviewUrl" alt="Document preview" class="w-full rounded-lg border border-gray-200 bg-white object-contain">
				</template>

				<template x-if="documentPreviewType === 'pdf'">
					<iframe :src="documentPreviewUrl + '#toolbar=0&navpanes=0'" title="Document preview" class="h-[70vh] w-full rounded-lg border border-gray-200"></iframe>
				</template>

				<template x-if="documentPreviewType === 'file'">
					<div class="rounded-xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-600">
						<p>Inline preview is not available for this file type.</p>
						<a :href="documentPreviewUrl" download class="mt-4 inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-600">Download File</a>
					</div>
				</template>
			</div>
		</div>
	</div>
</div>
@endsection
