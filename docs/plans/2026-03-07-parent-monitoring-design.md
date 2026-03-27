# Parent Monitoring Feature — Design Document

**Date:** 2026-03-07  
**Feature:** Parent Monitoring Dashboard (Progress, Quiz Results, Achievements, Content Approval)  
**Status:** Approved — ready for implementation planning

---

## 1. Overview

Parents who have registered a child account can monitor their child's learning activity through a dedicated monitoring section accessible from the learner sidebar. The feature covers four areas: module progress, quiz results, achievements, and content approval (parental gating of module enrollment for children under 13).

---

## 2. Scope

### Included
- Progress tab — module enrollment progress per child
- Quiz Results tab — quiz attempt history per child
- Achievements tab — XP, level, streak, reward log per child
- Content Approval tab — approve/reject pending module enrollment requests (conditionally shown)
- "My Children" sidebar nav item (visible only to parents)
- Migration to add `pending_parent_approval` status to `module_enrollments`

### Excluded
- Activity Logs (removed — no `ActivityLog` UI will be built in this feature)
- Admin-level oversight of parent-child relationships
- Email notifications for approval requests (future enhancement)

---

## 3. Architecture

### New Files

| File | Purpose |
|---|---|
| `app/Services/ParentChildService.php` | All data-fetching logic for monitoring tabs |
| `app/Http/Controllers/ParentController.php` | Thin controller — calls service, returns views |
| `app/Policies/ParentChildPolicy.php` | Authorizes parent access to their own children only |
| `resources/views/parent/children/show.blade.php` | Tabbed child detail page |
| `database/migrations/..._add_pending_parent_approval_to_module_enrollments.php` | Extends status enum |
| `tests/Feature/ParentChildMonitoringTest.php` | Feature test coverage |

### Modified Files

| File | Change |
|---|---|
| `resources/views/layouts/learner-sidebar.blade.php` | Add conditional "My Children" nav item |
| `routes/auth.php` | Add parent monitoring routes |
| `app/Providers/AppServiceProvider.php` | Register `ParentChildPolicy` |

---

## 4. Routes

All routes are inside `middleware(['auth', 'verified'])`:

```
GET  /parent/children                                          → ParentRegistrationController@childrenIndex  (existing)
GET  /parent/children/{child}                                  → ParentController@show
POST /parent/children/{child}/enrollments/{enrollment}/approve → ParentController@approveEnrollment
POST /parent/children/{child}/enrollments/{enrollment}/reject  → ParentController@rejectEnrollment
```

Route names:
- `parent.children.show`
- `parent.children.enrollments.approve`
- `parent.children.enrollments.reject`

---

## 5. Authorization

`ParentChildPolicy` — registered for `User` model, guards `ParentController`:

- **`view(User $parent, User $child)`** — passes if `parent_child_accounts` has a row where `parent_user_id = $parent->id` AND `child_user_id = $child->id`
- **`approveEnrollment / rejectEnrollment`** — same check + enrollment must belong to `$child` + status must be `pending_parent_approval`

Failure → 403 (not redirect, to prevent enumeration).

---

## 6. Service Layer — `ParentChildService`

Four public methods, all receive a verified `User $child`:

### `getProgress(User $child): Collection`
Returns all `ModuleEnrollment` records with `status = approved`, eager-loaded with `module` and computed progress from `UserProgress` (completed lesson count / total lesson count × 100).

### `getQuizResults(User $child): Collection`
Returns all `QuizAttempt` records, eager-loaded with `quiz.module`, ordered by `created_at DESC`.

### `getAchievements(User $child): array`
Returns:
- `gamification` → `UserGamification` record (level, score, streak)
- `rewardLogs` → `RewardLog` records ordered by `created_at DESC`

### `getPendingEnrollments(User $child): Collection`
Returns `ModuleEnrollment` records where `status = pending_parent_approval`, eager-loaded with `module`.

---

## 7. Controller — `ParentController`

