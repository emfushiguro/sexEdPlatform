<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-10 text-center">
            <h2 class="text-4xl font-bold text-white leading-tight">Stage Two</h2>
            <p class="mt-4 max-w-sm text-white/80">Submit your teaching qualifications for final administrator review.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-purple-900">Instructor application</h1>
            <p class="mt-2 text-sm text-gray-600">Stage One identity approval is separate from final instructor approval.</p>
        </div>

        @if ($errors->any())
            <div id="application-error-summary" data-error-summary class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4" role="alert" tabindex="-1">
                <h2 class="font-semibold text-red-800">Please review the highlighted requirements.</h2>
                <ul class="mt-2 list-disc pl-5 text-sm text-red-700">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if (session('status')) <div class="mb-6 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800" role="status">{{ session('status') }}</div> @endif

        <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
            <h2 class="font-semibold text-gray-900">Personal information</h2>
            <p class="mt-1">{{ $user->full_name }} · {{ $user->birthdate?->format('M j, Y') }}</p>
            <p>{{ $user->profile?->city?->name ?? $user->profile?->location ?? 'Address on file' }}</p>
            <a class="mt-2 inline-block text-purple-700 underline" href="{{ route('instructor-application.personal-information.edit') }}">Review personal information</a>
        </div>

        <form method="POST" action="{{ route('instructor-application.application.submit') }}" enctype="multipart/form-data" class="space-y-6" data-instructor-form @if ($errors->any()) aria-describedby="application-error-summary" @endif>
            @csrf
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Required documents</h2>
                @if ($requiresGovernmentId)
                    <label class="mt-4 block text-sm font-medium text-gray-700" for="government_id">Government ID <span class="text-red-600">*</span></label>
                    <input id="government_id" name="government_id" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-lg border p-2" data-draft-upload required>
                @else
                    <p class="mt-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">Government ID is already covered by your approved Stage One identity review.</p>
                    <label class="mt-4 block text-sm font-medium text-gray-700" for="government_id">Optional replacement Government ID</label>
                    <input id="government_id" name="government_id" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-lg border p-2" data-draft-upload>
                @endif
                <p class="mt-1 text-xs text-gray-500" data-upload-name-for="government_id" aria-live="polite">No file selected</p>
                <label class="mt-4 block text-sm font-medium text-gray-700" for="clearance">NBI / Police clearance <span class="text-red-600">*</span></label>
                <input id="clearance" name="clearance" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-lg border p-2" data-draft-upload @if (! $application?->clearance_path) required @endif>
                <p class="mt-1 text-xs text-gray-500" data-upload-name-for="clearance" aria-live="polite">No file selected</p>
                <label class="mt-4 block text-sm font-medium text-gray-700" for="cv_resume">CV or resume <span class="text-red-600">*</span></label>
                <input id="cv_resume" name="cv_resume" type="file" accept=".pdf,.doc,.docx" class="mt-1 block w-full rounded-lg border p-2" data-draft-upload @if (! $application?->cv_resume_path) required @endif>
                <p class="mt-1 text-xs text-gray-500" data-upload-name-for="cv_resume" aria-live="polite">No file selected</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-gray-900">Teaching expertise</h2>
                <label class="mt-4 block text-sm font-medium text-gray-700" for="educational_background">Educational background</label>
                <select id="educational_background" name="educational_background" class="mt-1 block w-full rounded-lg border p-2">
                    <option value="">Select one</option>
                    @foreach (\App\Models\InstructorApplication::EDUCATIONAL_BACKGROUND_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(old('educational_background', $application?->educational_background) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="mt-4 block text-sm font-medium text-gray-700" for="educational_background_other">If other, specify</label>
                <input id="educational_background_other" name="educational_background_other" value="{{ old('educational_background_other') }}" class="mt-1 block w-full rounded-lg border p-2">
                <label class="mt-4 block text-sm font-medium text-gray-700" for="bio">Professional background</label>
                <textarea id="bio" name="bio" maxlength="500" rows="4" class="mt-1 block w-full rounded-lg border p-2">{{ old('bio', $application?->bio) }}</textarea>
                <p class="mt-2 text-sm text-gray-600">Upload at least one Tier 2 document.</p>
                @foreach (['teaching_credential' => 'Teaching credential', 'sexed_certificate' => 'Certificate', 'professional_license' => 'Professional license'] as $field => $label)
                    <label class="mt-3 block text-sm font-medium text-gray-700" for="{{ $field }}">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full rounded-lg border p-2" data-draft-upload>
                    <p class="mt-1 text-xs text-gray-500" data-upload-name-for="{{ $field }}" aria-live="polite">No file selected</p>
                @endforeach
            </div>

            <label class="flex items-start gap-3 text-sm text-gray-700"><input type="checkbox" name="confirmation" value="1" required class="mt-1"> I confirm that the information and documents submitted are authentic.</label>
            <div class="flex flex-wrap gap-3">
                <button type="submit" formaction="{{ route('instructor-application.application.save-draft') }}" class="rounded-lg border border-purple-300 px-5 py-2.5 font-semibold text-purple-700">Save draft</button>
                <button type="submit" class="rounded-lg bg-purple-700 px-5 py-2.5 font-semibold text-white">Submit application</button>
            </div>
            <p data-submit-status class="text-sm text-gray-600" role="status" aria-live="polite"></p>
        </form>
    </div>
</x-auth-split-layout>
