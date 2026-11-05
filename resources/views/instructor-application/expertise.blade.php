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
    $educationValue = old('educational_background', '');
    $savedEducation = (string) ($application?->educational_background ?? '');
    if ($educationValue === '' && array_key_exists($savedEducation, \App\Models\InstructorApplication::EDUCATIONAL_BACKGROUND_LABELS)) {
        $educationValue = $savedEducation;
    } elseif ($educationValue === '' && $savedEducation !== '') {
        $educationValue = 'other';
    }
    $educationOther = old('educational_background_other', $educationValue === 'other' && $savedEducation !== 'other' ? $savedEducation : '');
@endphp

<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-10 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/60">Stage Two</p>
            <h2 class="mt-3 text-4xl font-bold leading-tight text-white">Teaching expertise</h2>
            <p class="mt-4 max-w-sm text-white/80">Tell us about your background and upload at least one proof of expertise.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-purple-700">Instructor application</p>
            <h1 class="mt-2 text-3xl font-bold text-purple-950">Teaching expertise</h1>
            <p class="mt-2 text-sm text-gray-600">Complete the final Stage Two details for administrator review.</p>
        </div>

        @include('instructor-application.partials.stage-two-stepper', ['step' => 'expertise'])

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

        @php
            $teachingCredentialName = $documentName($application, 'teaching_credential');
            $certificateName = $documentName($application, 'sexed_certificate');
            $professionalLicenseName = $documentName($application, 'professional_license');
        @endphp

        <form method="POST" action="{{ route('instructor-application.application.submit') }}" enctype="multipart/form-data" class="space-y-6" data-instructor-form @if ($errors->any()) aria-describedby="application-error-summary" @endif x-data="{ loading: false, action: 'submit' }" @submit="loading = true">
            @csrf

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Teaching expertise</h2>
                        <p class="mt-1 text-sm text-gray-600">Share the background an administrator will use during final review.</p>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-400">Step 2 of 2</span>
                </div>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="educational_background" class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600">Educational background <span class="text-rose-500">*</span></label>
                        <select id="educational_background" name="educational_background" class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-100" required>
                            <option value="">Select one</option>
                            @foreach (\App\Models\InstructorApplication::EDUCATIONAL_BACKGROUND_LABELS as $value => $label)
                                <option value="{{ $value }}" @selected($educationValue === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('educational_background')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="educational_background_other" class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600">If other, specify</label>
                        <input id="educational_background_other" name="educational_background_other" value="{{ $educationOther }}" maxlength="100" class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-100">
                        @error('educational_background_other')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="bio" class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600">Professional background</label>
                            <span class="text-[11px] font-medium text-gray-400">Maximum 500 characters</span>
                        </div>
                        <textarea id="bio" name="bio" maxlength="500" rows="5" class="w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-900 shadow-sm outline-none transition focus:border-purple-400 focus:ring-2 focus:ring-purple-100" placeholder="Describe your experience, subjects, or teaching approach.">{{ old('bio', $application?->bio) }}</textarea>
                        @error('bio')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Proof of expertise</h2>
                    <p class="mt-1 text-sm text-gray-600">Upload at least one teaching credential, certificate, or professional license.</p>
                </div>
                <div class="mt-5 space-y-5">
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'teaching_credential',
                        'name' => 'teaching_credential',
                        'label' => 'Teaching credential',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                        'help' => 'PDF, JPEG, PNG, or WebP - maximum 5 MB',
                        'existingName' => $teachingCredentialName,
                    ])
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'sexed_certificate',
                        'name' => 'sexed_certificate',
                        'label' => 'Certificate',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                        'help' => 'PDF, JPEG, PNG, or WebP - maximum 5 MB',
                        'existingName' => $certificateName,
                    ])
                    @include('instructor-application.partials.upload-field', [
                        'id' => 'professional_license',
                        'name' => 'professional_license',
                        'label' => 'Professional license',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.webp',
                        'help' => 'PDF, JPEG, PNG, or WebP - maximum 5 MB',
                        'existingName' => $professionalLicenseName,
                    ])
                </div>
                @error('tier2')<p class="mt-4 rounded-xl border border-rose-100 bg-rose-50 p-3 text-sm text-rose-700">{{ $message }}</p>@enderror
            </section>

            <label class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                <input type="checkbox" name="confirmation" value="1" x-bind:required="action === 'submit'" class="mt-1 h-4 w-4 rounded border-gray-300 text-purple-700 focus:ring-purple-500">
                <span>I confirm that the information and documents submitted are authentic.</span>
            </label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('instructor-application.application.edit') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2">Back to required documents</a>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="submit" formaction="{{ route('instructor-application.application.expertise.save-draft') }}" @click="action = 'draft'" :disabled="loading" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-purple-200 bg-white px-4 py-2.5 text-sm font-semibold text-purple-700 transition hover:bg-purple-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!loading || action !== 'draft'">Save draft</span>
                        <span x-show="loading && action === 'draft'" x-cloak>Saving draft...</span>
                    </button>
                    <button type="submit" @click="action = 'submit'" :disabled="loading" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-purple-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                        <span x-show="!loading || action !== 'submit'">Submit application</span>
                        <span x-show="loading && action === 'submit'" x-cloak class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Z"></path></svg>
                            Submitting...
                        </span>
                    </button>
                </div>
            </div>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </form>
    </div>
</x-auth-split-layout>
