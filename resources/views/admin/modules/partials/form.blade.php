<div class="max-w-3xl space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
    </div>

    <form method="POST" action="{{ $action }}" class="rounded-xl border border-gray-200 bg-white p-6 space-y-4">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input type="text" name="title" value="{{ old('title', $module?->title) }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" rows="4" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>{{ old('description', $module?->description) }}</textarea>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">Age Bracket</label>
                @php
                    $ageBracket = old('age_bracket');
                    if (!$ageBracket && $module) {
                        $ageBracket = $module->min_age >= 18 ? 'adults' : ($module->min_age >= 13 ? 'teens' : 'kids');
                    }
                @endphp
                <select name="age_bracket" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="kids" @selected($ageBracket === 'kids')>Kids</option>
                    <option value="teens" @selected($ageBracket === 'teens')>Teens</option>
                    <option value="adults" @selected($ageBracket === 'adults')>Adults</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Enrollment Mode</label>
                <select name="enrollment_mode" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    <option value="auto" @selected(old('enrollment_mode', $module?->enrollment_mode) === 'auto')>Auto</option>
                    <option value="manual" @selected(old('enrollment_mode', $module?->enrollment_mode) === 'manual')>Manual</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.modules.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700">Cancel</a>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-500">Save Module</button>
        </div>
    </form>
</div>
