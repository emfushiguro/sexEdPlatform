# Instructor Panel UI/UX Enhancement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Modernize instructor panel UI from old blue theme to consistent purple gradient brand identity across 6 key pages, plus add learner review modal to enrollments page.

**Architecture:** Primarily frontend UI updates (Blade template modifications). Task 1-7: pure styling changes with no database/business logic changes. Task 8: adds new modal UI feature using existing backend infrastructure (Alpine.js + existing enrollment controller methods).

**Tech Stack:** Blade templates, Tailwind CSS v3, Alpine.js (existing integrations preserved)

---

## Pre-Implementation Checklist

Before starting:
- [ ] Current branch: `feat/admin-integration-redesign-completion`
- [ ] Design document reviewed: `docs/plans/2026-03-20-instructor-panel-ui-enhancement-design.md`
- [ ] Backup current state: `git stash` (if needed)
- [ ] Laravel dev server running: `php artisan serve`
- [ ] Vite dev server running: `npm run dev`

---

## Task 1: Deprecate Lesson Creation Page

**Goal:** Replace old lesson creation page with deprecation notice, directing users to slideout modal workflow.

**Files:**
- Modify: `resources/views/instructor/lessons/create.blade.php`

**Rationale:** The modern workflow uses `lesson-slideout.blade.php` modal which already has the correct purple gradient toggle. The old create page bypasses this and has no toggle at all.

---

### Step 1: Write verification test

Create a simple feature test to ensure the route still works after modification.

**File:** `tests/Feature/Instructor/LessonManagementTest.php`

```php
<?php

namespace Tests\Feature\Instructor;

use Tests\TestCase;
use App\Models\User;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_create_page_loads_for_instructor(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');

        $module = Module::factory()->create(['created_by' => $instructor->id]);

        $this->actingAs($instructor)
             ->get(route('instructor.lessons.create', ['module_id' => $module->id]))
             ->assertOk();
    }
}
```

---

### Step 2: Run test to verify it passes (baseline)

```bash
php artisan test --filter=test_lesson_create_page_loads_for_instructor
```

**Expected:** PASS (page currently loads with old form)

---

### Step 3: Replace create.blade.php with deprecation notice

**File:** `resources/views/instructor/lessons/create.blade.php`

Replace entire contents with:

```blade
@extends('layouts.instructor-app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Lesson create moved to modal workflow</h1>
        <p class="text-sm text-gray-500 mb-6">
            Use the lesson creation action from the Manage Lessons page to add new lessons in the slideout modal.
        </p>

        <a href="{{ route('instructor.lessons.index') }}"
           class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98] shadow-sm"
           style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Go to Manage Lessons
        </a>
    </div>
</div>
@endsection
```

---

### Step 4: Run test to verify it still passes

```bash
php artisan test --filter=test_lesson_create_page_loads_for_instructor
```

**Expected:** PASS (page loads with deprecation notice)

---

### Step 5: Visual verification

**Manual check:**
1. Navigate to `/instructor/lessons/create`
2. Verify deprecation notice displays with purple gradient icon
3. Click "Go to Manage Lessons" button → should redirect to lesson index
4. From lesson index, click "Create Lesson" button → slideout modal should open
5. Verify slideout toggle has clear visual states (gray = OFF, purple gradient = ON)

---

### Step 6: Commit

```bash
git add resources/views/instructor/lessons/create.blade.php tests/Feature/Instructor/LessonManagementTest.php
git commit -m "feat(instructor): deprecate lesson creation page in favor of slideout modal

Replace old full-page lesson creation form with deprecation notice.
Users are directed to use the modern slideout modal workflow from
the Manage Lessons page.

- Add deprecation notice with purple gradient styling
- Direct users to lesson index page
- Add test coverage for route accessibility
- Slideout modal already has correct purple toggle implementation

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 2: Remove Time Limit Display from Quiz Overview

**Goal:** Remove time limit stat card from quiz overview page (UI only, no database changes).

**Files:**
- Modify: `resources/views/instructor/quizzes/show.blade.php`

**Rationale:** Time limit field is no longer needed in instructor view per user request. Database column remains for backward compatibility.

---

### Step 1: Locate the time limit display block

**File:** `resources/views/instructor/quizzes/show.blade.php`

Find and note the time limit stat card (approximately lines 119-123).

```bash
grep -n "Time Limit" resources/views/instructor/quizzes/show.blade.php
```

**Expected output:** Line number where "Time Limit" appears

---

### Step 2: Read current quiz show view

```bash
cat resources/views/instructor/quizzes/show.blade.php | head -n 150
```

Review the structure to understand surrounding stat cards.

---

### Step 3: Remove time limit stat card

**File:** `resources/views/instructor/quizzes/show.blade.php`

Remove this block (lines ~119-123):

```blade
<div class="bg-purple-50/40 rounded-xl p-3 border border-purple-100/60">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Time Limit</p>
    <p class="text-xl font-bold text-gray-900">
        {{ $quiz->time_limit ? $quiz->time_limit . ' min' : '—' }}
    </p>
</div>
```

**Note:** Leave surrounding grid structure intact. Only remove the time limit card div.

---

### Step 4: Visual verification

**Manual check:**
1. Navigate to any quiz overview page: `/instructor/quizzes/{quiz_id}`
2. Verify stat cards display: Total Questions, Total Points, Active/Inactive status
3. Verify NO time limit card appears
4. Verify grid layout still looks balanced

---

### Step 5: Functional verification

Test that quiz functionality remains intact:

1. Edit quiz via modal → Save → Verify saves correctly
2. Add question → Verify question appears in list
3. Toggle quiz active/inactive → Verify status updates

---

### Step 6: Commit

```bash
git add resources/views/instructor/quizzes/show.blade.php
git commit -m "refactor(instructor): remove time limit display from quiz overview

Remove time limit stat card from quiz overview page. Field remains
in database and quiz edit forms for backward compatibility, but is
no longer displayed in the instructor overview stats.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 3: Update Quiz Modal to Purple Gradient Theme

**Goal:** Convert quiz creation/edit modal from blue theme to purple gradient, matching lesson slideout aesthetic.

**Files:**
- Modify: `resources/views/instructor/quizzes/partials/quiz-modal.blade.php`

