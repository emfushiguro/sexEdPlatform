<div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Instructor Credibility</h2>

    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-gray-500">Warning Count</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.warning_count', 0) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Restriction Status</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.current_restriction_status', 'none') }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Last Violation Date</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.last_violation_at') ? \Illuminate\Support\Carbon::parse(data_get($workspace, 'moderation.last_violation_at'))->toDayDateTimeString() : 'N/A' }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Restriction Ends</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.restriction_ends_at') ? \Illuminate\Support\Carbon::parse(data_get($workspace, 'moderation.restriction_ends_at'))->toDayDateTimeString() : 'N/A' }}</dd>
        </div>
    </dl>

    @if(!empty(data_get($workspace, 'moderation.recent_violations', [])))
        <div class="mt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recent Violations</p>
            <ul class="mt-2 space-y-2">
                @foreach(data_get($workspace, 'moderation.recent_violations', []) as $violation)
                    <li class="rounded-lg border border-gray-100 px-3 py-2 text-xs text-gray-700">
                        <span class="font-semibold">{{ strtoupper($violation['reason_code'] ?? 'unknown') }}</span>
                        <span class="text-gray-500">· Sequence {{ $violation['violation_sequence'] ?? '-' }}</span>
                        <p class="mt-1">{{ $violation['guidance_note'] ?? '' }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
