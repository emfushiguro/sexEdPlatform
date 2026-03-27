# Instructor Panel Redesign — Implementation Plan

**Design doc:** `docs/plans/2026-03-14-instructor-panel-redesign-design.md`
**Date:** 2026-03-14
**Branch:** `feat/admin-panel-integration`

---

## Phase 1 — Shared Infrastructure

**Goal:** Lay the groundwork shared by all pages before touching individual views.

### Step 1.1 — Verify SoftDeletes on Module + User models

- Check `app/Models/Module.php` — ensure `use SoftDeletes` trait and `deleted_at` in `$fillable` or casts
- Check `app/Models/User.php` — same
- If missing, add the trait and create migrations: `php artisan make:migration add_soft_deletes_to_modules_table` / `...users_table`
- Run migrations

### Step 1.2 — Add activate/deactivate routes for modules

Add to `routes/web.php` (inside instructor route group):
```php
Route::patch('modules/{module}/activate', [ModuleController::class, 'activate'])->name('instructor.modules.activate');
Route::patch('modules/{module}/deactivate', [ModuleController::class, 'deactivate'])->name('instructor.modules.deactivate');
```

Add to `ModuleController.php`:
```php
public function activate(Module $module) {
    $module->update(['is_published' => true]);
    return back()->with('success', 'Module activated.');
}
public function deactivate(Module $module) {
    $module->update(['is_published' => false]);
    return back()->with('success', 'Module deactivated.');
}
```

### Step 1.3 — Add lesson reorder route

```php
Route::patch('lessons/reorder', [LessonController::class, 'reorder'])->name('instructor.lessons.reorder');
```

`LessonController@reorder`:
```php
public function reorder(Request $request) {
    foreach ($request->order as $index => $id) {
        Lesson::where('id', $id)->update(['order' => $index + 1]);
    }
    return response()->json(['success' => true]);
}
```

### Step 1.4 — Update ModuleController@store redirect

Change `return redirect()->route('instructor.modules.index')` to:
```php
return redirect()->route('instructor.modules.show', $module)
    ->with('success', 'Module created. Add your first lesson below.');
```

### Step 1.5 — Update LessonController@store redirect

Change redirect to:
```php
return redirect()->route('instructor.lessons.show', $lesson)
    ->with('success', 'Lesson created. Add topics and a quiz below.');
```

### Step 1.6 — UserController soft delete

Ensure `UserController@destroy` uses `$user->delete()` (soft delete) not `forceDelete()`.
Add to UserController:
```php
public function restore($id) {
    User::withTrashed()->findOrFail($id)->restore();
    return back()->with('success', 'User restored.');
}
```
Add route: `Route::patch('users/{id}/restore', ...)->name('instructor.users.restore');`

---

## Phase 2 — Manage Modules Page (`instructor/modules/index`)

### Step 2.1 — Rewrite `instructor/modules/index.blade.php`

Replace entire file. Structure:

```
@extends('layouts.instructor-app')
@section('content')

<!-- Page header: title + Create Module button -->
<!-- Search input (Alpine x-model="query") -->
<!-- Filter tabs: All / Published / Draft / Archived (Alpine x-model="activeTab") -->

<!-- Pending enrollments alert (existing logic, restyled) -->

<!-- Module card grid: grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5 -->
@forelse($modules as $module)
  <!-- Module card (hybrid layout — see design doc Section 3.1) -->
  <!-- Card top zone: brand gradient bg + thumbnail + status badge -->
  <!-- Card body: title, description (2-line clamp) -->
  <!-- Card stats row: lessons · quizzes · enrolled -->
  <!-- Card footer: eye / pencil / power / trash icons with tooltips -->
@empty
  <!-- Empty state with brand gradient CTA -->
@endforelse

<!-- Module creation modal partial -->
@include('instructor.modules.partials.module-modal')

@endsection

@push('scripts')
<!-- Alpine component: moduleIndex() with query, activeTab state -->
@endpush
```

**Alpine data:**
```js
function moduleIndex() {
    return {
        query: '',
        activeTab: 'all', // all | published | draft | archived
        get filteredModules() { /* filter by tab + query */ }
    }
}
```

**Card conditional actions (Blade logic):**
```php
@if($module->trashed())
  {{-- Show Restore button only --}}
@elseif($module->enrolledLearners()->count() > 0)
  {{-- Show eye, pencil, power icon (no trash) --}}
@else
  {{-- Show eye, pencil, power icon + trash --}}
@endif
```

**Stat chips:** Use `$module->lessons_count`, `$module->quizzes_count`, and `$module->enrolledLearners()->count()` (ensure `withCount` in controller).