**Rationale:** Quiz modal currently uses blue (`bg-blue-600`) while lesson slideout uses purple gradient. This creates visual inconsistency.

---

### Step 1: Read current quiz modal implementation

```bash
cat resources/views/instructor/quizzes/partials/quiz-modal.blade.php | head -n 200
```

Note the locations of:
- Active toggle checkbox styling
- Submit button styling
- Input focus states

---

### Step 2: Update active toggle to purple gradient

**File:** `resources/views/instructor/quizzes/partials/quiz-modal.blade.php`

Find the active toggle section (approximately lines 158-172 based on exploration).

**OLD:**
```blade
<div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
```

**NEW:**
```blade
<div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 peer-focus:ring-2 peer-focus:ring-purple-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"
     style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"></div>
```

**Note:** Add inline style for gradient background when checked. Remove `peer-checked:bg-blue-600` class.

---

### Step 3: Update submit button to purple gradient

Find the submit button (typically in the modal footer).

**OLD:**
```blade
<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-5 rounded-lg transition">
    Create Quiz
</button>
```

**NEW:**
```blade
<button type="submit"
        class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
    <span x-text="isEdit ? 'Save Quiz Changes' : 'Create Quiz'"></span>
</button>
```

---

### Step 4: Update all input focus states

Replace all instances of blue focus states with purple:

**Search and replace:**
- `focus:border-blue-500` → `focus:border-purple-400`
- `focus:ring-blue-500` → `focus:ring-purple-300`
- `focus:ring-blue-300` → `focus:ring-purple-300`

**Files to modify:** Same file `quiz-modal.blade.php`

---

### Step 5: Update helper text background (if any blue-tinted backgrounds exist)

Search for any blue-tinted helper backgrounds:

```bash
grep -n "bg-blue-50\|text-blue-" resources/views/instructor/quizzes/partials/quiz-modal.blade.php
```

If found, replace:
- `bg-blue-50` → `bg-purple-50`
- `text-blue-700` → `text-purple-700`
- `border-blue-200` → `border-purple-200`

---

### Step 6: Visual verification

**Manual check:**
1. Navigate to `/instructor/quizzes`
2. Click "Create Quiz" button → modal opens
3. Verify:
   - Modal button uses purple gradient (not blue)
   - Active toggle uses purple gradient when checked
   - Input focus rings are purple (not blue)
   - No blue UI elements remain
4. Fill out form and submit → verify quiz creates successfully
5. Edit existing quiz → verify modal loads correctly with purple theme

---

### Step 7: Functional verification

Test quiz creation/edit workflow:

1. Create new quiz → Fill all fields → Submit → Verify saves
2. Edit existing quiz → Change title → Submit → Verify updates
3. Toggle active/inactive → Verify visual feedback and saves correctly
4. Cancel modal → Verify closes without saving

---

### Step 8: Commit

```bash
git add resources/views/instructor/quizzes/partials/quiz-modal.blade.php
git commit -m "refactor(instructor): update quiz modal to purple gradient theme

Convert quiz creation/edit modal from blue theme to purple gradient,
aligning with the modern brand identity used in lesson slideout.

Changes:
- Active toggle: blue → purple gradient background
- Submit button: blue solid → purple gradient
- Focus states: blue rings → purple rings
- Consistent with lesson modal aesthetic

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 4: Modernize Edit Question Page

**Goal:** Update edit-question page to match the modern styling of add-question page (purple gradient, rounded-xl, consistent borders).

**Files:**
- Modify: `resources/views/instructor/quizzes/edit-question.blade.php`

**Reference:** `resources/views/instructor/quizzes/add-question.blade.php` (already modernized)

**Rationale:** Edit and add question pages should be visually identical. Currently edit uses old design (blue, indigo accents, basic corners).

---

### Step 1: Review add-question page styling (reference)

```bash
grep -n "rounded-\|border-\|bg-blue\|bg-purple\|gradient" resources/views/instructor/quizzes/add-question.blade.php | head -n 30
```

Note the modern patterns:
- Cards: `rounded-2xl bg-white shadow-sm border border-gray-100`
- Inputs: `rounded-xl border-gray-200 focus:border-purple-400 focus:ring-purple-300`
- Buttons: Purple gradient with `hover:opacity-90 active:scale-[0.98]`
- Secondary buttons: `bg-purple-50 text-purple-700 border-purple-200`

---

### Step 2: Read current edit-question structure

```bash
cat resources/views/instructor/quizzes/edit-question.blade.php | head -n 100
```

Identify sections that need updating:
- Card container
- Input fields
- Buttons (primary, secondary, add option/answer)
- Option/answer rows
- File upload styling

---

### Step 3: Update card container

**File:** `resources/views/instructor/quizzes/edit-question.blade.php`

**OLD:**
```blade
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
```

**NEW:**
```blade
<div class="rounded-2xl bg-white shadow-sm border border-gray-100">
    <div class="p-6 space-y-6">
```

---

### Step 4: Update primary submit button

**OLD:**
```blade
<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition">
    Update Question
</button>
```

**NEW:**
```blade
<button type="submit"
        class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98]"
        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
    </svg>
    Update Question
</button>
```

---

### Step 5: Update secondary buttons (Add Option, Insert Blank, etc.)

**Find all instances of:**
- `bg-indigo-50 text-indigo-700 hover:bg-indigo-100`
- `bg-green-50 text-green-700`

**Replace with:**
```blade
class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
```

---

### Step 6: Update all input styling

**Search and replace patterns:**

1. **Input corners:**
   - `rounded-md` → `rounded-xl`
   - `rounded-lg` (inputs) → `rounded-xl`

2. **Input borders:**
   - `border-gray-300` → `border-gray-200`

3. **Input focus states:**
   - `focus:border-blue-500` → `focus:border-purple-400`
   - `focus:ring-blue-500` → `focus:ring-purple-300`

Apply to:
- Text inputs
- Textareas
- Select dropdowns
- Number inputs

---

### Step 7: Update option/answer row styling

**OLD (example):**
```blade
<div class="flex items-center gap-3 p-2 rounded-lg border border-gray-200">
```

**NEW:**
```blade
<div class="flex items-center gap-3 p-3 rounded-xl border bg-gray-50/60"
     :class="option.isCorrect ? 'border-green-200 bg-green-50/50' : 'border-gray-100'">
