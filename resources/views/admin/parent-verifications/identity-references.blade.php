@extends('layouts.admin')
@section('title', 'Document reference examples')
@section('content')
<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-8" x-data="{ editor: @js($errors->any() ? (string) old('reference_editor', 'new') : null) }">
    <header class="flex items-start justify-between gap-4">
        <div><a href="{{ route('admin.identity-references.index') }}" class="text-sm font-semibold text-purple-700 underline">Back to Reference Library</a><h1 class="mt-3 text-2xl font-bold text-gray-900">{{ $label }} reference examples</h1><p class="mt-2 max-w-3xl text-sm text-gray-600">Use public specimens for guidance. A visual match does not establish authenticity. Check the official source before deciding.</p></div>
        <button type="button" @click="editor = 'new'" aria-label="Add reference example" title="Add reference example" class="mt-3 flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-purple-800 text-white hover:bg-purple-900"><svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg></button>
    </header>
    @include('admin.parent-verifications.partials.id-reference', ['idType' => $type, 'idLabel' => $label, 'showLibraryLink' => false])
    @if(session('success'))<p role="status" class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 p-4 text-red-800"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p class="mt-2">Select the attachment again before saving.</p></div>@endif
    <div class="grid gap-5 md:grid-cols-2">
    @forelse($examples as $example)
        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <a href="{{ route('admin.identity-references.image', [$type, $example->id]) }}" target="_blank" rel="noopener" class="block bg-gray-50 p-4"><img src="{{ route('admin.identity-references.image', [$type, $example->id]) }}" alt="{{ $label }} public example; opens full size" class="h-64 w-full object-contain"></a>
            <div class="p-5"><div class="flex items-start justify-between gap-3"><h2 class="text-lg font-bold text-gray-900">{{ $example->version ?: $label }}</h2><button type="button" @click="editor = @js((string) $example->id)" aria-label="Edit reference example {{ $example->id }}" title="Edit reference example" class="rounded-lg border border-purple-200 p-2 text-purple-800 hover:bg-purple-50"><svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m16 4 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg></button></div>
            <dl class="mt-3 space-y-2 text-sm">@if($example->issuer)<div><dt class="font-semibold">Issuer</dt><dd>{{ $example->issuer }}</dd></div>@endif @if($example->published_on)<div><dt class="font-semibold">Publication date</dt><dd>{{ $example->published_on }}</dd></div>@endif</dl>
            <div class="mt-4 flex flex-wrap gap-4 text-sm font-semibold"><a href="{{ $example->source_url }}" target="_blank" rel="noopener noreferrer" class="text-purple-700 underline">Official source</a>@if($example->verification_url)<a href="{{ $example->verification_url }}" target="_blank" rel="noopener noreferrer" class="text-purple-700 underline">Official verification</a>@endif</div><p class="mt-3 text-xs font-semibold text-amber-800">Public example; appearance alone is not proof.</p></div>
        </article>
    @empty
        <p class="rounded-xl border border-dashed border-gray-300 bg-white p-6 text-gray-600 md:col-span-2">No public specimens have been added. Use the official guidance above.</p>
    @endforelse
    </div>
    @foreach(collect([null])->concat($examples) as $record)
        @php $editorId = (string) ($record?->id ?? 'new'); @endphp
        <dialog x-data="{ thumbnail: null }" x-effect="if (editor === @js($editorId)) { if (!$el.open) $el.showModal(); } else if ($el.open) { $el.close(); }" @close="$el.querySelector('form').reset(); if (thumbnail) URL.revokeObjectURL(thumbnail); thumbnail = null; editor = null" @click="if ($event.target === $el) $el.close()" aria-labelledby="reference-editor-{{ $editorId }}" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border-0 bg-white p-0 shadow-2xl backdrop:bg-gray-950/60">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4"><h2 id="reference-editor-{{ $editorId }}" class="text-lg font-bold text-gray-900">{{ $record ? 'Edit reference example' : 'Add reference example' }}</h2><button type="button" @click="$el.closest('dialog').close()" aria-label="Close reference editor" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100">&#10005;</button></div>
            <form method="POST" action="{{ $record ? route('admin.identity-references.update', [$type, $record->id]) : route('admin.identity-references.store', $type) }}" enctype="multipart/form-data" class="grid gap-4 p-6 sm:grid-cols-2">@csrf @if($record) @method('PUT') @endif
                <p class="text-sm text-gray-600 sm:col-span-2">Use an authorized public specimen without real personal information. Confirm its source.</p>
                @include('admin.parent-verifications.partials.identity-reference-fields', ['record' => $record])
                <button class="rounded-lg bg-purple-800 px-4 py-3 font-semibold text-white hover:bg-purple-900 sm:col-span-2">{{ $record ? 'Save changes' : 'Add example' }}</button>
            </form>
        </dialog>
    @endforeach
</div>
@endsection