### Step 2.2 — Update ModuleController@index

Add `withCount` and `withTrashed` (for archived tab):
```php
$modules = Module::where('instructor_id', auth()->id())
    ->withTrashed()
    ->withCount(['lessons', 'quizzes'])
    ->with('thumbnail')
    ->orderByDesc('created_at')
    ->paginate(12);
```

---

## Phase 3 — Module Creation Modal

### Step 3.1 — Create `instructor/modules/partials/module-modal.blade.php`

Pattern: copy structure from `instructor/quizzes/partials/quiz-modal.blade.php`.

**Fields:** Title, Description (textarea), Thumbnail (file input with JS preview), Age Group (select: Kids/Teens/Adults/All Ages), Status toggle (Draft/Published).

**No duration field.**

**Alpine component:** `moduleModal()` — manages open/close state.

**Event listener:** `@open-module-modal.window="open = true"`

**Form action:** `POST {{ route('instructor.modules.store') }}`

**Trigger button** in `index.blade.php`:
```html
<button @click="$dispatch('open-module-modal')" ...>
  + Create Module
</button>
```

### Step 3.2 — Add image preview JS to modal

```js
previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        this.thumbnailPreview = URL.createObjectURL(file);
    }
}
```

---

## Phase 4 — Module Show Page (`instructor/modules/show`)

### Step 4.1 — Rewrite section headers and wrappers

Replace `bg-white shadow-sm sm:rounded-lg` wrappers with:
```html
<div class="rounded-2xl bg-white shadow-sm border border-gray-100 dark:border-gray-700 p-6">
```

Section headers use the brand left-border style:
```html
<div class="border-l-4 pl-3 mb-4" style="border-color: #730DB1;">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Section Title</h2>
    <p class="text-xs text-gray-400">Subtitle</p>
</div>
```

### Step 4.2 — Redesign Module Info Card (section 1)

Horizontal layout: thumbnail left + stat chips right. 4 chips: Duration · Lessons · Enrolled · Status badge.

### Step 4.3 — Redesign Enrolled Learners section (section 2)

Add Alpine tab state: `enrollmentTab: 'all'` with `All / Pending / Approved / Rejected` tabs.

Approve/reject buttons submit via `fetch()` to existing enrollment routes (no page reload). On success, update row status badge inline and decrement pending count badge.

```js
async approveEnrollment(id) {
    const res = await fetch(`/instructor/enrollments/${id}/approve`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
    });
    if (res.ok) { this.updateRow(id, 'approved'); }
}
```

Limit to 5 rows preview. Add "View All →" link to full enrollments page.

Pending count badge: `<span x-text="pendingCount" x-show="pendingCount > 0" class="bg-purple-100 text-purple-700 text-xs font-bold rounded-full px-1.5 py-0.5 ml-1"></span>`

### Step 4.4 — Redesign Lessons List section (section 3)

Add SortableJS drag handles:
```html
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const el = document.getElementById('lessons-sortable');
    if (el) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const order = [...el.querySelectorAll('[data-lesson-id]')].map(el => el.dataset.lessonId);
                fetch('{{ route("instructor.lessons.reorder") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ order })
                });
            }
        });
    }
</script>
@endpush
```

Replace move-up/move-down forms with the drag handle SVG icon.

Each lesson row: drag handle | `#order` badge | title | type badge (color-coded) | duration chip | `X completed` text | View / Edit / Delete icons.

"Add Lesson" button → `$dispatch('open-lesson-modal', { moduleId: {{ $module->id }} })`.

---

## Phase 5 — Lesson Slide-Over Modal

### Step 5.1 — Create `instructor/lessons/partials/lesson-slideout.blade.php`

Right slide-over panel:
```html
<div x-data="lessonSlideOut()" @open-lesson-modal.window="open = true; moduleId = $event.detail.moduleId" x-cloak>
    <!-- Backdrop -->
    <div x-show="open" @click="open = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40" x-transition:enter="..." x-transition:leave="..."></div>
    <!-- Panel -->
    <div x-show="open" class="fixed top-0 right-0 h-full w-full max-w-lg bg-white dark:bg-gray-900 z-50 shadow-xl flex flex-col" x-transition:enter="transition-transform duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition-transform duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
        <!-- Header: "New Lesson" title + close button -->
        <!-- Body: form fields -->
        <!-- Footer: Cancel + "Create & Add Topics" button -->
    </div>
</div>
```

**Fields:** Module (pre-selected disabled select), Title (text), Description (textarea), Order (number, auto-calc).

