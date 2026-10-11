@props([
    'name' => 'audiences',
    'selected' => ['all'],
    'id' => 'audience-picker',
])

@php
    $audienceOptions = ['all', 'guest', 'learner', 'parent', 'instructor', 'connector', 'admin'];
    $selectedAudiences = array_values(array_unique(array_filter((array) $selected)));
    if ($selectedAudiences === [] || in_array('all', $selectedAudiences, true)) {
        $selectedAudiences = ['all'];
    }
@endphp

<fieldset id="{{ $id }}" x-data="{ selected: @js($selectedAudiences) }" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
    <legend class="px-1 text-sm font-semibold text-gray-700 dark:text-gray-200">Audiences</legend>
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Choose who can see this content. Select All audiences or specific roles.</p>
    <div class="mt-3 grid gap-2 sm:grid-cols-2">
        @foreach($audienceOptions as $audience)
            @php($optionId = $id.'-'.$audience)
            <label for="{{ $optionId }}" class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 transition hover:border-purple-300 hover:bg-purple-50/60 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-purple-950/30">
                <input
                    id="{{ $optionId }}"
                    type="checkbox"
                    name="{{ $name }}[]"
                    value="{{ $audience }}"
                    x-model="selected"
                    @checked(in_array($audience, $selectedAudiences, true))
                    @change="if ($event.target.value === 'all' && $event.target.checked) selected = ['all']; if ($event.target.value !== 'all' && $event.target.checked) selected = selected.filter(value => value !== 'all')"
                    class="h-4 w-4 rounded border-gray-300 text-brand-700 focus:ring-purple-400"
                >
                <span>{{ str($audience)->headline() }}</span>
            </label>
        @endforeach
    </div>
    @error($name)<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
</fieldset>
