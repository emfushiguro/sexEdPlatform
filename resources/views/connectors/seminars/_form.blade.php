@php
    $selectedAges = old('learner_age_categories', $seminar->learner_age_categories ?? []);
    $selectedAges = is_array($selectedAges) ? $selectedAges : [];
@endphp

<div class="grid gap-5 lg:grid-cols-2" x-data="{ category: @js(old('category', $seminar->category ?? 'education')), approvalMode: @js(old('registration_approval_mode', $seminar->registration_approval_mode ?? 'auto_approve')) }">
    <label class="block lg:col-span-2">
        <span class="text-sm font-semibold text-gray-700">Title</span>
        <input name="title" value="{{ old('title', $seminar->title) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('title') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
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
        <select name="category" x-model="category" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            @foreach(config('seminars.categories') as $key => $label)
                <option value="{{ $key }}" @selected(old('category', $seminar->category) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block" x-show="category === 'other'" x-cloak>
        <span class="text-sm font-semibold text-gray-700">Custom Category</span>
        <input name="custom_category" value="{{ old('custom_category', $seminar->custom_category) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('custom_category') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Starts At (Philippine Time)</span>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $seminar->localStartsAt()?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('starts_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Ends At (Philippine Time)</span>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $seminar->localEndsAt()?->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('ends_at') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <label class="block">
        <span class="text-sm font-semibold text-gray-700">Capacity</span>
        <input type="number" min="1" name="capacity" value="{{ old('capacity', $seminar->capacity) }}" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
        @error('capacity') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </label>

    <fieldset class="lg:col-span-2">
        <legend class="text-sm font-semibold text-gray-700">Registration Approval</legend>
        <div class="mt-2 grid gap-3 md:grid-cols-2">
            <label class="cursor-pointer rounded-2xl border p-4 transition" :class="approvalMode === 'auto_approve' ? 'border-purple-300 bg-purple-50 ring-2 ring-purple-100' : 'border-gray-200 bg-white hover:bg-gray-50'">
                <input type="radio" name="registration_approval_mode" value="auto_approve" x-model="approvalMode" class="sr-only">
                <span class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/></svg>
                    </span>
                    <span>
                        <span class="block font-semibold text-gray-900">Auto Approve</span>
                        <span class="mt-1 block text-sm text-gray-500">Eligible registrants are accepted immediately and counted as participants.</span>
                    </span>
                </span>
            </label>
            <label class="cursor-pointer rounded-2xl border p-4 transition" :class="approvalMode === 'manual' ? 'border-purple-300 bg-purple-50 ring-2 ring-purple-100' : 'border-gray-200 bg-white hover:bg-gray-50'">
                <input type="radio" name="registration_approval_mode" value="manual" x-model="approvalMode" class="sr-only">
                <span class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <span>
                        <span class="block font-semibold text-gray-900">Manual Approval</span>
                        <span class="mt-1 block text-sm text-gray-500">Registrants wait in pending status until a host approves or rejects them.</span>
                    </span>
                </span>
            </label>
        </div>
        @error('registration_approval_mode') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
    </fieldset>

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