```

Ensures consistent spacing and visual feedback for correct answers.

---

### Step 8: Update file upload button styling

**Find file input:**
```blade
<input type="file" class="...">
```

**Update with:**
```blade
<input type="file"
       class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 transition"
       accept="image/*">
```

---

### Step 9: Update section headers with purple left border

**Find section headers:**
```blade
<div class="border-l-4 border-blue-500 pl-3">
    <p class="text-sm font-semibold text-gray-900">Answer Options</p>
```

**Replace with:**
```blade
<div class="border-l-4 pl-3" style="border-color: #730DB1;">
    <p class="text-sm font-semibold text-gray-900">Answer Options</p>
    <p class="text-xs text-gray-400">Helper text here</p>
</div>
```

---

### Step 10: Visual verification

**Manual check:**
1. Navigate to edit question page for any quiz
2. Verify card uses `rounded-2xl` with subtle border
3. Verify submit button has purple gradient
4. Verify all inputs have purple focus rings (tab through them)
5. Verify "Add Option" buttons are purple (not blue/indigo)
6. Verify option rows have subtle gray backgrounds
7. Verify file upload button has purple styling
8. Compare side-by-side with add-question page → should be visually identical

---

### Step 11: Functional verification

Test all question types:

1. **Multiple Choice:**
   - Edit existing question → Change option text → Save → Verify
   - Mark different option as correct → Save → Verify

2. **True/False:**
   - Edit question → Change correct answer → Save → Verify

3. **Fill Blank:**
   - Edit question → Add blank → Add answer → Save → Verify

4. **Identification:**
   - Edit question → Upload new image → Save → Verify

5. **Edge cases:**
   - Validation errors display correctly
   - Cancel button works
   - TinyMCE (if used) initializes

---

### Step 12: Commit

```bash
git add resources/views/instructor/quizzes/edit-question.blade.php
git commit -m "refactor(instructor): modernize edit question page to purple gradient theme

Align edit-question page styling with add-question page:
- Purple gradient buttons (was blue)
- Rounded-xl inputs and rounded-2xl cards (was rounded-md/lg)
- Border-gray-200 (was gray-300)
- Purple focus rings (was blue)
- Purple secondary buttons (was indigo)
- Consistent option row backgrounds

Visual parity achieved between add/edit question experiences.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 5: Modernize Topic Creation Page

**Goal:** Update topic creation page from blue theme to purple gradient while maintaining full-page form structure.

**Files:**
- Modify: `resources/views/instructor/topics/create.blade.php` (54.4KB)

**Rationale:** Topics are content-heavy (TinyMCE, file uploads, type selection). Full-page form provides better UX than modal. Update styling to match modern design system.

---

### Step 1: Backup original file

```bash
cp resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/create.blade.php.backup
```

This file is 54.4KB — create backup before mass updates.

---

### Step 2: Update card container

**Find (approximately line 1-30):**
```blade
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
```

**Replace with:**
```blade
<div class="bg-white shadow-sm border border-gray-100 rounded-2xl">
    <div class="p-6 space-y-6">
```

---

### Step 3: Update primary submit button

**Find:**
```blade
<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg shadow transition">
    Create Topic
</button>
```

**Replace with:**
```blade
<button type="submit"
        class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
        style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    Create Topic
</button>
```

---

### Step 4: Batch update input styling

**Use find-and-replace for efficiency:**

1. **Rounded corners:**
```bash
# In your editor, replace:
rounded-md → rounded-xl
rounded-lg (for inputs/buttons) → rounded-xl
```

2. **Border colors:**
```bash
border-gray-300 → border-gray-200
```

3. **Focus states:**
```bash
focus:border-blue-500 → focus:border-purple-400
focus:ring-blue-500 → focus:ring-purple-300
focus:border-blue-300 → focus:border-purple-400
```

4. **Button colors:**
```bash
bg-blue-600 → Use gradient style (see step 3)
bg-blue-50 text-blue-700 → bg-purple-50 text-purple-700
border-blue-200 → border-purple-200
hover:bg-blue-100 → hover:bg-purple-100
```

