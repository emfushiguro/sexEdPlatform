@php
    $generatedCode = (int) session('generated_attendance_seminar_id') === (int) $seminar->id && session()->has('generated_attendance_code')
        ? \Illuminate\Support\Facades\Crypt::decryptString(session('generated_attendance_code')) : null;
@endphp
<section class="rounded-2xl border border-gray-200 bg-white p-5">
    <h2 class="text-lg font-semibold text-gray-900">Attendance code</h2>
    <p class="mt-1 text-sm text-gray-600">Optional for in-person and external events. Submitting a code confirms entry, not full participation.</p>
    @if($generatedCode)
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">
            <p class="text-sm font-semibold text-amber-900">Copy this code now. It is shown only once.</p>
            <p class="mt-2 font-mono text-2xl tracking-widest text-amber-950">{{ $generatedCode }}</p>
        </div>
    @endif
    <p class="mt-3 text-sm text-gray-600">{{ $seminar->attendance_code_enabled ? 'Code enabled. Regenerate to replace a lost code.' : 'Code disabled.' }}</p>
    @if($seminar->attendance_code_enabled)
        <p class="mt-1 text-sm text-gray-600">Entry: {{ ($seminar->attendance_start_at ?? $seminar->starts_at?->copy()->subMinutes(15))?->timezone(config('app.display_timezone'))->format('M d, Y g:i A') }} to {{ ($seminar->attendance_end_at ?? $seminar->ends_at?->copy()->addMinutes(30))?->timezone(config('app.display_timezone'))->format('M d, Y g:i A') }} PHT</p>
    @endif
    <form method="POST" action="{{ $generateAction }}" class="mt-4 grid gap-3 sm:grid-cols-2">
        @csrf
        <label class="text-sm font-medium text-gray-700">Opens at (PHT, optional)
            <input type="datetime-local" name="attendance_start_at" value="{{ old('attendance_start_at', $seminar->attendance_start_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300">
        </label>
        <label class="text-sm font-medium text-gray-700">Closes at (PHT, optional)
            <input type="datetime-local" name="attendance_end_at" value="{{ old('attendance_end_at', $seminar->attendance_end_at?->copy()->timezone(config('app.display_timezone'))->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-lg border-gray-300">
        </label>
        @error('attendance_start_at')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        @error('attendance_end_at')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <button class="w-fit rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">{{ $seminar->attendance_code_enabled ? 'Regenerate code' : 'Generate code' }}</button>
    </form>
    @if($seminar->attendance_code_enabled)
        <form method="POST" action="{{ $disableAction }}" class="mt-3">
            @csrf
            <button class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Disable code</button>
        </form>
    @endif
</section>
