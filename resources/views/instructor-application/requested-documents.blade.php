@php
    $labels = ['government_id' => 'Government ID', 'clearance' => 'NBI / Police clearance', 'cv_resume' => 'CV or resume', 'teaching_credential' => 'Teaching credential', 'sexed_certificate' => 'Sex education certificate', 'professional_license' => 'Professional license'];
    $changeRequest = $application->application_metadata['document_change_request'];
@endphp
<x-auth-split-layout :showTabs="false">
    <x-slot name="panel"><div class="flex h-full flex-col items-center justify-center p-10 text-center"><h2 class="text-4xl font-bold text-white">Update your documents</h2><p class="mt-4 text-white/80">Upload the requested replacements to continue your application review.</p></div></x-slot>
    <h1 class="text-2xl font-bold text-purple-900">Replace requested documents</h1>
    <p class="mt-2 text-sm text-gray-600">Submit a replacement for each document below.</p>
    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>Message from the reviewer</strong><p class="mt-1 whitespace-pre-line">{{ $changeRequest['note'] }}</p></div>
    @if($errors->any())<p class="mt-4 text-sm text-rose-700" role="alert">{{ $errors->first() }}</p>@endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('instructor-application.documents.replace', $application) }}" class="mt-5 space-y-4" x-data="{ submitting: false }" @submit="submitting = true">
        @csrf
        <input type="hidden" name="request_token" value="{{ $changeRequest['token'] }}">
        @foreach($changeRequest['slots'] as $slot)
            <label class="block rounded-xl border border-gray-200 p-4 text-sm font-semibold text-gray-800">{{ $labels[$slot] ?? 'Document' }}
                <input type="file" name="{{ $slot }}" required accept="{{ $slot === 'cv_resume' ? '.pdf,.doc,.docx' : '.pdf,.jpg,.jpeg,.png,.webp' }}" class="mt-2 block w-full text-sm font-normal file:mr-3 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-2 file:font-semibold file:text-purple-700">
                <span class="mt-2 block text-xs font-normal text-gray-500">{{ $slot === 'cv_resume' ? 'PDF, DOC, or DOCX' : 'PDF, JPEG, PNG, or WebP' }} - maximum 5 MB.</span>
            </label>
        @endforeach
        <button type="submit" :disabled="submitting" class="min-h-11 rounded-xl bg-purple-700 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60">Submit replacements</button>
    </form>
</x-auth-split-layout>