```php
// show() — loads all 4 data sets, passes to view
public function show(User $child)
{
    $this->authorize('view', $child);
    $data = [
        'progress'           => $this->service->getProgress($child),
        'quizResults'        => $this->service->getQuizResults($child),
        'achievements'       => $this->service->getAchievements($child),
        'pendingEnrollments' => $this->service->getPendingEnrollments($child),
        'canApproveContent'  => auth()->user()->children()
                                    ->where('child_user_id', $child->id)
                                    ->first()?->pivot->can_approve_content ?? false,
    ];
    return view('parent.children.show', array_merge(['child' => $child], $data));
}
```

`approveEnrollment` and `rejectEnrollment` authorize via policy, validate status is `pending_parent_approval`, then:
- **Approve:** if module is `auto` enrollment → set to `approved`; if `manual` → set to `pending` (goes to instructor queue)  
- **Reject:** set to `rejected`

---

## 8. Database — Migration

Extend `module_enrollments.status` enum to include `pending_parent_approval`:

```
status: ['pending', 'approved', 'rejected', 'pending_parent_approval']
```

Enrollment flow for children requiring parental consent (`requires_parental_consent = true`) when `can_approve_content = true` on the relationship:

```
Child clicks Enroll
       ↓
status = pending_parent_approval
       ↓
Parent visits Content Approval tab
       ↓
     Approve                    Reject
       ↓                           ↓
module.enrollment_mode?        status = rejected
  auto → approved
  manual → pending (instructor reviews)
```

No change to the enrollment flow for children where `can_approve_content = false` — they enroll normally.

---

## 9. UI — View Design

### Sidebar (`learner-sidebar.blade.php`)
Add after Certificates nav item, conditional on `Auth::user()->isParent()`:

```php
@if(Auth::user()->isParent())
    // "My Children" nav item → route('parent.children.index')
    // Active when: request()->routeIs('parent.children.*')
@endif
```

### Child Detail Page (`parent/children/show.blade.php`)
Extends `layouts.learner-app`.

Layout:
```
← Back to My Children
[Avatar initials] {child.name}  •  {age} years old  •  {age bracket badge}

[Progress] [Quiz Results] [Achievements] [Content Approval*]
(*only if can_approve_content = true)

─── Tab Content ───
```

**Progress tab:**
- One card per enrolled module
- Module title, thumbnail (if any), progress bar (%), "X of Y lessons complete", last accessed date
- Empty state: "No modules enrolled yet"

**Quiz Results tab:**
- Table: Quiz Name | Module | Score | Pass/Fail | Date
- Pass = green badge, Fail = red badge
- Empty state: "No quizzes taken yet"

**Achievements tab:**
- Summary row: Level {N} • {XP} XP • {streak} day streak
- List of reward log entries: icon, title, date earned
- Empty state for rewards: "No rewards earned yet" (summary row always shows)

**Content Approval tab** *(only rendered if `can_approve_content = true`)*:
- List of pending enrollment requests: module title, age bracket, requested date
- Approve / Reject buttons (POST forms with CSRF)
- Empty state: "No pending enrollment requests"

Tab switching via Alpine.js:
```html
<div x-data="{ tab: 'progress' }">
  <!-- Tab buttons -->
  <!-- Tab panels: x-show="tab === 'progress'" etc. -->
</div>
```

---

## 10. Testing

**`tests/Feature/ParentChildMonitoringTest.php`**

| Test | Assertion |
|---|---|
| Parent can view their own child's detail page | 200 response |
| Parent cannot view a child that isn't theirs | 403 response |
| Non-parent authenticated user cannot access parent routes | Redirect or 403 |
| Guest cannot access parent routes | Redirect to login |
| Parent can approve a `pending_parent_approval` enrollment (auto module) | Status becomes `approved` |
| Parent can approve a `pending_parent_approval` enrollment (manual module) | Status becomes `pending` |
| Parent can reject a `pending_parent_approval` enrollment | Status becomes `rejected` |
| Content Approval tab data excluded when `can_approve_content = false` | `pendingEnrollments` empty / tab not rendered |
| Cannot approve enrollment that belongs to another child | 403 |

---

## 11. What Is NOT In This Feature

- Email/push notifications to parent when child requests enrollment
- Parent ability to remove a child account
- Real-time progress updates (polling, websockets)
- Admin oversight of parent-child relationships
- Activity logs tab
