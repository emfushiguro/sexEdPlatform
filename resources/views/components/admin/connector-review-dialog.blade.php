@props(['id', 'title', 'description', 'action', 'round', 'submit', 'destructive' => false])

<dialog id="{{ $id }}" data-review-dialog @click.self="$event.currentTarget.close()"
        aria-labelledby="{{ $id }}-title" aria-describedby="{{ $id }}-description"
        class="m-auto max-h-[90vh] w-[min(92vw,34rem)] overflow-y-auto rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/60">
    <div class="p-6 sm:p-7">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h3 id="{{ $id }}-title" class="break-words text-xl font-bold text-gray-900">{{ $title }}</h3>
                <p id="{{ $id }}-description" class="mt-2 break-words text-sm leading-6 text-gray-600">{{ $description }}</p>
            </div>
            <button type="button" @click="closeDialog($event.currentTarget)" aria-label="Close dialog"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 focus:ring-2 focus:ring-brand-500">
                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M6 18 18 6"/></svg>
            </button>
        </div>
        <form method="POST" action="{{ $action }}" class="mt-6 space-y-4" @submit="submitReview($event)">
            @csrf
            <input type="hidden" name="submission_round" value="{{ $round }}">
            <input type="hidden" name="review_dialog" value="{{ $id }}">
            @if($errors->any() && old('review_dialog') === $id)
                <div role="alert" class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            {{ $slot }}
            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                <button type="button" @click="closeDialog($event.currentTarget)" class="min-h-11 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" class="min-h-11 rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 {{ $destructive ? 'bg-rose-700 hover:bg-rose-800' : 'bg-brand-700 hover:bg-brand-800' }}">{{ $submit }}</button>
            </div>
        </form>
    </div>
</dialog>
