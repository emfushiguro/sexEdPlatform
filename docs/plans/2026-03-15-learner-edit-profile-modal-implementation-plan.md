# Learner Edit Profile Modal — Implementation Plan

**Date:** 2026-03-15
**Design doc:** `docs/plans/2026-03-15-learner-edit-profile-modal-design.md`
**Branch:** `feat/admin-panel-integration`

---

## Files To Touch

| # | File | Action |
|---|---|---|
| 1 | `database/migrations/TIMESTAMP_add_archived_to_users_status_enum.php` | Create — add `archived` to `status` enum |
| 2 | `resources/js/app.js` | Edit — add `editProfile` to `$store.modals` |
| 3 | `app/Http/Controllers/Learner/ProfileCompletionController.php` | Edit — JSON responses + archived status |
| 4 | `app/Http/Controllers/Learner/DashboardController.php` | Edit — pass subscription+cooldown data |
| 5 | `resources/views/components/learner/gamification-panel.blade.php` | Edit — change link to modal trigger button |
| 6 | `resources/views/learner/partials/edit-profile-modal.blade.php` | Create — full modal component |
| 7 | `resources/views/learner/dashboard.blade.php` | Edit — include modal partial |
| 8 | `resources/views/profile/learner-edit.blade.php` | Edit — redirect to dashboard |

---

## Step-by-Step Implementation

---

### Step 1 — Migration: Add `archived` to `users.status` enum

Create migration file. The `users.status` column is currently `enum('active','inactive','suspended')`.
Add `archived` as a new valid value.

```php
Schema::table('users', function (Blueprint $table) {
    DB::statement("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended','archived') NOT NULL DEFAULT 'active'");
});
```

Also add `archived` to `User::$fillable` and update any query scopes that filter by status.

---

### Step 2 — `resources/js/app.js`

Add `editProfile` state and open/close helpers to `$store.modals`:

```js
Alpine.store('modals', {
    // ...existing...
    editProfile: false,

    openEditProfile() { this.editProfile = true; },
    closeEditProfile() { this.editProfile = false; },
});
```

---

### Step 3 — `ProfileCompletionController.php` — JSON response branches

**`update()` method:**
- Replace `return back()->with('success', ...)` with:
  ```php
  if ($request->expectsJson()) {
      return response()->json([
          'success' => true,
          'message' => 'Profile updated successfully!',
          'data' => [
              'username'   => $learnerProfile->fresh()->username,
              'about'      => $learnerProfile->fresh()->about,
              'avatar_url' => $learnerProfile->fresh()->avatar_path
                  ? asset('storage/' . $learnerProfile->fresh()->avatar_path) : null,
          ],
      ]);
  }
  return back()->with('success', 'Profile updated successfully!');
  ```
- For validation errors on AJAX, Laravel already returns `422 + JSON errors` automatically — no extra code needed.
- For the username cooldown error, replace the `back()->with('error', ...)` with:
  ```php
  if ($request->expectsJson()) {
      return response()->json(['success' => false, 'errors' => ['username' => [$message]]], 422);
  }
  return back()->with('error', $message);
  ```

**`updatePassword()` method:**
- Replace `return back()->with('success', ...)` with JSON branch (same pattern).

**`deleteAccount()` method:**
- Before `$user->delete()`, add:
  ```php
  $user->status = 'archived';
  $user->save();
  ```
- Replace `return redirect()->route('home')->with(...)` with:
  ```php
  if ($request->expectsJson()) {
      return response()->json(['success' => true, 'redirect' => route('home')]);
  }
  return redirect()->route('home')->with('success', 'Your account has been archived.');
  ```
- Keep avatar deletion (already present, leave unchanged).

---

### Step 4 — `DashboardController.php` — Subscription + cooldown data

Add to `index()` before the `return view(...)` call:

```php
// ── Profile modal data ──────────────────────────────────────────
$currentSubscription = $user->subscriptions()
    ->whereIn('status', ['active', 'trialing'])
    ->latest()
    ->first();

$currentPlan = $currentSubscription && $currentSubscription->plan_id
    ? \App\Models\SubscriptionPlan::find($currentSubscription->plan_id)
    : null;

$usernameCooldownDays = 0;
if (!$user->isPremium() && $learnerProfile->username_changed_at) {
    $daysSince = now()->diffInDays($learnerProfile->username_changed_at);
    $usernameCooldownDays = $daysSince < 7 ? (7 - (int) $daysSince) : 0;
}
```

Add `$currentSubscription`, `$currentPlan`, `$usernameCooldownDays` to `compact(...)`.

---

### Step 5 — `gamification-panel.blade.php` — Modal trigger

Change the existing "Edit Profile" `<a>` link (line ~78-82) to a button:

```blade
{{-- Edit Profile button --}}
<button
    @click="$store.modals.openEditProfile()"
    class="block w-full text-center text-sm font-semibold text-white py-2 rounded-xl mb-4 transition-opacity hover:opacity-90"
    style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);"
>
    Edit Profile
</button>
```

---

### Step 6 — Create `resources/views/learner/partials/edit-profile-modal.blade.php`

Full modal Alpine component. Structure:

```
x-data="editProfileModal()" on root div
x-show="$store.modals.editProfile"
x-cloak

Modal backdrop (fixed inset-0 bg-black/50 z-50)
Modal panel (max-w-2xl w-full mx-auto my-10 bg-white rounded-2xl shadow-xl)

  ┌─ Brand gradient top bar (h-1.5) ─────┐
  │ Header row: title + close button      │
  │ Tab bar: [Profile][Password][Sub][Danger] │
  │ Tab content area ─────────────────────│
  │   Tab: profile                        │
  │     - avatar upload + preview         │
  │     - username (cooldown-aware)       │
  │     - bio textarea + char counter     │
  │     - success/error banners           │
  │     - Save button                     │
  │   Tab: password                       │
  │     - current password                │
  │     - new password + strength bar     │
  │     - confirm password                │
  │     - Save button                     │
  │   Tab: subscription                   │
  │     - plan badge + renewal date       │
  │     - manage button                   │
  │   Tab: danger                         │
  │     - archive action (2-state)        │
  │     - [idle] "Archive Account" button │
  │     - [confirming] password + submit  │
  └───────────────────────────────────────┘
```

**Alpine component function `editProfileModal()`:**