**Form action:** `POST {{ route('instructor.lessons.store') }}`

### Step 5.2 — Include slide-over in module show page

```blade
@include('instructor.lessons.partials.lesson-slideout')
```

---

## Phase 6 — Manage Lessons Page (`instructor/lessons/index`)

### Step 6.1 — Rewrite `instructor/lessons/index.blade.php`

Replace table with accordion grouped by module:

```blade
<!-- Page header + search + module filter -->

@forelse($moduleGroups as $moduleGroup)
<div x-data="{ open: {{ request('module_id') == $moduleGroup->id || $loop->first ? 'true' : 'false' }} }" class="rounded-2xl ...">
    <!-- Accordion header: module title, lesson count, toggle chevron, + Add Lesson button -->
    <div @click="open = !open" class="flex items-center justify-between p-4 cursor-pointer ...">
        <div>
            <span class="font-semibold text-sm">{{ $moduleGroup->title }}</span>
            <span class="text-xs text-gray-400 ml-2">{{ $moduleGroup->lessons->count() }} lessons</span>
        </div>
        <div class="flex items-center gap-2">
            <button @click.stop="$dispatch('open-lesson-modal', { moduleId: {{ $moduleGroup->id }} })" ...>+ Add</button>
            <!-- chevron svg rotates with open -->
        </div>
    </div>
    <!-- Accordion body: lessons rows -->
    <div x-show="open" x-collapse>
        @forelse($moduleGroup->lessons->sortBy('order') as $lesson)
        <!-- Lesson row: order badge | title | type badge | duration | Edit | Delete -->
        @empty
        <!-- "No lessons yet" placeholder -->
        @endforelse
    </div>
</div>
@empty
<!-- Empty state -->
@endforelse

@include('instructor.lessons.partials.lesson-slideout')
```

### Step 6.2 — Update LessonController@index

Group lessons by module:
```php
$moduleGroups = Module::where('instructor_id', auth()->id())
    ->with(['lessons' => fn($q) => $q->orderBy('order')])
    ->get();
```

Pass to view. Also pass `$modules` for filter dropdown.

**Search:** Alpine `x-model="query"` — use `x-show` on accordion groups and lesson rows, auto-expand groups with matches.

---

## Phase 7 — Manage Quizzes Page (`instructor/quizzes/index`)

### Step 7.1 — Rewrite `instructor/quizzes/index.blade.php`

Follow TailAdmin `basic-tables-three` pattern:

```blade
@push('scripts')
<script>
function quizTable() {
    return {
        search: '',
        moduleFilter: '',
        typeFilter: '',
        currentPage: 1,
        perPage: 10,
        quizzes: @json($quizzes),
        get filtered() {
            return this.quizzes.filter(q =>
                (!this.search || q.title.toLowerCase().includes(this.search.toLowerCase())) &&
                (!this.moduleFilter || q.module_id == this.moduleFilter) &&
                (!this.typeFilter || q.type === this.typeFilter)
            );
        },
        get paginated() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },
        get totalPages() { return Math.ceil(this.filtered.length / this.perPage); }
    }
}
</script>
@endpush
```

**Columns:** Title+description | Belongs To (pill badge: module/lesson) | Questions count | Passing % | Status badge | Actions (`x-common.table-dropdown`)

**Actions dropdown:** View → `route('instructor.quizzes.show', id)`, Edit → `route('instructor.quizzes.edit', id)`, Toggle Active (PATCH), Delete.

### Step 7.2 — Update QuizController@index

Pass quizzes as JSON-serializable collection:
```php
$quizzes = Quiz::with(['module', 'lesson', 'questions'])
    ->whereHas('module', fn($q) => $q->where('instructor_id', auth()->id()))
    ->orWhereHas('lesson.module', fn($q) => $q->where('instructor_id', auth()->id()))
    ->withCount('questions')
    ->get();
```

---

## Phase 8 — Manage Users/Learners Page (`instructor/users/index`)

### Step 8.1 — Rewrite `instructor/users/index.blade.php`

Follow TailAdmin `basic-tables-two` (checkboxes) + `basic-tables-three` (pagination+search) combined pattern.

**Alpine component:**
```js
function userTable() {
    return {
        search: '',
        roleFilter: '',
        statusFilter: '',
        currentPage: 1,
        perPage: 15,
        expandedRow: null,
        toggleRow(id) { this.expandedRow = this.expandedRow === id ? null : id; },
        // paginated/filtered getters same as quizTable pattern
    }
}
```