**Caution:** Review each replacement to ensure context is correct (don't replace status badges, for example).

---

### Step 5: Update type selection cards

Type cards are used for selecting topic type (video, text, worksheet, quiz, interactive).

**Find pattern like:**
```blade
<input type="radio" name="type" value="video" class="...">
<div class="... border-2" :class="selectedType === 'video' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
```

**Update with:**
```blade
<input type="radio" name="type" value="video" class="...">
<div class="... border-2 rounded-xl" :class="selectedType === 'video' ? 'border-purple-400 bg-purple-50/50' : 'border-gray-200 bg-white'">
```

Apply to all type cards (video, text, worksheet, quiz, interactive).

---

### Step 6: Update section headers

**Find:**
```blade
<h3 class="text-lg font-medium text-gray-900 mb-4">
```

**Replace with:**
```blade
<h3 class="text-lg font-semibold text-gray-900 mb-4">
```

**Find:**
```blade
<p class="text-sm text-gray-500">
```

**Replace with:**
```blade
<p class="text-sm text-gray-400">
```

Ensures consistent typography hierarchy.

---

### Step 7: Update file upload button

**Find:**
```blade
<input type="file" class="..." accept="video/*,application/pdf">
```

**Wrap or update with:**
```blade
<input type="file"
       class="block w-full text-sm text-gray-500 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 transition"
       accept="video/*,application/pdf">
```

---

### Step 8: Update prerequisite checkbox section

**Find prerequisite checkbox:**
```blade
<input type="checkbox" name="is_prerequisite" class="w-5 h-5 text-blue-600 border-2 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
```

**Replace with:**
```blade
<input type="checkbox" name="is_prerequisite" value="1"
       class="w-5 h-5 mt-0.5 text-purple-600 border-2 border-gray-300 rounded focus:ring-2 focus:ring-purple-400">
```

---

### Step 9: Update TinyMCE wrapper (if applicable)

**Ensure TinyMCE initialization remains unchanged** but wrapper div uses consistent styling:

```blade
<div class="mb-6">
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        Content <span class="text-red-500">*</span>
    </label>
    <textarea id="content" name="content"
              class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300"></textarea>
</div>
```

**Do NOT modify TinyMCE initialization JavaScript** — only update wrapper CSS classes.

---

### Step 10: Visual verification

**Manual check:**
1. Navigate to topic creation page: `/instructor/topics/create?lesson_id={id}`
2. Verify page uses purple gradient button (not blue)
3. Verify all inputs have purple focus rings (tab through)
4. Verify type selection cards highlight with purple border when selected
5. Verify file upload button has purple styling
6. Verify card container has `rounded-2xl` and subtle border
7. Verify no blue UI elements remain

---

### Step 11: Functional verification

Test topic creation workflow:

1. **Video type:**
   - Select video type → Upload video file → Fill title → Submit
   - Verify topic creates and video saves

2. **Text type:**
   - Select text type → TinyMCE loads → Add content → Submit
   - Verify topic creates with rich text content

3. **Worksheet type:**
   - Select worksheet → Upload PDF → Fill title → Submit
   - Verify topic creates and file saves

4. **Prerequisite:**
   - Check "Mark as Prerequisite" → Submit
   - Verify topic saves with prerequisite flag

5. **Validation errors:**
   - Submit empty form → Verify error messages display correctly

---

### Step 12: Remove backup file (if all tests pass)

```bash
rm resources/views/instructor/topics/create.blade.php.backup
```

---

### Step 13: Commit

```bash
git add resources/views/instructor/topics/create.blade.php
git commit -m "refactor(instructor): modernize topic creation page to purple gradient theme

Update topic creation page from blue theme to purple gradient brand:
- Purple gradient submit button (was blue)
- Rounded-xl inputs and rounded-2xl card (was md/lg)
- Border-gray-200 (was gray-300)
- Purple focus rings (was blue)
- Purple type selection highlights (was blue)
- Purple file upload button (was default)
- Consistent typography weights

Full-page form structure maintained for complex content editing UX.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 6: Modernize Topic Edit Page

**Goal:** Update topic edit page to match the modernized create page styling.

**Files:**
- Modify: `resources/views/instructor/topics/edit.blade.php`

**Rationale:** Edit and create pages should be visually identical. Apply same purple gradient theme updates.

---

### Step 1: Backup original file

```bash
cp resources/views/instructor/topics/edit.blade.php resources/views/instructor/topics/edit.blade.php.backup
```

---

### Step 2: Apply same updates as Task 5

**Follow Task 5 steps 2-9 for edit.blade.php:**

1. Update card container → `rounded-2xl border border-gray-100`
2. Update submit button → purple gradient
3. Batch update inputs → `rounded-xl`, `border-gray-200`, purple focus
4. Update type selection cards → purple highlights
5. Update section headers → `font-semibold`, `text-gray-400`
6. Update file upload button → purple styling
7. Update prerequisite checkbox → purple
8. Ensure TinyMCE wrapper has consistent styling

**Difference from create page:** Edit page will pre-populate fields with existing topic data. Ensure Alpine.js/Livewire bindings (if any) are not broken by CSS changes.

---

### Step 3: Visual verification

**Manual check:**
1. Navigate to topic edit page for existing topic
2. Verify styling matches create page (purple gradient, rounded-xl, etc.)
3. Verify form pre-populates with topic data
4. Verify type selection shows current topic type
5. Verify existing video/worksheet files display correctly

---

### Step 4: Functional verification

Test topic editing workflow:

1. **Edit video topic:**
   - Change title → Submit → Verify updates
   - Replace video file → Submit → Verify new file saves

2. **Edit text topic:**
   - Modify TinyMCE content → Submit → Verify updates

3. **Toggle prerequisite:**
   - Change prerequisite checkbox → Submit → Verify updates

4. **Change type:**
   - Change from video to text type → Submit → Verify type change and content migration

---

### Step 5: Remove backup file (if all tests pass)

```bash
rm resources/views/instructor/topics/edit.blade.php.backup
```

---

### Step 6: Commit

```bash
git add resources/views/instructor/topics/edit.blade.php
git commit -m "refactor(instructor): modernize topic edit page to purple gradient theme

Align topic edit page with modernized create page styling:
- Purple gradient submit button
- Rounded-xl inputs and rounded-2xl card
- Border-gray-200, purple focus rings
- Purple type selection and file upload
- Consistent with topic creation UX

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 7: Final Cross-Page Verification & Testing

**Goal:** Verify all changes work together, no regressions, visual consistency achieved.

**Files:** None (testing only)

---

### Step 1: Complete visual walkthrough

Navigate through entire instructor workflow and verify purple gradient theme:

**Lesson Management:**
- [ ] `/instructor/lessons` → Index page
- [ ] Click "Create Lesson" → Slideout opens with purple toggle
- [ ] Toggle lesson active → Visual feedback clear (gray OFF, purple ON)
- [ ] `/instructor/lessons/create` → Deprecation notice displays

**Topic Management:**
- [ ] Create new topic → Purple gradient buttons, purple focus rings
- [ ] Edit existing topic → Matches create page styling
- [ ] All topic types (video, text, worksheet) → Purple theme throughout

**Quiz Management:**
- [ ] `/instructor/quizzes` → Index page
- [ ] Click "Create Quiz" → Modal with purple theme opens
- [ ] Quiz toggle shows purple when active
- [ ] `/instructor/quizzes/{id}` → Overview does NOT show time limit
- [ ] Add question → Already modern (purple)
- [ ] Edit question → Matches add question styling

---

### Step 2: Cross-browser spot check

Test in available browsers:

1. **Chrome:**
   - [ ] All pages render correctly
   - [ ] Purple gradients display
   - [ ] Rounded corners visible
   - [ ] Focus rings work

2. **Firefox:**
   - [ ] File upload buttons styled correctly
   - [ ] Gradients render smoothly

3. **Safari (if available):**
   - [ ] Toggle switches work
   - [ ] Border-radius renders correctly

---

### Step 3: Dark mode check (if supported)

If instructor layout supports dark mode:

- [ ] Navigate through all updated pages in dark mode
- [ ] Verify `dark:` variants render correctly
- [ ] Verify text remains readable on dark backgrounds

**Note:** If dark mode is not yet implemented in instructor panel, skip this step.

---

### Step 4: Responsive layout check

Test at different viewport widths:

1. **Mobile (375px):**
   - [ ] Topic forms remain usable
   - [ ] Modals display correctly
   - [ ] Buttons don't overflow

2. **Tablet (768px):**
   - [ ] Grid layouts adjust properly
   - [ ] Slideout modal works

3. **Desktop (1440px):**
   - [ ] Max-width containers prevent excessive width
   - [ ] Spacing looks balanced

---

### Step 5: Functional regression testing

Ensure all features still work after UI updates:

**Create Flows:**
- [ ] Create lesson via slideout → Saves correctly
- [ ] Create topic (all types) → Saves correctly
- [ ] Create quiz via modal → Saves correctly
- [ ] Add question (all types) → Saves correctly

**Edit Flows:**
- [ ] Edit lesson → Updates correctly
- [ ] Edit topic → Updates correctly
- [ ] Edit quiz → Updates correctly
- [ ] Edit question → Updates correctly

**Delete Flows:**
- [ ] Delete lesson → Confirmation modal works
- [ ] Delete topic → Works
- [ ] Delete question → Works

**Toggle/Status:**
- [ ] Toggle lesson active/inactive → Updates
- [ ] Toggle quiz active/inactive → Updates

---

### Step 6: Run automated tests

```bash
# Run full test suite to catch any regressions
php artisan test
```

**Expected:** All tests pass (or same pass/fail status as before UI updates)

**If failures occur:**
- Review test output
- Check if failures are related to CSS changes (unlikely) or functional breakage
- Fix any broken tests
- Re-run until clean

---

### Step 7: Performance check

Verify no performance degradation:

1. **Page load times:**
   - [ ] Topic create page loads in < 2 seconds
   - [ ] Quiz overview loads quickly
   - [ ] No console errors

2. **TinyMCE initialization:**
   - [ ] Editors load without delay
   - [ ] No JavaScript errors in console

3. **File uploads:**
   - [ ] Upload progress indicators work
   - [ ] Large file uploads complete successfully

---

### Step 8: Document completion

Create a summary of changes for documentation:

**File:** `docs/changelogs/2026-03-20-instructor-ui-modernization.md`

```markdown
# Instructor Panel UI Modernization — Completed 2026-03-20

## Summary
Completed migration from old blue theme to modern purple gradient brand identity across instructor panel.

## Pages Updated
1. **Lesson creation page** — Deprecated in favor of slideout modal
2. **Quiz overview** — Removed time limit display
3. **Quiz modal** — Blue → purple gradient
4. **Edit question page** — Aligned with add question styling
5. **Topic creation page** — Purple gradient theme
6. **Topic edit page** — Purple gradient theme

## Design System
- **Primary gradient:** `linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)`
- **Borders:** `border-gray-200` (was gray-300)
- **Corners:** `rounded-xl` (inputs), `rounded-2xl` (cards)
- **Focus:** `focus:border-purple-400 focus:ring-purple-300`

## Testing Completed
- ✅ Visual verification across all pages
- ✅ Functional testing (create/edit/delete workflows)
- ✅ Cross-browser spot checks
- ✅ Responsive layout verification
- ✅ Automated test suite passes

## Known Issues
None

## Follow-Up Tasks
- Consider dark mode full implementation for instructor panel
- Accessibility audit for focus indicators (WCAG AA compliance)
```

