<form method="POST" action="{{ route('admin.content-reviews.reject', $reviewRequest) }}" class="flex items-center gap-3">
    @csrf
    <input
        type="text"
        name="feedback"
        value="{{ old('feedback', $reviewRequest->feedback) }}"
        placeholder="Required rejection feedback"
        class="w-80 rounded-lg border border-gray-300 px-3 py-2 text-sm"
    >
    <button type="submit" class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-500">
        Reject
    </button>
</form>
