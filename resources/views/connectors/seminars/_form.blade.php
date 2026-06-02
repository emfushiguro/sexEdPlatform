@php
    $selectedAges = old('learner_age_categories', $seminar->learner_age_categories ?? []);
    $selectedAges = is_array($selectedAges) ? $selectedAges : [];
@endphp

<div class="grid gap-5 lg:grid-cols-2">
    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Title</span>
        <input name="title" value="{{ old('title', $seminar->title) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('title') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Description</span>
        <textarea name="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('description', $seminar->description) }}</textarea>
        @error('description') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Purpose</span>
        <textarea name="purpose" rows="3" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">{{ old('purpose', $seminar->purpose) }}</textarea>
        @error('purpose') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Type</span>
        <select name="type" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            @foreach(\App\Enums\SeminarType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', $seminar->type) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>
        @error('type') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Category</span>
        <select name="category" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            @foreach(config('seminars.categories') as $key => $label)
                <option value="{{ $key }}" @selected(old('category', $seminar->category) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Starts At</span>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($seminar->starts_at)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('starts_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Ends At</span>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($seminar->ends_at)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('ends_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Capacity</span>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $seminar->capacity) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('capacity') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Target Participants</span>
        <select name="target_participants" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            <option value="learners" @selected(old('target_participants', $seminar->target_participants) === 'learners')>Learners</option>
            <option value="instructors" @selected(old('target_participants', $seminar->target_participants) === 'instructors')>Instructors</option>
            <option value="learners_and_instructors" @selected(old('target_participants', $seminar->target_participants) === 'learners_and_instructors')>Learners and Instructors</option>
        </select>
        @error('target_participants') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Location</span>
        <input name="location" value="{{ old('location', $seminar->location) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('location') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <fieldset class="lg:col-span-2">
        <legend class="text-sm font-semibold text-gray-700">Learner Age Categories</legend>
        <div class="mt-2 flex flex-wrap gap-3">
            @foreach(config('seminars.learner_age_categories') as $key => $label)
                <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    <input type="checkbox" name="learner_age_categories[]" value="{{ $key }}" @checked(in_array($key, $selectedAges, true)) class="rounded border-gray-300 text-purple-700 focus:ring-purple-500">
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        @error('learner_age_categories') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </fieldset>
</div>

<div class="mt-6 flex items-center justify-end gap-3">
    <a href="{{ $seminar->exists ? route('connector.seminars.show', [$connector, $seminar]) : route('connector.seminars.index', $connector) }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
    <button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-800">{{ $submitLabel }}</button>
</div>