**Expandable row:**
```html
<!-- Normal row (clickable) -->
<tr @click="toggleRow({{ $user->id }})" class="cursor-pointer hover:bg-purple-50/40 transition-colors">
    ...
</tr>
<!-- Detail row (x-show, x-collapse) -->
<tr x-show="expandedRow === {{ $user->id }}" x-collapse>
    <td colspan="7" class="px-4 pb-3 pt-0">
        <div class="bg-gray-50 rounded-xl p-3 text-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest mb-2">Enrolled Modules</p>
            @foreach($user->enrollments as $enrollment)
            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-white border border-gray-200 mr-1 mb-1">
                {{ $enrollment->module->title }}
                <span class="{{ $enrollment->status === 'approved' ? 'text-green-600' : 'text-gray-400' }}">{{ ucfirst($enrollment->status) }}</span>
            </span>
            @endforeach
        </div>
    </td>
</tr>
```

**Soft delete confirmation modal:**
```html
@include('instructor.users.partials.delete-confirm-modal')
```
Trigger: `@click="$dispatch('open-delete-user-modal', { userId: {{ $user->id }} })"`

### Step 8.2 — Create `instructor/users/partials/delete-confirm-modal.blade.php`

Centered modal with warning copy and Cancel / "Delete User" (red) buttons. Uses `fetch()` to `DELETE instructor.users.destroy` without page reload; on success removes row from Alpine data.

### Step 8.3 — Update UserController@index

Add `withCount('enrollments')`, `with('enrollments.module')`, include soft-deleted check.

---

## Phase 9 — Lesson Show Page (`instructor/lessons/show`)

### Step 9.1 — Design refresh

Replace all `bg-white shadow-sm sm:rounded-lg` with `rounded-2xl bg-white shadow-sm border border-gray-100 dark:border-gray-700`.

Replace plain `<h2>` section titles with the brand left-border header pattern.

### Step 9.2 — Remove duplicate "Create Quiz" button

Find the "Create Quiz" button inside the Lesson Quizzes card body. Remove it. Keep only the top toolbar button.

### Step 9.3 — Drag-and-drop topic reorder

Add SortableJS to `@push('scripts')`. Attach to `#topics-sortable` list. On `end` event, `fetch()` PATCH to a new `instructor.topics.reorder` route:
```php
Route::patch('topics/reorder', [TopicController::class, 'reorder'])->name('instructor.topics.reorder');
```

Replace move-up / move-down `<form>` buttons with drag handle SVG (≡).

### Step 9.4 — Learner progress overview chip

Above the topics table, add a stat chip:
```html
<div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3 flex items-center gap-3 mb-4">
    <!-- graduation cap SVG icon -->
    <div>
        <p class="text-xs text-gray-400 uppercase tracking-widest font-medium">Learner Progress</p>
        <p class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ $completedCount }} / {{ $enrolledCount }} completed
        </p>
    </div>
    <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
        <div class="h-full rounded-full" style="background: linear-gradient(to right, #A30EB2, #3B0CB1); width: {{ $completionRate }}%"></div>
    </div>
</div>
```

Pass `$completedCount`, `$enrolledCount`, `$completionRate` from `LessonController@show`.

---

## Phase 10 — Final Checks

- [ ] All SVG icons sourced from design doc icon reference (no emojis anywhere)
- [ ] `x-cloak` used on all Alpine `x-show` elements (already global in layout)
- [ ] `@stack('scripts')` used for Alpine components, not inline `<script>` in body
- [ ] `@push('head')` for any page-specific CSS (not `@stack('styles')`)
- [ ] All form buttons use `type="submit"` explicitly
- [ ] CSRF tokens present on all `fetch()` calls
- [ ] Dark mode classes present (`dark:bg-gray-900`, `dark:text-white`, etc.) matching learner-side patterns
- [ ] Flash toast messages working (instructor-app layout already has `window.toast` system)
- [ ] SortableJS CDN only loaded on pages that need it (`@push('scripts')`)
- [ ] Test soft delete: deleted modules/users disappear from normal list, appear in Archived tab / with-trashed query
- [ ] Test redirect flows: module create → modules.show, lesson create → lessons.show

---

## Execution Order

| Phase | Area | Priority |
|---|---|---|
| 1 | Infrastructure (routes, soft deletes, redirects) | First |
| 2+3 | Modules index + create modal | High |
| 4 | Module show redesign | High |
| 5 | Lesson slide-over modal | High (depends on 4) |
| 6 | Lessons index accordion | Medium |
| 7 | Quizzes index table | Medium |
| 8 | Users/learners table | Medium |
| 9 | Lesson show redesign | Medium |
| 10 | Final checks | Last |
