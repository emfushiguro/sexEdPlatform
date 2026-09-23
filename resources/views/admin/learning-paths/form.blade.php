@php
    $editing = $path !== null;
    $categories = old('categories', $editing ? $path->learnerCategories->pluck('category')->all() : ['teens']);
    $categories = is_array($categories) ? $categories : [];
    $categoryError = $errors->first('categories') ?: $errors->first('categories.*');
    $moduleError = $errors->first('module_ids') ?: $errors->first('module_ids.*');
    $candidateData = $candidates->merge($selectedModules)->unique('id')->values()->map(fn ($module) => [
        'id' => (int) $module->id,
        'title' => $module->title,
        'creator' => $module->creator?->name ?? ($module->content_owner_type === 'admin' ? 'Platform' : 'Former instructor'),
        'thumbnail' => $module->thumbnail,
        'categories' => $module->learnerCategoryKeys(),
        'learnerVisible' => $module->isLearnerVisible() && !$module->trashed(),
    ])->values();
    $selectedIds = $selectedModules->pluck('id')->map(fn ($id) => (int) $id)->values();
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">{{ $editing ? 'Edit learning path' : 'Create learning path' }}</h1>
            <p class="mt-1 text-sm text-gray-600">Choose categories first, then arrange modules in learning order.</p>
        </div>
        <a href="{{ route('admin.learning-paths.index') }}" class="text-sm font-medium text-purple-700 hover:underline">Back to paths</a>
    </div>

    @if($errors->any())
        <div id="learning-path-errors" role="alert" tabindex="-1" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800 focus:outline-2 focus:outline-rose-600">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('learning-path-errors')?.focus());</script>
    @endif

    <form
        method="POST"
        action="{{ $editing ? route('admin.learning-paths.update', $path) : route('admin.learning-paths.store') }}"
        enctype="multipart/form-data"
        class="learning-path-builder space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:p-7"
        x-data="learningPathBuilder({ modules: @js($candidateData), selectedIds: @js($selectedIds), categories: @js($categories) })"
        x-init="initializeRows()"
        @pointermove.window="movePointerDrag($event)"
        @pointerup.window="dropPointerDrag($event)"
        @pointercancel.window="cancelDrag()"
        @submit="syncRows()"
    >
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
            <div class="mt-2 flex flex-wrap gap-4">
                @foreach(['kids' => 'Kids', 'teens' => 'Teens', 'adults' => 'Adults'] as $key => $label)
                    <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="categories[]" value="{{ $key }}" @checked(in_array($key, $categories, true)) aria-invalid="{{ $categoryError ? 'true' : 'false' }}" @if($categoryError) aria-describedby="categories-error" @endif @change="syncCategories($root)" class="h-4 w-4 rounded border-gray-300 text-purple-700 focus:ring-purple-600">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @if($categoryError)<p id="categories-error" class="mt-1 text-sm text-rose-700">{{ $categoryError }}</p>@endif
        </fieldset>

        <section aria-labelledby="learning-path-order-heading">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 id="learning-path-order-heading" class="text-sm font-semibold text-gray-800">Modules in path order</h2>
                    <p id="learning-path-order-instructions" class="mt-1 text-xs text-gray-600">Use the handle to drag, or use Move up and Move down. Press Space or Enter on a handle to pick it up, then use the arrow keys, Home, or End.</p>
                </div>
                <div class="w-full sm:max-w-xs">
                    <label for="module-search" class="sr-only">Search eligible modules</label>
                    <input id="module-search" type="search" x-model="query" placeholder="Search eligible modules" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-purple-600 focus:ring-purple-600">
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <label for="available-module" class="sr-only">Add a learner-visible module</label>
                <select id="available-module" x-ref="available" aria-invalid="{{ $moduleError ? 'true' : 'false' }}" @if($moduleError) aria-describedby="modules-error" @endif class="min-w-0 flex-1 rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-purple-600 focus:ring-purple-600">
                    <option value="">Choose a module</option>
                    <template x-for="module in eligibleModules" :key="module.id">
                        <option :value="module.id" x-text="moduleOptionLabel(module)"></option>
                    </template>
                </select>
                <button type="button" @click="add($refs.available.value); $refs.available.value = ''" class="min-h-11 rounded-xl border border-purple-200 px-4 py-2 text-sm font-semibold text-purple-700 hover:bg-purple-50">Add</button>
            </div>

            <div class="mt-4" aria-invalid="{{ $moduleError ? 'true' : 'false' }}" @if($moduleError) aria-describedby="modules-error" @endif>
                <ol x-ref="selected" aria-label="Selected modules" aria-describedby="learning-path-order-instructions" class="learning-path-order-list space-y-2">
                    @foreach($selectedModules as $index => $module)
                        <li data-learning-path-row data-module-id="{{ $module->id }}" data-learning-path-index="{{ $index }}" aria-posinset="{{ $index + 1 }}" aria-setsize="{{ $selectedModules->count() }}" class="learning-path-order-row relative flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm" :class="draggedId === {{ $module->id }} ? 'learning-path-order-row--dragged' : ''">
                            <div data-learning-path-insertion x-show="isDragging() && dragOverIndex === indexFor({{ $module->id }})" class="learning-path-order-insertion-line absolute -top-1 left-3 right-3 h-1 rounded-full bg-purple-600" aria-hidden="true"></div>
                            <button type="button" data-learning-path-handle @pointerdown="beginPointerDrag(indexFor({{ $module->id }}), $event)" @keydown="handleDragKeyById({{ $module->id }}, $event)" :aria-pressed="draggedId === {{ $module->id }}" aria-label="Reorder {{ $module->title }}" class="learning-path-drag-handle inline-flex h-11 w-11 shrink-0 cursor-grab items-center justify-center rounded-lg border border-gray-200 text-lg text-gray-600 hover:border-purple-300 hover:text-purple-700">↕</button>
                            <input type="hidden" name="module_ids[]" value="{{ $module->id }}">
                            <span class="min-w-0 flex-1">{{ $module->title }} — {{ $module->creator?->name ?? ($module->content_owner_type === 'admin' ? 'Platform' : 'Former instructor') }} @if($module->trashed())<span class="ml-2 text-xs font-semibold text-amber-800">Deleted module — no longer learner-visible — remove to save</span>@elseif(!$module->isLearnerVisible())<span class="ml-2 text-xs font-semibold text-amber-800">No longer learner-visible — remove to save</span>@endif</span>
                            <button type="button" data-learning-path-up @click="moveUpById({{ $module->id }})" :disabled="indexFor({{ $module->id }}) === 0" aria-label="Move up {{ $module->title }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-purple-200 text-lg font-semibold text-purple-700 hover:bg-purple-50 disabled:cursor-not-allowed disabled:opacity-40">↑</button>
                            <button type="button" data-learning-path-down @click="moveDownById({{ $module->id }})" :disabled="indexFor({{ $module->id }}) === moduleIds.length - 1" aria-label="Move down {{ $module->title }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-purple-200 text-lg font-semibold text-purple-700 hover:bg-purple-50 disabled:cursor-not-allowed disabled:opacity-40">↓</button>
                            <button type="button" data-learning-path-remove @click="remove({{ $module->id }})" aria-label="Remove {{ $module->title }}" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-rose-200 text-lg font-semibold text-rose-700 hover:bg-rose-50">×</button>
                        </li>
                    @endforeach
                </ol>
                @if($moduleError)<p id="modules-error" class="mt-1 text-sm text-rose-700">{{ $moduleError }}</p>@endif
            </div>

            <template x-if="mismatchedModuleIds.length > 0">
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                    <p class="font-semibold">Review selected modules</p>
                    <p class="mt-1">These modules no longer support every selected learner category:</p>
                    <ul class="mt-2 list-inside list-disc"><template x-for="id in mismatchedModuleIds" :key="`mismatch-${id}`"><li x-text="moduleLabel(moduleFor(id))"></li></template></ul>
                </div>
            </template>
            <template x-if="unavailableModuleIds.length > 0">
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" role="alert">
                    <p class="font-semibold">Unavailable selected modules</p>
                    <p class="mt-1">These modules are no longer available to new learners. Remove or replace them before saving.</p>
                    <ul class="mt-2 list-inside list-disc"><template x-for="id in unavailableModuleIds" :key="`unavailable-${id}`"><li x-text="moduleLabel(moduleFor(id))"></li></template></ul>
                </div>
            </template>
            <div class="sr-only" aria-live="polite" x-text="dragAnnouncement"></div>
            <template x-if="isDragging()">
                <div class="learning-path-order-overlay fixed z-50 rounded-xl border border-purple-300 bg-white px-3 py-2 text-sm font-semibold shadow-xl" :style="dragOverlayStyle()" aria-hidden="true" x-text="moduleLabel(moduleFor(draggedId))"></div>
            </template>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('admin.learning-paths.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100">Cancel</a>
            <button type="submit" class="rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">{{ $editing ? 'Save changes' : 'Create path' }}</button>
        </div>
    </form>
</div>
