<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3">Participant</th>
                <th class="px-4 py-3">Registration</th>
                <th class="px-4 py-3">Attendance</th>
                <th class="px-4 py-3">Recorded by</th>
                <th class="px-4 py-3">Attended at</th>
                <th class="px-4 py-3">Joined</th>
                <th class="px-4 py-3">Left</th>
                <th class="px-4 py-3">Duration</th>
                <th class="px-4 py-3">Update</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($registrants as $registrant)
                @php
                    $attendance = $attendances->get($registrant->user_id);
                    $attendanceStatus = match ($attendance?->attendance_method) {
                        'attendance_code' => 'Attendance submitted',
                        'manual' => match ($attendance->status) {
                            'attended' => 'Attended',
                            'not_present' => 'Not present',
                            default => ucfirst(str_replace('_', ' ', $attendance->status)),
                        },
                        default => $attendance ? ucfirst(str_replace('_', ' ', $attendance->status)) : 'Not submitted',
                    };
                    $methodLabel = match ($attendance?->attendance_method) {
                        'attendance_code' => 'Attendance code',
                        'manual' => 'Event organizer',
                        'native' => 'Livestream',
                        'legacy' => 'Legacy record',
                        default => '—',
                    };
                    $manualAction = isset($connector)
                        ? route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant])
                        : route('admin.seminars.attendance.manual', [$seminar, $registrant]);
                @endphp
                <tr class="align-top">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-900">{{ $registrant->user?->name ?? 'Unknown participant' }}</div>
                        <div class="text-xs text-gray-500">{{ $registrant->user?->email }}</div>
                        <div class="mt-1 text-xs capitalize text-gray-500">{{ str_replace('_', ' ', $registrant->participant_type) }}</div>
                    </td>
                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $registrant->status) }}</td>
                    <td class="px-4 py-3">{{ $attendanceStatus }}</td>
                    <td class="px-4 py-3">{{ $methodLabel }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($attendance?->attended_at)->format('M d, Y g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($attendance?->joined_at)->format('M d, Y g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ optional($attendance?->left_at)->format('M d, Y g:i A') ?? '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $attendance ? number_format((int) $attendance->total_seconds / 60, 1).' min' : '—' }}</td>
                    <td class="min-w-64 px-4 py-3">
                        @if($registrant->status === 'registered' && $registrant->cancelled_at === null)
                            <form method="POST" action="{{ $manualAction }}" class="space-y-2">
                                @csrf
                                <label class="block text-xs font-medium text-gray-600">
                                    Decision
                                    <select name="attended" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                        <option value="1" {{ $attendance?->status === 'attended' ? 'selected' : '' }}>Present</option>
                                        <option value="0" {{ $attendance?->status === 'not_present' ? 'selected' : '' }}>Not present</option>
                                    </select>
                                </label>
                                <label class="block text-xs font-medium text-gray-600">
                                    Reason for removal or correction
                                    <textarea name="reason" rows="2" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Required when removing or correcting a decision">{{ old('reason') }}</textarea>
                                </label>
                                <button type="submit" class="rounded-lg bg-purple-700 px-3 py-2 text-xs font-semibold text-white hover:bg-purple-800">Save decision</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400">Manual updates require a confirmed active registration.</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-500">No registrations yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
