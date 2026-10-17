@php
    $generatedCode = (int) session('generated_attendance_seminar_id') === (int) $seminar->id
        && (int) session('generated_attendance_actor_id') === (int) auth()->id()
        && $seminar->attendance_code_enabled && session()->has('generated_attendance_code')
        ? \Illuminate\Support\Facades\Crypt::decryptString(session('generated_attendance_code')) : null;
    if ($generatedCode && ! \Illuminate\Support\Facades\Hash::check($generatedCode, $seminar->attendance_code_hash)) {
        $generatedCode = null;
    }
    $shareableCode = $generatedCode ?? $postedHostCode ?? null;
    $opensAt = $seminar->attendance_start_at ?? $seminar->starts_at?->copy()->subMinutes(15);
    $closesAt = $seminar->attendance_end_at ?? $seminar->ends_at?->copy()->addMinutes(30);
    $windowStatus = ! $seminar->attendance_code_enabled ? 'Closed'
        : ((! $opensAt || ! $closesAt || now()->gt($closesAt)) ? 'Expired' : (now()->lt($opensAt) ? 'Scheduled' : 'Open'));
@endphp
<section class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ copied: false, copyError: false, async copyCode() { try { await navigator.clipboard.writeText(@js($shareableCode ?? '')); this.copied = true; this.copyError = false; } catch { this.copyError = true; } } }" x-init="if (@js($errors->has('attendance_start_at') || $errors->has('attendance_end_at'))) $nextTick(() => $refs.attendanceSettings.showModal())">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold text-gray-900">Attendance code</h2>
        <button type="button" @click="$refs.attendanceSettings.showModal()" aria-haspopup="dialog" aria-controls="attendance-code-settings" class="rounded-lg border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">{{ $seminar->attendance_code_enabled ? 'Attendance settings' : 'Set up attendance code' }}</button>
    </div>
    <p class="mt-1 text-sm text-gray-600">Optional for in-person and external events. Submitting a code confirms entry, not full participation.</p>
    @if($generatedCode)
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">Copy this code now. It is shown only once.</p>
            <p class="mt-2 font-mono text-2xl tracking-widest text-amber-950">{{ $generatedCode }}</p>
        </div>
    @endif
    @if($shareableCode)
        @if(! $generatedCode)
            <p class="mt-4 font-mono text-2xl tracking-widest text-purple-900">{{ $shareableCode }}</p>
        @endif
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button type="button" @click="copyCode()" class="rounded-lg border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-700">Copy code</button>
            <button type="button" @click="$refs.codePresentation.showModal()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">Present code</button>
            <span x-show="copied" x-cloak role="status" class="text-sm text-emerald-700">Copied</span>
            <span x-show="copyError" x-cloak role="status" class="text-sm text-amber-800">Select and copy the displayed code manually.</span>
        </div>
        <dialog x-ref="codePresentation" aria-labelledby="attendance-presentation-title" class="m-auto w-[min(90vw,48rem)] rounded-3xl border-0 p-8 text-center shadow-2xl backdrop:bg-gray-900/70" @click="if ($event.target === $refs.codePresentation) $refs.codePresentation.close()">
            <h3 id="attendance-presentation-title" class="text-xl font-bold text-gray-900">{{ $seminar->title }}</h3>
            <p class="mt-3 text-gray-600">Enter this code on your event page to submit attendance.</p>
            <p class="my-10 break-all font-mono text-5xl font-bold tracking-widest text-purple-900 sm:text-7xl">{{ $shareableCode }}</p>
            <p class="text-sm text-gray-500">Code submission does not prove continuous participation.</p>
            <button type="button" autofocus @click="$refs.codePresentation.close()" class="mt-6 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold">Close presentation</button>
        </dialog>
    @endif
    <p class="mt-3 text-sm font-semibold text-gray-700">Attendance window: {{ $windowStatus }}</p>
    <p class="mt-1 text-sm text-gray-600">{{ $seminar->attendance_code_enabled ? 'Code enabled. Regenerate to replace a lost code; the previous code stops working immediately.' : 'Generate a code to activate attendance for the configured window.' }}</p>
    @if($seminar->attendance_code_enabled)
        <p class="mt-1 text-sm text-gray-600">Entry: {{ $opensAt?->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i A') }} to {{ $closesAt?->copy()->timezone(config('app.display_timezone'))->format('M d, Y g:i A') }} PHT</p>
    @endif
    @if($seminar->attendance_code_enabled && in_array($seminar->status, ['published', 'completed'], true))
        <div class="mt-5 border-t border-gray-100 pt-4">
            <h3 class="font-semibold text-gray-900">Share with registrants</h3>
            <p class="mt-1 text-sm text-gray-600">Post the code on the authenticated event page for confirmed eligible registrants during the attendance window. This also allows registrants who have not joined the session to submit it.</p>
            @if($seminar->attendance_code_posted_at)
                <p class="mt-2 text-sm font-semibold text-emerald-700">Posted for registrants. {{ $windowStatus === 'Scheduled' ? 'It will appear when attendance opens.' : ($windowStatus === 'Expired' ? 'The attendance window has ended; the code is hidden from registrants.' : 'It is visible while attendance is open.') }}</p>
                <form method="POST" action="{{ route('connector.seminars.attendance.code.unpost', [$connector, $seminar]) }}" class="mt-3">
                    @csrf
                    <button class="rounded-lg border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-700">Unpost code</button>
                </form>
            @elseif($windowStatus !== 'Expired')
                <form method="POST" action="{{ route('connector.seminars.attendance.code.post', [$connector, $seminar]) }}" class="mt-3 flex flex-wrap items-end gap-3">
                    @csrf
                    @if($shareableCode)
                        <input type="hidden" name="attendance_share_code" value="{{ $shareableCode }}">
                    @else
                        <label class="text-sm font-medium text-gray-700">Current eight-digit code
                            <input name="attendance_share_code" inputmode="numeric" pattern="[0-9]{8}" minlength="8" maxlength="8" autocomplete="off" required class="mt-1 block rounded-lg border-gray-300">
                        </label>
                    @endif
                    <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Post for registrants</button>
                </form>
                @if(! $shareableCode)<p class="mt-2 text-xs text-gray-500">Paste the code you saved after generation, or regenerate a lost code.</p>@endif
            @endif
            @error('attendance_share_code')<p class="mt-2 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
        </div>
    @endif
    <dialog x-ref="attendanceSettings" id="attendance-code-settings" aria-labelledby="attendance-settings-title" @click.self="$refs.attendanceSettings.close()" class="m-auto max-h-[90vh] w-[min(92vw,36rem)] overflow-y-auto rounded-2xl border-0 p-6 shadow-2xl backdrop:bg-gray-900/60">
        <div class="flex items-start justify-between gap-4">
            <h3 id="attendance-settings-title" class="text-lg font-bold text-gray-900">Attendance code settings</h3>
            <button type="button" autofocus @click="$refs.attendanceSettings.close()" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700">Close</button>
        </div>
        <p class="mt-3 text-sm text-gray-600">Choose when registrants can submit their code. Leave a field blank to open 15 minutes before the event or close 30 minutes after it ends.</p>
        @if($seminar->attendance_code_enabled)<p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Regenerating replaces the current code and removes its post. Copy and share the new code afterward.</p>@endif
        <form method="POST" action="{{ $generateAction }}" class="mt-5 space-y-4">
            @csrf
            <label class="block text-sm font-medium text-gray-700">Opens at (PHT, optional)
                <input type="datetime-local" name="attendance_start_at" value="{{ old('attendance_start_at', $seminar->attendance_start_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300">
            </label>
            <label class="block text-sm font-medium text-gray-700">Closes at (PHT, optional)
                <input type="datetime-local" name="attendance_end_at" value="{{ old('attendance_end_at', $seminar->attendance_end_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300">
            </label>
            @error('attendance_start_at')<p class="text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
            @error('attendance_end_at')<p class="text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
            <div class="flex justify-end gap-2"><button type="button" @click="$refs.attendanceSettings.close()" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</button><button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">{{ $seminar->attendance_code_enabled ? 'Regenerate code' : 'Generate code' }}</button></div>
        </form>
    </dialog>
    @if($seminar->attendance_code_enabled)
        <form method="POST" action="{{ $disableAction }}" class="mt-3">
            @csrf
            <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Close attendance</button>
        </form>
    @endif
</section>