```js
function editProfileModal() {
    return {
        activeTab: 'profile',

        // Profile tab state
        profileLoading: false,
        profileSuccess: null,
        profileErrors: {},
        avatarPreview: null,        // ObjectURL for previewing locally
        avatarFile: null,           // File object
        bio: '',                    // initialized from @js($learnerProfile->about)
        username: '',               // initialized from @js($learnerProfile->username)
        bioLength: 0,

        // Password tab state
        passwordLoading: false,
        passwordSuccess: null,
        passwordErrors: {},
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',

        // Danger zone state
        confirmDelete: false,
        deletePassword: '',
        deleteLoading: false,
        deleteError: null,

        init() {
            this.bio = this.$el.dataset.bio || '';
            this.username = this.$el.dataset.username || '';
            this.bioLength = this.bio.length;
        },

        // Profile tab
        selectAvatar() { this.$refs.avatarInput.click(); },
        onAvatarChange(e) {
            const file = e.target.files[0];
            if (!file) return;
            this.avatarFile = file;
            this.avatarPreview = URL.createObjectURL(file);
        },
        passwordStrength() {
            const p = this.newPassword;
            if (!p) return 0;
            let score = 0;
            if (p.length >= 8) score++;
            if (/[A-Z]/.test(p)) score++;
            if (/[0-9]/.test(p)) score++;
            if (/[@$!%*?&#]/.test(p)) score++;
            return score; // 0-4
        },

        async saveProfile() {
            this.profileLoading = true;
            this.profileSuccess = null;
            this.profileErrors = {};

            const fd = new FormData();
            fd.append('_method', 'PUT');
            fd.append('username', this.username);
            fd.append('about', this.bio);
            if (this.avatarFile) fd.append('avatar', this.avatarFile);
            fd.append('_token', document.querySelector('meta[name=csrf-token]').content);

            try {
                const res = await fetch('{{ route("profile.learner.update") }}', {
                    method: 'POST',   // FormData can't do PUT directly
                    headers: { 'Accept': 'application/json', 'X-HTTP-Method-Override': 'PUT' },
                    body: fd,
                });
                // Note: use POST + X-HTTP-Method-Override because FormData doesn't support PUT natively
                // Actually, send _method=PUT in form data (Laravel's method spoofing)
                const data = await res.json();
                if (res.ok && data.success) {
                    this.profileSuccess = data.message;
                    if (data.data?.avatar_url) this.avatarPreview = data.data.avatar_url;
                } else {
                    this.profileErrors = data.errors ?? {};
                }
            } catch {
                this.profileErrors = { general: ['Something went wrong. Please try again.'] };
            } finally {
                this.profileLoading = false;
            }
        },

        async savePassword() {
            this.passwordLoading = true;
            this.passwordSuccess = null;
            this.passwordErrors = {};
            try {
                const res = await fetch('{{ route("profile.password.update") }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        current_password: this.currentPassword,
                        password: this.newPassword,
                        password_confirmation: this.confirmPassword,
                    }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.passwordSuccess = data.message;
                    this.currentPassword = '';
                    this.newPassword = '';
                    this.confirmPassword = '';
                } else {
                    this.passwordErrors = data.errors ?? {};
                }
            } catch {
                this.passwordErrors = { general: ['Something went wrong.'] };
            } finally {
                this.passwordLoading = false;
            }
        },

        async archiveAccount() {
            this.deleteLoading = true;
            this.deleteError = null;
            try {
                const res = await fetch('{{ route("profile.account.delete") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ password: this.deletePassword }),
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.deleteError = data.errors?.password?.[0] ?? 'Incorrect password.';
                }
            } catch {
                this.deleteError = 'Something went wrong.';
            } finally {
                this.deleteLoading = false;
            }
        },
    };
}
```

**Profile tab → POST with `_method=PUT`** (Laravel method spoofing in FormData):
- `fd.append('_method', 'PUT')` + `method: 'POST'` on fetch

**Subscription tab data passed from controller** via PHP variables:
- `$currentPlan`, `$currentSubscription` — rendered inline in Blade

---

### Step 7 — `dashboard.blade.php`

At the bottom of `@section('content')`, before `@endsection`:
```blade
@include('learner.partials.edit-profile-modal', [
    'learnerProfile'       => $learnerProfile,
    'currentSubscription'  => $currentSubscription,
    'currentPlan'          => $currentPlan,
    'usernameCooldownDays' => $usernameCooldownDays,
    'isPremium'            => Auth::user()->isPremium(),
])
```

---

### Step 8 — `profile/learner-edit.blade.php`

Replace the full page content with a simple redirect controller call — or at minimum add a redirect at the top of the `edit()` method in the controller (simplest approach):

In `ProfileCompletionController::edit()`:
```php
// Learner profile is now managed via modal on the dashboard
return redirect()->route('learner.dashboard')
    ->with('info', 'Please use the Edit Profile button on your dashboard.');
```

---

## Notes

- **FormData + PUT:** Use `fd.append('_method', 'PUT')` + `fetch({ method: 'POST' })` — Laravel's method spoofing works for AJAX too when using `Accept: application/json`.
- **Validation errors:** Laravel's `422` response with `errors` JSON is returned automatically when `$request->expectsJson()` is true — no extra code for field-level errors.
- **`username_changed_at`:** Already a Carbon timestamp on `LearnerProfile` — `diffInDays` works directly.
- **Avatar old file cleanup:** Already handled in `update()` — leave that logic intact.
- **Migration:** Requires running `php artisan migrate` — no data is lost, only enum extended.
