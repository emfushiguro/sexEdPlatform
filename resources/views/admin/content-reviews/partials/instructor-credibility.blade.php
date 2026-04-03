<div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5" x-data="{ violationsOpen: false }">
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Instructor Credibility</h2>

    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-gray-500">Warning Count</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.warning_count', 0) }}</dd>
        </div>
        <div>
            <dt class="text-gray-500">Restriction Status</dt>
            <dd class="font-semibold text-gray-900">{{ data_get($workspace, 'moderation.current_restriction_label', 'None') }}</dd>
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
            <button type="button" class="flex w-full items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-left" @click="violationsOpen = !violationsOpen">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Violation History</p>
                <svg class="h-4 w-4 text-gray-500 transition-transform" :class="violationsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <ul x-show="violationsOpen" class="mt-2 space-y-2" style="display:none;">
                @foreach(data_get($workspace, 'moderation.recent_violations', []) as $violation)
                    <li class="rounded-lg border border-gray-100 px-3 py-2 text-xs text-gray-700">
                        <span class="font-semibold">{{ $violation['reason_label'] ?? 'Unknown' }}</span>
                        <span class="text-gray-500">· Sequence {{ $violation['violation_sequence'] ?? '-' }}</span>
                        <p class="mt-1">{{ $violation['guidance_note'] ?? '' }}</p>
                        @if(!empty($violation['suggested_penalty_label']) && $violation['suggested_penalty_label'] !== 'N/A')
                            <p class="mt-1 text-[11px] text-gray-500">Suggested Action: {{ $violation['suggested_penalty_label'] }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