---

### Step 9: Final commit

```bash
git add docs/changelogs/2026-03-20-instructor-ui-modernization.md
git commit -m "docs: add instructor UI modernization changelog

Document completion of purple gradient theme migration across
instructor panel. All 6 identified pages updated with visual
consistency achieved.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Step 10: Create summary report

**Output for user:**

```
✅ INSTRUCTOR PANEL UI/UX ENHANCEMENT — COMPLETE

📊 Summary:
- 6 pages updated to purple gradient theme
- 1 page deprecated (lesson creation)
- 1 field removed (time limit display)
- 7 commits created
- All tests passing
- Zero regressions

🎨 Design Consistency Achieved:
- Purple gradient brand identity throughout
- Consistent rounded corners (xl/2xl)
- Unified border colors (gray-200)
- Clear toggle visual states

✅ Verification Complete:
- Visual walkthroughs ✓
- Functional testing ✓
- Cross-browser checks ✓
- Responsive layouts ✓
- Automated tests ✓

📁 Documentation:
- Design doc: docs/plans/2026-03-20-instructor-panel-ui-enhancement-design.md
- Plan doc: docs/plans/2026-03-20-instructor-panel-ui-enhancement-plan.md
- Changelog: docs/changelogs/2026-03-20-instructor-ui-modernization.md

