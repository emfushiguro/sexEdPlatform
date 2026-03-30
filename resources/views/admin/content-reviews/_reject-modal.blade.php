@php
    use App\Enums\ModuleReviewRejectionReason;
@endphp

<form method="POST" action="{{ route('admin.content-reviews.reject', $reviewRequest) }}" class="space-y-3">
    @csrf
    <div>
        <label for="reason_code" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Rejection Reason</label>
        <select id="reason_code" name="reason_code" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            <option value="">Select a reason</option>
            @foreach(ModuleReviewRejectionReason::cases() as $reason)
                <option value="{{ $reason->value }}" @selected(old('reason_code') === $reason->value)>{{ $reason->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="guidance_note" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Guidance Note</label>
        <textarea id="guidance_note"
            name="guidance_note"
            rows="4"
            placeholder="Required guidance for instructor improvement"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('guidance_note') }}</textarea>
    </div>

    <button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">
        Reject
    </button>
</form>
