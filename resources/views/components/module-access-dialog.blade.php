@props(['id', 'title'])

<dialog id="{{ $id }}" aria-labelledby="{{ $id }}-title" @click.self="$event.currentTarget.close()"
    class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 text-left shadow-2xl backdrop:bg-slate-950/60">
    <div class="p-6">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-title" class="text-lg font-bold text-gray-900">{{ $title }}</h2>
            <button type="button" autofocus @click="$event.currentTarget.closest('dialog').close()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">Close</button>
        </div>
        <div class="mt-5">{{ $slot }}</div>
    </div>
</dialog>