🚀 Ready for:
- Code review
- Merge to main
- Production deployment
```

---

## Post-Implementation Checklist

Before marking complete:

**Code Quality:**
- [ ] All modified files follow PSR-12 PHP standards
- [ ] Blade templates use consistent indentation
- [ ] No console errors in browser dev tools
- [ ] No deprecation warnings

**Git Hygiene:**
- [ ] All commits have descriptive messages
- [ ] Co-Authored-By tags present
- [ ] No WIP or temp commits
- [ ] Clean git history (no merge conflicts)

**Documentation:**
- [ ] Design document saved
- [ ] Implementation plan saved
- [ ] Changelog created
- [ ] CLAUDE.md updated (if needed)

**Testing:**
- [ ] Manual testing completed
- [ ] Automated tests passing
- [ ] No known regressions
- [ ] Edge cases verified

**Handoff:**
- [ ] Summary report generated
- [ ] Screenshots captured (optional)
- [ ] Ready for review process

---

## Troubleshooting Guide

### Issue: TinyMCE not initializing after styling changes

**Cause:** CSS class changes may conflict with TinyMCE initialization selector.

**Solution:**
1. Check TinyMCE init code: `tinymce.init({ selector: '#content', ... })`
2. Ensure textarea ID unchanged
3. Verify no duplicate IDs on page
4. Check browser console for JS errors

---

### Issue: File upload button not styled in Safari

**Cause:** Safari has limited support for `::file-selector-button` pseudo-element.

**Solution:**
1. Use vendor-specific pseudo-elements
2. Accept minor visual differences across browsers
3. File upload functionality remains intact

---

### Issue: Gradient backgrounds not displaying

**Cause:** Inline `style` attribute may be stripped by Blade/sanitization.

**Solution:**
1. Verify inline style syntax: `style="background: linear-gradient(...);"`
2. Check if CSP (Content Security Policy) blocks inline styles
3. Move to CSS class if inline styles are blocked

---

### Issue: Focus rings not visible in some browsers

**Cause:** Browser default focus styles may override Tailwind classes.

**Solution:**
1. Add `focus:outline-none` explicitly
2. Ensure `focus:ring-*` classes are present
3. Test with keyboard navigation (Tab key)

---

### Issue: Alpine.js reactivity broken after DOM changes

**Cause:** Changing element structure may break Alpine bindings.

**Solution:**
1. Ensure `x-model`, `x-data`, `x-show` attributes unchanged
2. Only modify CSS classes, not Alpine directives
3. Test modal open/close after changes

---

## Task 8: Add Learner Review Modal to Enrollments Index

**Goal:** Add a "Review Information" modal on enrollments index page so instructors can view learner profile, module details, and approve/reject without leaving the page.

**Files:**
- Modify: `resources/views/instructor/enrollments/index.blade.php`
- Modify: `resources/js/app.js` (Alpine.js store extension)

**Rationale:** Currently, instructors must navigate to a separate show page to see learner details. A modal provides faster workflow and keeps instructors on the enrollments list page.

**Backend Note:** All controller methods and rejection reason system already exist. This is pure UI enhancement.

---

### Step 1: Read current enrollments index structure

```bash
cat resources/views/instructor/enrollments/index.blade.php | head -n 100
```

Understand:
- Current card layout for enrollment listings
- Existing Alpine.js data structure
- Where "View" button currently is
- Inline approve/reject button implementation

---

### Step 2: Extend Alpine.js modals store

**File:** `resources/js/app.js`

Add enrollment review modal state after existing modal declarations:

```javascript
Alpine.store('modals', {
    // ... existing modals (quizModal, lessonSlideout, etc.)

    enrollmentReview: false,
    enrollmentReviewData: null,

    openEnrollmentReview(enrollmentData) {
        this.enrollmentReviewData = enrollmentData;
        this.enrollmentReview = true;
    },

    closeEnrollmentReview() {
        this.enrollmentReview = false;
        setTimeout(() => {
            this.enrollmentReviewData = null;
        }, 300); // Wait for transition
    },

    rejectModal: false,
    rejectEnrollmentId: null,
    rejectReason: '',
    rejectNote: '',
    rejectReasons: [
        { value: 'prerequisite_missing', label: 'Prerequisite module not completed' },
        { value: 'age_requirement_not_met', label: 'Age requirement not met' },
        { value: 'profile_incomplete', label: 'Learner profile is incomplete' },
        { value: 'capacity_limit', label: 'Module capacity reached' },
        { value: 'other', label: 'Other (specify in notes)' },
    ],

    openRejectModal(enrollmentId) {
        this.rejectEnrollmentId = enrollmentId;
        this.rejectReason = '';
        this.rejectNote = '';
        this.rejectModal = true;
        this.closeEnrollmentReview(); // Close review modal
    },

    closeRejectModal() {
        this.rejectModal = false;
        setTimeout(() => {
            this.rejectEnrollmentId = null;
            this.rejectReason = '';
            this.rejectNote = '';
        }, 300);
    },
});
```

---

### Step 3: Add "Review Information" button to enrollment cards

**File:** `resources/views/instructor/enrollments/index.blade.php`

Find the enrollment card where "View" button exists (likely in a foreach loop).

**Replace the "View" link:**

**OLD:**
```blade
<a href="{{ route('instructor.enrollments.show', $enrollment) }}"
   class="...">
    View
</a>
```

**NEW:**
```blade
<button type="button"
        @click="$store.modals.openEnrollmentReview({
            id: {{ $enrollment->id }},
            learner: {
                name: '{{ addslashes($enrollment->user->name) }}',
                email: '{{ $enrollment->user->email }}',
                username: '{{ $enrollment->user->learnerProfile->username ?? 'N/A' }}',
                age: {{ $enrollment->user->learnerProfile->getAge() ?? 'null' }},
                ageBracket: '{{ $enrollment->user->learnerProfile->getAgeBracket() ?? 'N/A' }}',
                gender: '{{ $enrollment->user->learnerProfile->gender ?? 'N/A' }}',
                city: '{{ $enrollment->user->learnerProfile->city->name ?? 'N/A' }}',
                barangay: '{{ $enrollment->user->learnerProfile->barangay->name ?? 'N/A' }}',
                isChild: {{ $enrollment->user->learnerProfile->requires_parental_consent ? 'true' : 'false' }},
                parentName: '{{ $enrollment->user->parent->name ?? null }}',
                profileComplete: {{ $enrollment->user->learnerProfile->isCompleted() ? 'true' : 'false' }},
            },
            module: {
                id: {{ $enrollment->module->id }},
                title: '{{ addslashes($enrollment->module->title) }}',
                description: '{{ addslashes(Str::limit($enrollment->module->description, 150)) }}',
                minAge: {{ $enrollment->module->min_age ?? 0 }},
                maxAge: {{ $enrollment->module->max_age ?? 100 }},
                lessonsCount: {{ $enrollment->module->lessons()->count() }},
                isPremium: {{ $enrollment->module->is_premium ? 'true' : 'false' }},
                thumbnailUrl: '{{ $enrollment->module->thumbnail ? asset('storage/' . $enrollment->module->thumbnail) : asset('images/default-module.jpg') }}',
            },
            status: '{{ $enrollment->status }}',
            appliedAt: '{{ $enrollment->created_at->format('M d, Y h:i A') }}',
        })"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
    </svg>
    Review Information
