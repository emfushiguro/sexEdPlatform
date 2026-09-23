@php
    $editing = $path !== null;
    $categories = old('categories', $editing ? $path->learnerCategories->pluck('category')->all() : ['teens']);
    $categories = is_array($categories) ? $categories : [];
    $categoryError = $errors->first('categories') ?: $errors->first('categories.*');
    $moduleError = $errors->first('module_ids') ?: $errors->first('module_ids.*');
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div><h1 class="text-2xl font-semibold text-gray-900">{{ $editing ? 'Edit learning path' : 'Create learning path' }}</h1><p class="mt-1 text-sm text-gray-600">Choose categories first, then arrange modules in learning order.</p></div>
        <a href="{{ route('admin.learning-paths.index') }}" class="text-sm font-medium text-purple-700 hover:underline">Back to paths</a>
    </div>

    @if($errors->any())
        <div id="learning-path-errors" role="alert" tabindex="-1" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 focus:outline-2 focus:outline-rose-600">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('learning-path-errors')?.focus());</script>
    @endif

    <form method="POST" action="{{ $editing ? route('admin.learning-paths.update', $path) : route('admin.learning-paths.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7"
          x-data="{
            add() {
                const option = this.$refs.available.selectedOptions[0];
                if (!option || !option.value || this.$refs.selected.querySelector(`[data-module-id='${option.value}']`)) return;
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm';
                row.dataset.moduleId = option.value;
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = 'module_ids[]'; input.value = option.value;
                const label = document.createElement('span');
                label.className = 'min-w-0 flex-1'; label.textContent = option.textContent;
                row.append(input, label);
                for (const [text, action] of [['Up', () => row.previousElementSibling?.before(row)], ['Down', () => row.nextElementSibling?.after(row)], ['Remove', () => row.remove()]]) {
                    const button = document.createElement('button'); button.type = 'button'; button.textContent = text;
                    button.className = 'font-medium text-purple-700 hover:underline'; button.addEventListener('click', action); row.append(button);
                }
                this.$refs.selected.append(row); this.$refs.available.value = '';
            },
            move(row, direction) { if (direction < 0) row.previousElementSibling?.before(row); else row.nextElementSibling?.after(row); }
          }">
        @csrf
        @if($editing) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="title" class="mb-1.5 block text-sm font-semibold text-gray-800">Title</label>
                <input id="title" name="title" type="text" maxlength="255" required value="{{ old('title', $path?->title) }}" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" @if($errors->has('title')) aria-describedby="title-error" @endif class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:border-purple-600 focus:ring-purple-600">
                @error('title')<p id="title-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">
                <label for="description" class="mb-1.5 block text-sm font-semibold text-gray-800">Description</label>
                <textarea id="description" name="description" rows="4" required aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" @if($errors->has('description')) aria-describedby="description-error" @endif class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:border-purple-600 focus:ring-purple-600">{{ old('description', $path?->description) }}</textarea>
                @error('description')<p id="description-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-sm font-semibold text-gray-800">Status</label>
                <select id="status" name="status" aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}" @if($errors->has('status')) aria-describedby="status-error" @endif class="w-full rounded-xl border border-gray-300 px-3 py-2.5 focus:border-purple-600 focus:ring-purple-600">
                    @foreach(\App\Models\LearningPath::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $path?->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>@endforeach
                </select>
                @error('status')<p id="status-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="thumbnail" class="mb-1.5 block text-sm font-semibold text-gray-800">Thumbnail (optional)</label>
                <input id="thumbnail" name="thumbnail" type="file" accept="image/*" aria-invalid="{{ $errors->has('thumbnail') ? 'true' : 'false' }}" @if($errors->has('thumbnail')) aria-describedby="thumbnail-error" @endif class="block w-full rounded-xl border border-gray-300 px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-1.5 file:font-medium file:text-purple-700">
                @if($editing && $path->thumbnail)<p class="mt-1 text-xs text-gray-500">Current image: <a class="text-purple-700 underline" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path->thumbnail) }}">View thumbnail</a></p>@endif
                @error('thumbnail')<p id="thumbnail-error" class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
            </div>
        </div>

        <fieldset aria-invalid="{{ $categoryError ? 'true' : 'false' }}" @if($categoryError) aria-describedby="categories-error" @endif>
            <legend class="text-sm font-semibold text-gray-800">Learner categories</legend>
            <div class="mt-2 flex flex-wrap gap-4">@foreach(['kids' => 'Kids', 'teens' => 'Teens', 'adults' => 'Adults'] as $key => $label)
                <label class="inline-flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="categories[]" value="{{ $key }}" @checked(in_array($key, $categories, true)) aria-invalid="{{ $categoryError ? 'true' : 'false' }}" @if($categoryError) aria-describedby="categories-error" @endif class="rounded border-gray-300 text-purple-700 focus:ring-purple-600">{{ $label }}</label>
            @endforeach</div>
            @if($categoryError)<p id="categories-error" class="mt-1 text-sm text-rose-700">{{ $categoryError }}</p>@endif
        </fieldset>

        <div>
            <label for="available-module" class="mb-1.5 block text-sm font-semibold text-gray-800">Add a learner-visible module</label>
            <div class="flex gap-2"><select id="available-module" x-ref="available" aria-invalid="{{ $moduleError ? 'true' : 'false' }}" @if($moduleError) aria-describedby="modules-error" @endif class="min-w-0 flex-1 rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-600 focus:ring-purple-600"><option value="">Choose a module</option>
                @foreach($candidates as $candidate)<option value="{{ $candidate->id }}">{{ $candidate->title }} — {{ $candidate->creator?->name ?? ($candidate->content_owner_type === 'admin' ? 'Platform' : 'Former instructor') }} ({{ implode(', ', $candidate->learnerCategoryLabels()) }})</option>@endforeach
            </select><button type="button" @click="add()" class="rounded-xl border border-purple-200 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">Add</button></div>
            <div class="mt-4" aria-invalid="{{ $moduleError ? 'true' : 'false' }}" @if($moduleError) aria-describedby="modules-error" @endif>
                <p class="mb-2 text-sm font-semibold text-gray-800">Selected modules, in path order</p>
                <div x-ref="selected" class="space-y-2">
                    @foreach($selectedModules as $module)
                        <div data-module-id="{{ $module->id }}" class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                            <input type="hidden" name="module_ids[]" value="{{ $module->id }}">
                            <span class="min-w-0 flex-1">{{ $module->title }} — {{ $module->creator?->name ?? ($module->content_owner_type === 'admin' ? 'Platform' : 'Former instructor') }}</span>
                            <button type="button" @click="move($el.parentElement, -1)" class="font-medium text-purple-700 hover:underline">Up</button>
                            <button type="button" @click="move($el.parentElement, 1)" class="font-medium text-purple-700 hover:underline">Down</button>
                            <button type="button" @click="$el.parentElement.remove()" class="font-medium text-purple-700 hover:underline">Remove</button>
                        </div>
                    @endforeach
                </div>
                @if($moduleError)<p id="modules-error" class="mt-1 text-sm text-rose-700">{{ $moduleError }}</p>@endif
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('admin.learning-paths.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</a>
            <button type="submit" class="rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">{{ $editing ? 'Save changes' : 'Create path' }}</button>
        </div>
    </form>
</div>
