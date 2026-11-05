@php
    $documentName = static function ($application, string $field): ?string {
        if (! $application) {
            return null;
        }

        $name = data_get($application->application_metadata, "documents.{$field}.original_name");

        return filled($name)
            ? (string) $name
            : (filled($application->{$field.'_path'}) ? 'Previously uploaded file' : null);
    };
    $governmentIdName = $documentName($application, 'government_id');
    $clearanceName = $documentName($application, 'clearance');
    $cvResumeName = $documentName($application, 'cv_resume');
@endphp

<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-10 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Stage Two</p>
            <h2 class="mt-3 text-4xl font-bold leading-tight text-white">Required documents</h2>
            <p class="mt-4 max-w-sm text-white/80">Save your core documents first. You will add your teaching expertise on the next step.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Instructor application</p>
            <h1 class="mt-2 text-3xl font-bold text-purple-950">Required documents</h1>
            <p class="mt-2 text-sm text-gray-600">Save these documents now, then continue to your teaching expertise.</p>
        </div>

        @include('instructor-application.partials.stage-two-stepper', ['step' => 'documents'])

        @if ($errors->any())
            <div id="application-error-summary" data-error-summary class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert" tabindex="-1">
                <h2 class="font-semibold text-red-800">Please review the highlighted requirements.</h2>
                <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
        @endif

        <div class="mb-6 rounded-2xl border border-gray-200 bg-gray-50 p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">Personal information</p>
            <p class="mt-2 font-semibold text-gray-900">{{ $user->full_name }} <span class="font-normal text-gray-500">&middot;</span> <span class="font-normal text-gray-700">{{ $user->birthdate?->format('M j, Y') }}</span></p>
            <p class="text-sm text-gray-600">{{ $user->profile?->city?->name ?? $user->profile?->location ?? 'Address on file' }}</p>
            <a class="mt-3 inline-flex min-h-10 items-center text-sm font-semibold text-purple-700 underline decoration-purple-300 underline-offset-2 hover:text-purple-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2" href="{{ route('instructor-application.personal-information.edit') }}">Review personal information</a>
        </div>

        <form method="POST" action="{{ route('instructor-application.application.documents.save') }}" enctype="multipart/form-data" class="space-y-6" data-instructor-form @if ($errors->any()) aria-describedby="application-error-summary" @endif x-data="{ loading: false }" @submit="loading = true">
            @csrf

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Required documents</h2>
                        <p class="mt-1 text-sm text-gray-600">Upload clear, readable files. Existing draft files stay saved when you return later.</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Step 1 of 2</span>
                </div>

                @if (! $requiresGovernmentId)
                    <p class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 p-3 text-sm leading-6 text-emerald-800">Government ID is already covered by your approved Stage One identity review. You may upload a replacement if needed.</p>
                @endif

                <div class="mt-5 space-y-5">
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'government_id',
                        'name' => 'government_id',
                        'label' => 'Government ID',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                        'help' => 'PDF, JPEG, PNG, or WebP - maximum 5 MB',
                        'required' => $requiresGovernmentId,
                        'existingName' => $governmentIdName,
                    ])
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'clearance',
                        'name' => 'clearance',
                        'label' => 'NBI / Police clearance',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                        'help' => 'PDF, JPEG, PNG, or WebP - maximum 5 MB',
                        'required' => true,
                        'existingName' => $clearanceName,
                    ])
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'cv_resume',
                        'name' => 'cv_resume',
                        'label' => 'CV or resume',
                        'accept' => '.pdf,.doc,.docx',
                        'help' => 'PDF, DOC, or DOCX - maximum 5 MB',
                        'required' => true,
                        'existingName' => $cvResumeName,
                    ])
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('instructor-application.dashboard') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2">Back to application hub</a>
                <button type="submit" :disabled="loading" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!loading">Save and continue</span>
                    <span x-show="loading" x-cloak class="inline-flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>
                        Saving documents...
                    </span>
                </button>
            </div>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </form>
    </div>
</x-auth-split-layout>
