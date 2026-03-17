# Learner Edit Profile Modal — Design Document

**Date:** 2026-03-15
**Status:** Approved
**Branch:** `feat/admin-panel-integration`

---

## Problem Statement

The current learner profile edit experience lives on a separate page (`/profile/learner/edit`) using the legacy `x-app-layout` layout and blue Breeze buttons. It is inconsistent with the established learner design system (brand gradient, `layouts.learner-app`, rounded-2xl tokens) and requires a full navigation away from the dashboard.

---

## Goals

1. Replace the separate edit profile page with a modal accessible from the learner dashboard.
2. Align the UI with the learner design system (brand gradient, `rounded-2xl`, section tokens).
3. Use AJAX (`fetch`) so all updates happen in-place without page reloads.
4. Simplify the profile completion section to: **username**, **bio**, and **avatar**.
5. Implement soft-delete (archive) for account deletion — no hard deletes.

---

## Architecture Overview

### Trigger
A clickable avatar + "Edit Profile" button in the learner dashboard hero/greeting area. Opens via `$store.modals.editProfile = true`.

### Modal Shell
- **Type:** Centered overlay, not slide-over
- **Size:** `max-w-2xl w-full` — large enough for forms, not full-screen
- **Style:** `rounded-2xl bg-white shadow-xl` with brand gradient accent bar at top
- **Managed by:** `$store.modals.editProfile` boolean (added to `resources/js/app.js`)
- **Tab state:** Local Alpine `x-data="{ activeTab: 'profile', ... }"` on modal root

### Form Submission Pattern
All forms use `fetch()` with appropriate headers:
```js
fetch(url, {
    method: 'PUT' | 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
        'Accept': 'application/json',
        // For JSON bodies:
        'Content-Type': 'application/json',
        // For file upload use FormData (no Content-Type header)
    },
    body: formData | JSON.stringify(data),
})
```

Controllers detect `$request->expectsJson()` and return structured JSON responses:
- Success: `{ success: true, message: 'string', data?: {} }`
- Validation error: `{ success: false, errors: { field: ['message'] } }`
- Redirect (after delete): `{ redirect: '/' }`

---

## Tab Specifications

### Tab 1 — Profile

**Fields:**
| Field | Source | Notes |
|---|---|---|
| Avatar | `learnerProfile->avatar_path` | Circular preview, click-to-upload, max 2 MB, jpeg/png/jpg |
| Username | `learnerProfile->username` | Cooldown-aware (see below) |
| Bio | `learnerProfile->about` | `<textarea>` max 255 chars, live char counter |

**Avatar upload:**
- Clicking the circular avatar opens a hidden `<input type="file">` via `$refs.avatarInput.click()`
- On file selection: preview the image locally via `URL.createObjectURL()`
- Submitted as `FormData` (multipart) via `PUT /profile/learner`

**Username cooldown (free users):**
- If `username_changed_at` is within 7 days: field is `disabled`, amber chip shows "Available in X days"
- Premium users: no cooldown (field always enabled)
- On successful save: update the displayed username in the dashboard hero

**Submit:** `PUT /profile/learner` — existing route, controller updated to return JSON

---

### Tab 2 — Password

**Fields:**
| Field | Validation |
|---|---|
| Current password | required |
| New password | required, regex (uppercase + lowercase + digit + special char) |
| Confirm password | must match new password |

**UX details:**
- Simple password strength bar (weak/medium/strong based on length + complexity)
- All three fields must be filled to enable the submit button
- `PUT /profile/password` — existing route, controller updated to return JSON

---

### Tab 3 — Subscription

**Display only (no editable fields):**
- Current plan badge: "Free" (gray) or "Premium" (brand gradient)
- Renewal date (if premium active subscription)
- Parent account seat info (if `learnerProfile->is_parent_account`)

**Actions:**
- "Manage Subscription" button — links to existing subscription management page (no modal needed)
- No backend changes required for this tab

---

### Tab 4 — Danger Zone

**Account archiving flow:**
1. User clicks "Archive Account" (red outlined button)
2. Alpine switches to `confirmDelete: true` state (inline confirmation, no second modal)
3. Confirmation state shows: warning text + current password input + "Yes, archive my account" button
4. On submit: `DELETE /profile/account` via fetch
5. Controller: sets `$user->status = 'archived'` then calls `$user->delete()` (soft delete, sets `deleted_at`)
6. Controller logs user out, returns `{ redirect: '/' }`
7. Alpine catches redirect: `window.location.href = data.redirect`

**Why "archived" not "deleted":**
The `User` model already has the `SoftDeletes` trait. Setting `status = 'archived'` before soft-deleting makes archived accounts easy to filter in admin panels without relying solely on `deleted_at`. Hard delete is not implemented — admins can restore accounts.

---

## Files Modified

| File | Change |
|---|---|
| `resources/views/learner/dashboard.blade.php` | Add modal trigger (avatar area) + edit profile modal Alpine component |
| `resources/js/app.js` | Add `editProfile: false` to `$store.modals` |
| `app/Http/Controllers/Learner/ProfileCompletionController.php` | Add JSON response branches to `update()`, `updatePassword()`, `deleteAccount()`; set `status='archived'` in `deleteAccount()` |
| `resources/views/profile/learner-edit.blade.php` | Add redirect to dashboard (keep as fallback) |

---

## Design System Tokens Used

| Element | Token |
|---|---|
| Modal background | `bg-white rounded-2xl shadow-xl` |
| Active tab | Brand gradient text/border |
| Inactive tab | `text-gray-500 hover:text-gray-700` |
| Section card bg | `bg-purple-50/40 rounded-2xl p-5 border border-purple-100/60` |
| Primary button | Brand gradient + `hover:opacity-90 hover:scale-[1.02]` |
| Danger button | `border-2 border-red-300 text-red-600 hover:bg-red-50` |
| Success banner | `bg-green-50 border border-green-200 text-green-700 rounded-xl` |
| Error text | `text-red-500 text-xs mt-1` |

---

## Out of Scope

- Changing email address (requires email verification flow — separate feature)
- Editing age range, gender, city/barangay (set during profile completion, not re-editable)
- The `profile.edit` Breeze route (left untouched — not used by learners)
