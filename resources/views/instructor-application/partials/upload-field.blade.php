@php
    $id = $id ?? '';
    $name = $name ?? $id;
    $label = $label ?? 'Attachment';
    $accept = $accept ?? '.pdf,.jpg,.jpeg,.png';
    $help = $help ?? 'PDF, JPEG, or PNG - maximum 5 MB';
    $required = (bool) ($required ?? false);
    $existingName = $existingName ?? null;
@endphp

<div x-data="{
    fileName: '',
    fileType: '',
    fileSize: '',
    isDragging: false,
    setFile(file) {
        if (!file) return;
        this.fileName = file.name;
        this.fileType = file.type || 'File';
        this.fileSize = `${Math.max(1, Math.round(file.size / 1024))} KB`;
    },
    handleDrop(event) {
        this.isDragging = false;
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.$refs.input.files = transfer.files;
        this.setFile(file);
    },
    clearFile() {
        this.$refs.input.value = '';
        this.fileName = '';
        this.fileType = '';
        this.fileSize = '';
    }
}">
    <div class="flex items-center justify-between gap-3 mb-2">
        <label for="{{ $id }}" class="block text-xs font-semibold uppercase tracking-[0.12em] text-gray-600">{{ $label }} @if ($required && ! $existingName)<span class="text-rose-500">*</span>@endif</label>
        @if ($existingName)
            <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-600">Saved</span>
        @elseif ($required)
            <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-rose-500">Required</span>
        @else
            <span class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400">Optional</span>
        @endif
    </div>

    <div class="rounded-2xl border-2 border-dashed border-purple-200 bg-purple-50/35 p-3 transition focus-within:ring-4 focus-within:ring-purple-100"
         :class="isDragging ? 'border-purple-500 bg-purple-100/70 ring-4 ring-purple-100' : 'hover:border-purple-400 hover:bg-purple-50/70'"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="handleDrop($event)">
        <label for="{{ $id }}" class="group block cursor-pointer rounded-xl outline-none">
            <input id="{{ $id }}" name="{{ $name }}" type="file" accept="{{ $accept }}" class="sr-only" x-ref="input" @change="setFile($event.target.files[0])" @if ($required && ! $existingName) required @endif>

            <div x-show="!fileName" class="flex flex-col items-center justify-center px-4 py-6 text-center">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-purple-600 shadow-sm ring-1 ring-purple-100 transition group-hover:scale-105 group-hover:bg-purple-100">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4 4 4M5 16.5v1.25A2.25 2.25 0 0 0 7.25 20h9.5A2.25 2.25 0 0 0 19 17.75V16.5" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-gray-900">Drop a file here or <span class="text-purple-700 underline decoration-purple-300 underline-offset-2">browse</span></p>
                <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
            </div>

            <div x-show="fileName" x-cloak class="flex items-center gap-3 rounded-xl bg-white px-3.5 py-3 text-left shadow-sm ring-1 ring-purple-100">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" />
                    </svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-gray-900" x-text="fileName"></span>
                    <span class="mt-0.5 block text-xs text-gray-500"><span x-text="fileType"></span><span aria-hidden="true"> - </span><span x-text="fileSize"></span></span>
                </span>
                <span class="shrink-0 text-xs font-semibold text-purple-700">Replace</span>
            </div>
        </label>
    </div>

    @if ($existingName)
        <div x-show="!fileName" class="mt-3 flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3.5 py-3" role="status">
            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                </svg>
            </span>
            <span class="min-w-0 text-sm"><span class="font-semibold text-emerald-800">Previously uploaded</span><span class="mt-0.5 block truncate text-xs text-emerald-700">{{ $existingName }}</span></span>
        </div>
    @endif

    <div x-show="fileName" x-cloak class="mt-3 flex justify-end">
        <button type="button" class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-300" @click="clearFile()">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
            Clear selection
        </button>
    </div>
</div>