</button>
```

**Note:** Use `addslashes()` to escape quotes in JSON data. Ensure all Blade variables are safely escaped.

---

### Step 4: Create enrollment review modal component

**File:** `resources/views/instructor/enrollments/index.blade.php`

Add this modal component **at the end of the file**, before `@endsection`:

```blade
{{-- ══════════════════════════════════════════════════════════
     ENROLLMENT REVIEW MODAL
══════════════════════════════════════════════════════════ --}}
<div x-show="$store.modals.enrollmentReview"
     x-cloak
     @keydown.escape.window="$store.modals.closeEnrollmentReview()"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div x-show="$store.modals.enrollmentReview"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.modals.closeEnrollmentReview()"
         class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

    {{-- Modal Container --}}
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="$store.modals.enrollmentReview"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">

            {{-- Header --}}
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between"
                 style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Enrollment Review</h3>
                        <p class="text-xs text-white/70 mt-0.5">Review learner information and module details</p>
                    </div>
                </div>
                <button type="button"
                        @click="$store.modals.closeEnrollmentReview()"
                        class="p-2 rounded-lg hover:bg-white/10 transition text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-6" x-data="{ data: $store.modals.enrollmentReviewData }">

                {{-- Age Appropriateness Warning --}}
                <div x-show="data && data.learner.age && (data.learner.age < data.module.minAge || data.learner.age > data.module.maxAge)"
                     class="rounded-xl border border-orange-200 bg-orange-50 p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-orange-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-orange-800">Age Appropriateness Warning</p>
                        <p class="text-xs text-orange-700 mt-1">
                            Learner's age (<span x-text="data?.learner.age"></span>) is outside the recommended range
                            (<span x-text="data?.module.minAge"></span>-<span x-text="data?.module.maxAge"></span> years old) for this module.
                        </p>
                    </div>
                </div>

                {{-- Two Column Layout --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    {{-- LEFT COLUMN: Learner Information --}}
                    <div class="space-y-4">
                        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                            <h4 class="text-sm font-bold text-gray-900">Learner Information</h4>
                            <p class="text-xs text-gray-400 mt-0.5">Profile and demographic details</p>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                            {{-- Full Name --}}
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500">Full Name</p>
                                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="data?.learner.name"></p>
                                </div>
                            </div>

                            {{-- Username --}}
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500">Username</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="data?.learner.username"></p>
                                </div>
                            </div>

                            {{-- Email --}}
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500">Email Address</p>
                                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="data?.learner.email"></p>
                                </div>
                            </div>

                            {{-- Age & Gender --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-start gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-pink-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-500">Age</p>
                                        <p class="text-sm font-semibold text-gray-900">
                                            <span x-text="data?.learner.age || 'N/A'"></span>
                                            <span x-show="data?.learner.ageBracket" class="text-xs text-gray-500">
                                                (<span x-text="data?.learner.ageBracket"></span>)
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-500">Gender</p>
                                        <p class="text-sm font-semibold text-gray-900 capitalize" x-text="data?.learner.gender"></p>
                                    </div>
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500">Location</p>
                                    <p class="text-sm font-semibold text-gray-900">
                                        <span x-text="data?.learner.barangay"></span>,
                                        <span x-text="data?.learner.city"></span>
                                    </p>
                                </div>
                            </div>

                            {{-- Profile Status --}}
                            <div class="flex items-start gap-3 pt-2 border-t border-gray-200">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                     :class="data?.learner.profileComplete ? 'bg-green-100' : 'bg-red-100'">
                                    <svg class="w-4 h-4" :class="data?.learner.profileComplete ? 'text-green-600' : 'text-red-600'"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="data?.learner.profileComplete" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        <path x-show="!data?.learner.profileComplete" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-gray-500">Profile Status</p>
                                    <p class="text-sm font-semibold" :class="data?.learner.profileComplete ? 'text-green-700' : 'text-red-700'">
                                        <span x-text="data?.learner.profileComplete ? 'Complete' : 'Incomplete'"></span>
                                    </p>
                                </div>
                            </div>

                            {{-- Parent Info (if child account) --}}
                            <div x-show="data?.learner.isChild" class="pt-2 border-t border-gray-200">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-500">Parent/Guardian</p>
                                        <p class="text-sm font-semibold text-gray-900" x-text="data?.learner.parentName || 'Not linked'"></p>
                                        <p class="text-xs text-purple-600 mt-1">✓ Requires parental consent</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: Module Information --}}
                    <div class="space-y-4">
                        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                            <h4 class="text-sm font-bold text-gray-900">Module Requested</h4>
                            <p class="text-xs text-gray-400 mt-0.5">Details about the module for enrollment</p>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                            {{-- Module Thumbnail & Title --}}
                            <div class="flex items-start gap-3">
                                <img :src="data?.module.thumbnailUrl"
                                     :alt="data?.module.title"
                                     class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <h5 class="text-base font-bold text-gray-900 line-clamp-2" x-text="data?.module.title"></h5>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span x-show="data?.module.isPremium"
                                              class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            Premium
                                        </span>
                                        <span class="text-xs text-gray-500" x-text="data?.module.lessonsCount + ' lessons'"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Description</p>
                                <p class="text-sm text-gray-700 leading-relaxed" x-text="data?.module.description"></p>
                            </div>

                            {{-- Age Range --}}
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Age Range</p>
                                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-purple-50 border border-purple-200">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <span class="text-sm font-semibold text-purple-700">
                                        <span x-text="data?.module.minAge"></span>-<span x-text="data?.module.maxAge"></span> years old
                                    </span>
                                </div>
                            </div>

                            {{-- Applied Date --}}
                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-xs font-medium text-gray-500 mb-1">Application Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="data?.appliedAt"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer with Actions --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <button type="button"
                        @click="$store.modals.closeEnrollmentReview()"
                        class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition">
                    Close
                </button>

                <div class="flex items-center gap-3">
                    {{-- Reject Button --}}
                    <button type="button"
                            @click="$store.modals.openRejectModal($store.modals.enrollmentReviewData?.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-700 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Reject
                    </button>

                    {{-- Approve Button --}}
                    <form method="POST" :action="`/instructor/enrollments/${$store.modals.enrollmentReviewData?.id}/approve`">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98] shadow-sm"
                                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Approve Enrollment
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     REJECTION REASON MODAL
══════════════════════════════════════════════════════════ --}}
<div x-show="$store.modals.rejectModal"
     x-cloak
     @keydown.escape.window="$store.modals.closeRejectModal()"
     class="fixed inset-0 z-[60] overflow-y-auto"
     style="display: none;">

    {{-- Backdrop --}}
    <div x-show="$store.modals.rejectModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="$store.modals.closeRejectModal()"
         class="fixed inset-0 bg-gray-900/70 backdrop-blur-sm"></div>

    {{-- Modal Container --}}
    <div class="flex min-h-screen items-center justify-center p-4">
        <div x-show="$store.modals.rejectModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @click.stop
             class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl">

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">Reject Enrollment</h3>
                <p class="text-xs text-gray-500 mt-1">Please provide a reason for rejection</p>
            </div>

            {{-- Form --}}
            <form method="POST" :action="`/instructor/enrollments/${$store.modals.rejectEnrollmentId}/reject`">
                @csrf
                @method('PATCH')

                <div class="p-6 space-y-4">
                    {{-- Rejection Reason Dropdown --}}
                    <div>
                        <label for="rejection_reason_code" class="block text-sm font-semibold text-gray-700 mb-2">
                            Reason <span class="text-red-500">*</span>
                        </label>
                        <select id="rejection_reason_code"
                                name="rejection_reason_code"
                                x-model="$store.modals.rejectReason"
                                required
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm">
                            <option value="">Select a reason...</option>
                            <template x-for="reason in $store.modals.rejectReasons" :key="reason.value">
                                <option :value="reason.value" x-text="reason.label"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Optional Notes --}}
                    <div>
                        <label for="rejection_reason_note" class="block text-sm font-semibold text-gray-700 mb-2">
                            Additional Notes <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea id="rejection_reason_note"
                                  name="rejection_reason_note"
                                  x-model="$store.modals.rejectNote"
                                  rows="3"
                                  maxlength="1000"
                                  placeholder="Provide additional context or guidance for the learner..."
                                  class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"></textarea>
                        <p class="text-xs text-gray-400 mt-1">
                            <span x-text="($store.modals.rejectNote || '').length"></span>/1000 characters
                        </p>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end gap-3">
                    <button type="button"
                            @click="$store.modals.closeRejectModal()"
                            class="px-4 py-2 text-sm font-semibold text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition active:scale-[0.98] shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

