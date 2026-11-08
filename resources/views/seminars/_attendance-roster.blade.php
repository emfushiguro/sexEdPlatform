@php
    $canCorrect = $canCorrectAttendance ?? isset($connector);
    $showSessionTimes = $seminar->isNativeDelivery();
@endphp
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
                <th class="px-4 py-3">Participant</th>
                <th class="px-4 py-3">Registration</th>
                <th class="px-4 py-3">Attendance</th>
                <th class="px-4 py-3">Recorded by</th>
                <th class="px-4 py-3">Recorded at (PHT)</th>
                @if($showSessionTimes)
                <th class="px-4 py-3">Joined</th>
                <th class="px-4 py-3">Left</th>
                <th class="px-4 py-3">Duration</th>
                @endif
                @if($canCorrect)<th class="px-4 py-3 text-right">Decision</th>@endif
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
                    $recordedAt = $attendance?->attendance_method === 'manual' ? $attendance->updated_at : $attendance?->attended_at;
                    $hasDecision = $attendance && (in_array($attendance->attendance_method, ['manual', 'attendance_code', 'legacy'], true) || in_array($attendance->status, ['attended', 'not_present'], true));
                    $decisionError = in_array(old('attendance_registrant_id'), [$registrant->id, (string) $registrant->id], true) && ($errors->has('attended') || $errors->has('reason'));
                    $decisionValue = $decisionError && in_array(old('attended'), ['0', '1'], true) ? old('attended') : ($attendance?->status === 'not_present' ? '0' : '1');
                @endphp
                <tr class="align-middle">
                    <td class="px-4 py-3">
                        @include('seminars._participant-profile', ['user' => $registrant->user])
                        <div class="mt-1 text-xs capitalize text-gray-500">{{ str_replace('_', ' ', $registrant->participant_type) }}</div>
                    </td>
                    <td class="px-4 py-3 capitalize">{{ str_replace('_', ' ', $registrant->status) }}</td>
                    <td class="px-4 py-3">{{ $attendanceStatus }}</td>
                    <td class="px-4 py-3">{{ $methodLabel }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $recordedAt ? $recordedAt->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A').' PHT' : '—' }}</td>
                    @if($showSessionTimes)
                    <td class="px-4 py-3 whitespace-nowrap">{{ $attendance?->joined_at ? $attendance->joined_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A').' PHT' : '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $attendance?->left_at ? $attendance->left_at->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i:s A').' PHT' : '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $attendance ? number_format((int) $attendance->total_seconds / 60, 1).' min' : '—' }}</td>
                    @endif
                    @if($canCorrect)
                    <td class="px-4 py-3 text-right">
                        @if($registrant->status === 'registered' && $registrant->cancelled_at === null)
                            <div x-data="{ decision: @js($decisionValue), hasDecision: @js((bool) $hasDecision) }" x-init="if (@js($decisionError)) $nextTick(() => $refs.decisionDialog.showModal())">
                                <button type="button" @click="$refs.decisionDialog.showModal()" aria-haspopup="dialog" aria-controls="attendance-decision-{{ $registrant->id }}" aria-label="Update attendance for {{ $registrant->user?->name ?? 'participant' }}" title="Update attendance" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-purple-200 text-purple-700 hover:bg-purple-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-purple-600">
                                    <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4 4 0 0 1-1.897 1.13L6 18l.8-2.685a4 4 0 0 1 1.13-1.897l8.932-8.931Z"/></svg>
                                </button>
                                <dialog x-ref="decisionDialog" id="attendance-decision-{{ $registrant->id }}" aria-labelledby="attendance-decision-title-{{ $registrant->id }}" @click.self="$refs.decisionDialog.close()" class="m-auto max-h-[90vh] w-[min(92vw,30rem)] overflow-y-auto rounded-2xl border-0 p-6 text-left shadow-2xl backdrop:bg-gray-900/60">
                                    <div class="flex items-start justify-between gap-4">
                                        <div><h3 id="attendance-decision-title-{{ $registrant->id }}" class="text-lg font-bold text-gray-900">Update attendance</h3><p class="mt-1 text-sm text-gray-600">{{ $registrant->user?->name ?? 'Participant' }}</p></div>
                                        <button type="button" autofocus @click="$refs.decisionDialog.close()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">Close</button>
                                    </div>
                                    <form method="POST" action="{{ route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]) }}" class="mt-5 space-y-4">
                                        @csrf
                                        <input type="hidden" name="attendance_registrant_id" value="{{ $registrant->id }}">
                                        <label class="block text-sm font-medium text-gray-700">Decision
                                            <select name="attended" x-model="decision" class="mt-1 w-full rounded-lg border-gray-300 text-sm"><option value="1">Present</option><option value="0">Not present</option></select>
                                        </label>
                                        <label x-show="decision === '0' || hasDecision" x-cloak class="block text-sm font-medium text-gray-700">Reason for removal or correction
                                            <textarea :required="decision === '0' || hasDecision" name="reason" rows="3" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300 text-sm" placeholder="Required when removing or correcting a decision">{{ $decisionError && is_string(old('reason')) ? old('reason') : '' }}</textarea>
                                        </label>
                                        @if($decisionError)
                                            @foreach(['attended', 'reason'] as $field) @error($field)<p class="text-sm text-red-700" role="alert">{{ $message }}</p>@enderror @endforeach
                                        @endif
                                        <div class="flex justify-end gap-2"><button type="button" @click="$refs.decisionDialog.close()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button type="submit" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">Save decision</button></div>
                                    </form>
                                </dialog>
                            </div>
                        @else
                            <span class="text-xs text-gray-400" title="Manual updates require a confirmed active registration.">Unavailable</span>
                        @endif
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + ($showSessionTimes ? 3 : 0) + ($canCorrect ? 1 : 0) }}" class="px-4 py-10 text-center text-gray-500">No registrations yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
