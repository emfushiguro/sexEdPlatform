<form method="POST" action="{{ route('admin.content-reviews.approve', $reviewRequest) }}">
    @csrf
    <button type="submit" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
        Approve
    </button>
</form>