---

### Step 5: Add x-cloak CSS to prevent flash

**File:** `resources/css/app.css` (if not already present)

```css
[x-cloak] {
    display: none !important;
}
```

Run: `npm run dev` to compile

---

### Step 6: Visual verification

**Manual check:**
1. Navigate to `/instructor/enrollments`
2. Find an enrollment card
3. Click "Review Information" button
4. **Verify modal opens with:**
   - Purple gradient header
   - Learner profile information (name, email, age, gender, location)
   - Parent info displays if child account
   - Profile completion status
   - Module thumbnail, title, description
   - Age range badge
   - Age appropriateness warning (if applicable)
5. **Test actions:**
   - Click "Approve" → Should submit form and approve enrollment
   - Click "Reject" → Should open rejection reason modal
   - Select rejection reason → Add notes → Submit → Should reject
   - Click "Close" or click outside modal → Should close
6. **Test responsiveness:**
   - Desktop: Two-column layout
   - Mobile: Stacked single-column layout

---

### Step 7: Functional verification

**Test workflows:**

1. **Age-appropriate learner:**
   - Open review modal for learner within module age range
   - Verify NO warning banner displays
   - Approve → Verify enrollment updates to "approved"

2. **Age-inappropriate learner:**
   - Open review modal for learner outside module age range
   - Verify orange warning banner displays
   - Still able to approve (instructor discretion)

3. **Child account:**
   - Open review modal for learner with `requires_parental_consent = true`
   - Verify parent name displays
   - Verify "Requires parental consent" badge shows

4. **Incomplete profile:**
   - Open review modal for learner with incomplete profile
   - Verify profile status shows "Incomplete" in red

5. **Rejection flow:**
   - Click "Reject" button
   - Review modal closes, rejection modal opens
   - Select reason → No notes → Submit → Verify rejection saves
   - Try rejecting with "Other" reason without notes → Should still work

6. **Multiple modals:**
   - Open review modal → Click reject → Rejection modal opens (z-index correct)
   - Close rejection modal → Review modal should still be closed

---

### Step 8: Error handling verification

**Test edge cases:**

1. **Missing data:**
   - Open review modal with missing optional fields (no parent, no age)
   - Verify "N/A" or fallback displays correctly

2. **Long text:**
   - Module with very long title (50+ chars)
   - Verify truncation or line-clamp works

3. **Console errors:**
   - Open browser console
   - Interact with modals
   - Verify no JavaScript errors

4. **Network errors:**
   - Open review modal → Click approve
   - If network fails, verify appropriate error handling

---

### Step 9: Commit

```bash
git add resources/views/instructor/enrollments/index.blade.php resources/js/app.js
git commit -m "feat(instructor): add learner review modal to enrollments index

Add comprehensive review information modal on enrollments index page:

Features:
- Quick review of learner profile without leaving page
- Display appropriate learner information (profile, demographics)
- Show complete module details with thumbnail
- Age appropriateness warning if outside module range
- Parent/guardian info for child accounts
- Profile completion status indicator
- Inline approve/reject actions

Rejection flow:
- Integrated rejection reason modal
- Predefined rejection reasons (prerequisite, age, profile, capacity, other)
- Optional additional notes (1000 char limit)
- Form validation with error display

UI/UX:
- Purple gradient brand theme
- Smooth Alpine.js transitions
- Responsive two-column layout (stacks on mobile)
- Proper z-index layering for nested modals
- Click-outside and Escape key to close

Backend: Reuses existing controller methods and rejection system

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Execution Options

Plan complete and saved to `docs/plans/2026-03-20-instructor-panel-ui-enhancement-plan.md`.

**Two execution options:**

### 1. Subagent-Driven (this session)
I dispatch fresh subagent per task, review between tasks, fast iteration. Requires **superpowers:subagent-driven-development** skill.

### 2. Parallel Session (separate)
Open new session with executing-plans skill, batch execution with checkpoints. Allows you to work on other things while implementation runs.

**Which approach would you prefer?**
