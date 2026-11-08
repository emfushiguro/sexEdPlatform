<section class="rounded-lg border border-gray-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-gray-900">Attendance audit history</h2>
    <div class="mt-4 space-y-4 text-sm">
        @forelse($audits as $audit)
            <div class="border-b border-gray-100 pb-3">
                <p class="font-semibold text-gray-900">{{ str($audit->activity_type)->replace('_', ' ')->ucfirst() }}</p>
                <p class="mt-1 text-gray-600">{{ $audit->user?->name ?? 'System' }} · {{ $audit->created_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A') }} PHT</p>
                @if(isset($audit->metadata['participant_id']))<p class="text-gray-600">Participant ID: {{ $audit->metadata['participant_id'] }}</p>@endif
                @if(filled($audit->metadata['reason'] ?? null))<p class="mt-1 text-gray-700">Reason: {{ $audit->metadata['reason'] }}</p>@endif
                @if(isset($audit->metadata['after']))
                    <p class="mt-1 text-gray-700">{{ $audit->metadata['before']['status'] ?? 'No record' }} → {{ $audit->metadata['after']['status'] }} ({{ $audit->metadata['after']['attendance_method'] }})</p>
                @endif
            </div>
        @empty
            <p class="text-gray-500">No attendance audit entries recorded.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $audits->links() }}</div>
</section>
