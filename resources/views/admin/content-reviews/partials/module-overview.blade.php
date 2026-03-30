<div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Module Overview</h2>

    <dl class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-gray-500">Module Title</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'module.title', $reviewRequest->module_title) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Review Status</dt>
            <dd class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $reviewRequest->status)) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Submission Date</dt>
            <dd class="font-semibold text-gray-900">{{ optional($reviewRequest->submitted_at)->toDayDateTimeString() }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Instructor</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'instructor.name', 'Unknown Instructor') }}</dd>
        </div>
    </dl>

    @if(data_get($workspace, 'module.description'))
        <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Description</p>
            <p class="mt-1 text-sm text-gray-700">{{ data_get($workspace, 'module.description') }}</p>
        </div>
    @endif
</div>
