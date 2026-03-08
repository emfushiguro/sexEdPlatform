# Organizations & Seminars — Backend Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Wire up the existing Organization and Seminar UI designs to real database-backed controllers, replacing all hardcoded placeholder data with live queries and making every admin form fully functional.

**Architecture:** Create `Admin\OrganizationAdminController` and `Admin\SeminarAdminController` following the same patterns as `Admin\UserAdminController` (paginated index, search/filter, CRUD). Align the `Organization` model with its migration, add missing columns to `seminars` to match the designed create form, update the four existing admin views to accept model data, and add the three missing views (`organizations/create`, `organizations/edit`, `seminars/edit`). Update admin routes to use the new controllers.

**Tech Stack:** Laravel 12, Eloquent ORM, Blade, Tailwind CSS (existing semantic tokens), Alpine.js 3.

---

## File Inventory — What Already Exists

### Models
| File | Status |
|------|--------|
| `app/Models/Organization.php` | Exists — has `address` + `verified` in fillable but migration only has `location`; needs fix |
| `app/Models/Seminar.php` | Exists — OK |
| `app/Models/SeminarRegistrant.php` | Exists — OK |

### Migrations
| File | Status |
|------|--------|
| `database/migrations/2026_01_06_085815_create_organizations_table.php` | Exists — missing `verified` column, has `location` not `address` |
| `database/migrations/2026_01_06_085835_create_seminars_table.php` | Exists — missing `capacity`, `presenter`, `type` columns |
| `database/migrations/2026_01_06_085836_create_seminar_organizations_table.php` | Exists — OK |
| `database/migrations/2026_01_06_085837_create_seminar_registrants_table.php` | Exists — OK |

### Controllers
| File | Status |
|------|--------|
| `app/Http/Controllers/Admin/OrganizationAdminController.php` | **Missing — must create** |
| `app/Http/Controllers/Admin/SeminarAdminController.php` | **Missing — must create** |

### Views
| File | Status |
|------|--------|
| `resources/views/admin/organizations/index.blade.php` | Exists — hardcoded data |
| `resources/views/admin/organizations/show.blade.php` | Exists — hardcoded data |
| `resources/views/admin/organizations/create.blade.php` | **Missing — must create** |
| `resources/views/admin/organizations/edit.blade.php` | **Missing — must create** |
| `resources/views/admin/seminars/index.blade.php` | Exists — hardcoded data |
| `resources/views/admin/seminars/create.blade.php` | Exists — form has `action="#"` stub |
| `resources/views/admin/seminars/show.blade.php` | Exists — hardcoded data |
| `resources/views/admin/seminars/edit.blade.php` | **Missing — must create** |

### Routes (`routes/admin.php`)
Organizations and Seminars routes currently use closures returning static views. Must be replaced with controller references.

---

## Design Tokens Reference (from Admin panel)

```
/* Cards */   rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 p-5 shadow-theme-xs
/* Tables */  rounded-2xl overflow-hidden shadow-theme-xs — header bg-gray-50 dark:bg-white/[0.02]
/* Inputs */  px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500
/* Buttons */ px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors
/* Badges */  inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
/* Stats */   w-10 h-10 rounded-xl bg-{color}-50 dark:bg-{color}-500/10 + text-{color}-500
```

---

## Task 1: Fix Organization Model (align with migration)

**Why:** The `Organization` model declares `address` and `verified` in `$fillable`, but the migration creates a `location` column (not `address`) and has no `verified` column. This mismatch would cause silent failures on save.

### Files
- **Modify:** `app/Models/Organization.php`

### Step 1.1 — Update `$fillable` and cast

In `app/Models/Organization.php`, replace `address` with `location` in `$fillable` (the DB column is `location`):

```php
protected $fillable = [
    'user_id',
    'name',
    'contact_info',
    'description',
    'location',
    'verified',
];
```

Keep `verified` in `$fillable` — it will be added by the migration in Task 2.

### Step 1.2 — Commit

```bash
git add app/Models/Organization.php
git commit -m "fix: align Organization model fillable with database schema"
```

---

## Task 2: Database Migrations for Missing Columns

**Why:** The `organizations` table lacks a `verified` boolean, and the `seminars` table lacks `capacity`, `presenter`, and `type` fields that are present in the designed Create Seminar form.

### Files
- **Create:** `database/migrations/2026_03_08_000001_add_verified_to_organizations_table.php`
- **Create:** `database/migrations/2026_03_08_000002_add_extra_fields_to_seminars_table.php`

### Step 2.1 — Migration: add `verified` to organizations

Create `database/migrations/2026_03_08_000001_add_verified_to_organizations_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('verified')->default(false)->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('verified');
        });
    }
};
```

### Step 2.2 — Migration: add `capacity`, `presenter`, `type` to seminars

Create `database/migrations/2026_03_08_000002_add_extra_fields_to_seminars_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seminars', function (Blueprint $table) {
            $table->string('presenter')->nullable()->after('location');
            $table->unsignedInteger('capacity')->nullable()->after('presenter');
            $table->enum('type', ['online', 'in_person', 'hybrid'])->default('online')->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('seminars', function (Blueprint $table) {
            $table->dropColumn(['presenter', 'capacity', 'type']);
        });
    }
};
```

### Step 2.3 — Update Seminar model fillable and casts

In `app/Models/Seminar.php`, add the new fields to `$fillable`:

```php
protected $fillable = [
    'title',
    'description',
    'location',
    'schedule',
    'is_premium',
    'presenter',
    'capacity',
    'type',
];
```

### Step 2.4 — Run migrations

```bash
php artisan migrate
```

Expected: migrations run without errors.

### Step 2.5 — Commit

```bash
git add database/migrations/ app/Models/Seminar.php
git commit -m "feat: add verified to organizations, add capacity/presenter/type to seminars"
```

---

## Task 3: OrganizationAdminController

**Why:** The admin routes for organizations currently use anonymous closures that return static views. A proper controller provides CRUD actions with real DB queries, search/filter, and verification toggling.

### Files
- **Create:** `app/Http/Controllers/Admin/OrganizationAdminController.php`

### Step 3.1 — Create the controller

Create `app/Http/Controllers/Admin/OrganizationAdminController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;

class OrganizationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::with('user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_info', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('verified')) {
            $query->where('verified', $request->verified === '1');
        }

        $organizations = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total'    => Organization::count(),
            'verified' => Organization::verified()->count(),
            'pending'  => Organization::where('verified', false)->count(),
            'with_seminars' => Organization::has('seminars')->count(),
        ];

        return view('admin.organizations.index', compact('organizations', 'stats'));
    }

    public function create()
    {
        $users = User::where('role', 'organization')->get();
        return view('admin.organizations.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'contact_info' => 'nullable|string|max:255',
            'location'     => 'nullable|string|max:255',
            'verified'     => 'boolean',
        ]);

        $validated['verified'] = $request->boolean('verified');

        Organization::create($validated);

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['user', 'seminars']);
        return view('admin.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization)
    {
        $users = User::where('role', 'organization')->get();
        return view('admin.organizations.edit', compact('organization', 'users'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'user_id'      => 'nullable|exists:users,id',
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'contact_info' => 'nullable|string|max:255',
            'location'     => 'nullable|string|max:255',
            'verified'     => 'boolean',
        ]);

        $validated['verified'] = $request->boolean('verified');

        $organization->update($validated);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization deleted.');
    }

    public function toggleVerified(Organization $organization)
    {
        $organization->update(['verified' => !$organization->verified]);

        $status = $organization->verified ? 'verified' : 'unverified';
        return back()->with('success', "Organization {$status} successfully.");
    }
}
```

### Step 3.2 — Commit

```bash
git add app/Http/Controllers/Admin/OrganizationAdminController.php
git commit -m "feat: add OrganizationAdminController with full CRUD and verify toggle"
```

---

## Task 4: SeminarAdminController

**Why:** Same reason as organizations — the routes are stubs. We need a real controller to CRUD seminars, query registrants, and handle the Create form that currently has `action="#"`.

### Files
- **Create:** `app/Http/Controllers/Admin/SeminarAdminController.php`

### Step 4.1 — Create the controller

Create `app/Http/Controllers/Admin/SeminarAdminController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Seminar;
use Illuminate\Http\Request;

class SeminarAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Seminar::withCount('registrants');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('presenter', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'upcoming') {
                $query->upcoming();
            } elseif ($request->status === 'past') {
                $query->past();
            }
        }

        $seminars = $query->orderBy('schedule', 'desc')->paginate(15)->withQueryString();

        $stats = [
            'total'    => Seminar::count(),
            'upcoming' => Seminar::upcoming()->count(),
            'past'     => Seminar::past()->count(),
            'premium'  => Seminar::premium()->count(),
        ];

        return view('admin.seminars.index', compact('seminars', 'stats'));
    }

    public function create()
    {
        $organizations = Organization::orderBy('name')->get();
        return view('admin.seminars.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:255',
            'date'          => 'required|date',
            'time'          => 'required|string',
            'type'          => 'required|in:online,in_person,hybrid',
            'capacity'      => 'nullable|integer|min:1',
            'presenter'     => 'nullable|string|max:255',
            'is_premium'    => 'boolean',
            'organizations' => 'nullable|array',
            'organizations.*' => 'exists:organizations,id',
        ]);

        $schedule = $validated['date'] . ' ' . $validated['time'];

        $seminar = Seminar::create([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location'    => $validated['location'] ?? null,
            'schedule'    => $schedule,
            'type'        => $validated['type'],
            'capacity'    => $validated['capacity'] ?? null,
            'presenter'   => $validated['presenter'] ?? null,
            'is_premium'  => $request->boolean('is_premium'),
        ]);

        if (!empty($validated['organizations'])) {
            $seminar->organizations()->sync($validated['organizations']);
        }

        return redirect()->route('admin.seminars.show', $seminar)
            ->with('success', 'Seminar created successfully.');
    }

    public function show(Seminar $seminar)
    {
        $seminar->load(['organizations', 'registrants.user']);
        return view('admin.seminars.show', compact('seminar'));
    }

    public function edit(Seminar $seminar)
    {
        $organizations = Organization::orderBy('name')->get();
        $seminar->load('organizations');
        return view('admin.seminars.edit', compact('seminar', 'organizations'));
    }

    public function update(Request $request, Seminar $seminar)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'location'      => 'nullable|string|max:255',
            'date'          => 'required|date',
            'time'          => 'required|string',
            'type'          => 'required|in:online,in_person,hybrid',
            'capacity'      => 'nullable|integer|min:1',
            'presenter'     => 'nullable|string|max:255',
            'is_premium'    => 'boolean',
            'organizations' => 'nullable|array',
            'organizations.*' => 'exists:organizations,id',
        ]);

        $seminar->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'location'    => $validated['location'] ?? null,
            'schedule'    => $validated['date'] . ' ' . $validated['time'],
            'type'        => $validated['type'],
            'capacity'    => $validated['capacity'] ?? null,
            'presenter'   => $validated['presenter'] ?? null,
            'is_premium'  => $request->boolean('is_premium'),
        ]);

        $seminar->organizations()->sync($validated['organizations'] ?? []);

        return redirect()->route('admin.seminars.show', $seminar)
            ->with('success', 'Seminar updated successfully.');
    }

    public function destroy(Seminar $seminar)
    {
        $seminar->delete();

        return redirect()->route('admin.seminars.index')
            ->with('success', 'Seminar deleted.');
    }
}
```

### Step 4.2 — Commit

```bash
git add app/Http/Controllers/Admin/SeminarAdminController.php
git commit -m "feat: add SeminarAdminController with full CRUD"
```

---

## Task 5: Update Admin Routes

**Why:** The organization and seminar routes currently use anonymous closures. They must be replaced with resource routes pointing to the new controllers.

### Files
- **Modify:** `routes/admin.php`

### Step 5.1 — Add imports and replace closures

At the top of `routes/admin.php`, add imports:

```php
use App\Http\Controllers\Admin\OrganizationAdminController;
use App\Http\Controllers\Admin\SeminarAdminController;
```

Replace the `// Seminars` closure block:

```php
// Seminars
Route::resource('seminars', SeminarAdminController::class);
```

Replace the `// Organizations` closure block:

```php
// Organizations
Route::resource('organizations', OrganizationAdminController::class);
Route::post('organizations/{organization}/toggle-verified', [OrganizationAdminController::class, 'toggleVerified'])
    ->name('organizations.toggle-verified');
```

Remove the `// Calendar` route's seminar closure and the separate `Route::prefix('seminars')` and `Route::prefix('organizations')` blocks entirely.

### Step 5.2 — Verify routes

```bash
php artisan route:list --name=admin.organizations
php artisan route:list --name=admin.seminars
```

Expected: standard CRUD routes (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) plus `toggle-verified`.

### Step 5.3 — Commit

```bash
git add routes/admin.php
git commit -m "feat: wire admin organizations and seminars routes to controllers"
```

---

## Task 6: Update Organization Views

**Why:** `index.blade.php` and `show.blade.php` currently render hardcoded PHP arrays. They must use the `$organizations` / `$organization` variables passed by the controller.

### Files
- **Modify:** `resources/views/admin/organizations/index.blade.php`
- **Modify:** `resources/views/admin/organizations/show.blade.php`
- **Create:** `resources/views/admin/organizations/create.blade.php`
- **Create:** `resources/views/admin/organizations/edit.blade.php`

### Step 6.1 — Update `organizations/index.blade.php`

Replace the static `$orgs` array and hardcoded stat numbers with real data:

1. Replace the four stat cards section to use `$stats`:

```blade
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([
        ['label'=>'Total Orgs',      'value'=>$stats['total'],       'bg'=>'bg-brand-50 dark:bg-brand-500/10',   'color'=>'text-brand-600 dark:text-brand-400'],
        ['label'=>'Verified',        'value'=>$stats['verified'],    'bg'=>'bg-success-50 dark:bg-success-500/10','color'=>'text-success-600 dark:text-success-400'],
        ['label'=>'Pending Review',  'value'=>$stats['pending'],     'bg'=>'bg-warning-50 dark:bg-warning-500/10','color'=>'text-warning-600 dark:text-warning-400'],
        ['label'=>'With Seminars',   'value'=>$stats['with_seminars'],'bg'=>'bg-purple-50 dark:bg-purple-500/10', 'color'=>'text-purple-600 dark:text-purple-400'],
    ] as $c)
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
            <span class="text-lg font-bold {{ $c['color'] }}">{{ $c['value'] }}</span>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $c['label'] }}</p>
    </div>
    @endforeach
</div>
```

2. Wire the "Add Organization" button to `route('admin.organizations.create')`.

3. Add a search form that posts GET to `route('admin.organizations.index')`:

```blade
<form method="GET" action="{{ route('admin.organizations.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search organizations..."
           class="px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm ...">
    <select name="verified" class="...">
        <option value="">All Status</option>
        <option value="1" {{ request('verified') === '1' ? 'selected' : '' }}>Verified</option>
        <option value="0" {{ request('verified') === '0' ? 'selected' : '' }}>Pending</option>
    </select>
    <button type="submit" class="px-4 py-2 rounded-lg bg-brand-500 text-white text-sm ...">Search</button>
</form>
```

4. Replace the `@foreach($orgs as $org)` loop with:

```blade
@forelse($organizations as $org)
<tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
    <td class="px-5 py-3">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 text-xs font-bold flex-shrink-0">
                {{ strtoupper(substr($org->name, 0, 1)) }}
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $org->name }}</p>
        </div>
    </td>
    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $org->location ?? '—' }}</td>
    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $org->contact_info ?? '—' }}</td>
    <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $org->seminars_count ?? 0 }}</td>
    <td class="px-5 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            {{ $org->verified ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
                              : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' }}">
            {{ $org->verified ? 'Verified' : 'Pending' }}
        </span>
    </td>
    <td class="px-5 py-3 text-right">
        <a href="{{ route('admin.organizations.show', $org) }}"
           class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors inline-flex">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
    </td>
</tr>
@empty
<tr><td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No organizations found.</td></tr>
@endforelse
```

5. Add pagination below the table:

```blade
<div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
    {{ $organizations->links() }}
</div>
```

6. Remove the top "backend coming soon" info banner.

### Step 6.2 — Update `organizations/show.blade.php`

Replace all hardcoded strings with `$organization` model fields:

```blade
{{-- Header --}}
<h2 class="text-xl font-bold text-gray-900 dark:text-white mb-1">{{ $organization->name }}</h2>

{{-- Status badge --}}
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
    {{ $organization->verified ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
                               : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' }}">
    {{ $organization->verified ? 'Verified Partner' : 'Pending Verification' }}
</span>

{{-- Detail grid --}}
<div><p class="text-xs text-gray-400 mb-0.5">Location</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $organization->location ?? '—' }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Contact</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $organization->contact_info ?? '—' }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Description</p><p class="text-sm text-gray-600 dark:text-gray-400">{{ $organization->description ?? '—' }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Member Since</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $organization->created_at->format('M Y') }}</p></div>
```

Replace the hardcoded Members table with a Seminars table (organizations host seminars, not members directly):

```blade
@forelse($organization->seminars as $seminar)
<tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $seminar->title }}</td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ $seminar->schedule->format('M d, Y') }}</td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $seminar->type)) }}</td>
</tr>
@empty
<tr><td colspan="3" class="px-5 py-6 text-center text-sm text-gray-400">No seminars yet.</td></tr>
@endforelse
```

Wire the **Edit Details** button in the Actions card to `route('admin.organizations.edit', $organization)`.

Wire the **Suspend Partnership** button to a DELETE form:

```blade
<form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}"
      onsubmit="return confirm('Delete this organization?')">
    @csrf @method('DELETE')
    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-error-200 dark:border-error-800 text-sm text-error-700 dark:text-error-400 hover:bg-error-50 dark:hover:bg-error-500/10 transition-colors">
        ...Delete Organization
    </button>
</form>
```

Add a Verify/Unverify toggle button:

```blade
<form method="POST" action="{{ route('admin.organizations.toggle-verified', $organization) }}">
    @csrf
    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
        {{ $organization->verified ? 'Unverify Organization' : 'Verify Organization' }}
    </button>
</form>
```

### Step 6.3 — Create `organizations/create.blade.php`

```blade
@extends('layouts.admin')
@section('title', 'Add Organization')
@section('page-title', 'Add Organization')
@section('content')

<div class="mb-5">
    <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Organizations
    </a>
</div>

<div class="max-w-2xl">
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Organization Details</h3>
        </div>
        <form class="p-6 space-y-5" method="POST" action="{{ route('admin.organizations.store') }}">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name <span class="text-error-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition @error('name') border-error-500 @enderror">
                @error('name')<p class="mt-1 text-xs text-error-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                <textarea name="description" rows="3"
                          class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 resize-none transition">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Location</label>
                    <input type="text" name="location" value="{{ old('location') }}"
                           class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Contact Info</label>
                    <input type="text" name="contact_info" value="{{ old('contact_info') }}"
                           class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                </div>
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="verified" value="1" {{ old('verified') ? 'checked' : '' }}
                           class="rounded border-gray-300 dark:border-gray-600 text-brand-500 focus:ring-brand-500">
                    Mark as Verified Partner
                </label>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('admin.organizations.index') }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Create Organization</button>
            </div>
        </form>
    </div>
</div>
@endsection
```

### Step 6.4 — Create `organizations/edit.blade.php`

Same structure as `create.blade.php` but pre-fills with model values and uses `PUT` method:

```blade
@extends('layouts.admin')
@section('title', 'Edit Organization')
@section('page-title', 'Edit Organization')
@section('content')

<div class="mb-5">
    <a href="{{ route('admin.organizations.show', $organization) }}" ...>← Back</a>
</div>

<div class="max-w-2xl">
    <div class="rounded-2xl ... overflow-hidden">
        <div class="px-6 py-4 border-b ...">
            <h3 ...>Edit Organization</h3>
        </div>
        <form class="p-6 space-y-5" method="POST" action="{{ route('admin.organizations.update', $organization) }}">
            @csrf @method('PUT')
            {{-- Same fields as create but with value="{{ old('name', $organization->name) }}" etc. --}}
            ...
        </form>
    </div>
</div>
@endsection
```

### Step 6.5 — Commit

```bash
git add resources/views/admin/organizations/
git commit -m "feat: update organization views with real data, add create/edit views"
```

---

## Task 7: Update Seminar Views

**Why:** `index.blade.php` and `show.blade.php` have hardcoded data; `create.blade.php` has `action="#"` with a JS alert stub. These must all be wired to use the controller variables and proper form actions.

### Files
- **Modify:** `resources/views/admin/seminars/index.blade.php`
- **Modify:** `resources/views/admin/seminars/create.blade.php`
- **Modify:** `resources/views/admin/seminars/show.blade.php`
- **Create:** `resources/views/admin/seminars/edit.blade.php`

### Step 7.1 — Update `seminars/index.blade.php`

1. Replace the stat cards section to use `$stats`:

```blade
@foreach([
    ['label'=>'Total Seminars','value'=>$stats['total'],   'bg'=>'bg-brand-50 dark:bg-brand-500/10',    'color'=>'text-brand-600 dark:text-brand-400'],
    ['label'=>'Upcoming',      'value'=>$stats['upcoming'],'bg'=>'bg-success-50 dark:bg-success-500/10','color'=>'text-success-600 dark:text-success-400'],
    ['label'=>'Past',          'value'=>$stats['past'],    'bg'=>'bg-gray-100 dark:bg-white/5',         'color'=>'text-gray-600 dark:text-gray-400'],
    ['label'=>'Premium',       'value'=>$stats['premium'], 'bg'=>'bg-warning-50 dark:bg-warning-500/10','color'=>'text-warning-600 dark:text-warning-400'],
] as $c)
...
@endforeach
```

2. Add a search/filter form (GET to `route('admin.seminars.index')`).

3. Replace hardcoded seminar rows with:

```blade
@forelse($seminars as $seminar)
<tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
    <td class="px-5 py-3">
        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $seminar->title }}</p>
        <p class="text-xs text-gray-400 mt-0.5">{{ $seminar->presenter ?? 'No presenter' }}</p>
    </td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ $seminar->schedule->format('M d, Y — g:i A') }}</td>
    <td class="px-5 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
            {{ $seminar->schedule > now() ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400'
                                         : 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-400' }}">
            {{ $seminar->schedule > now() ? 'Upcoming' : 'Past' }}
        </span>
    </td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ $seminar->registrants_count }}</td>
    <td class="px-5 py-3 text-right">
        <a href="{{ route('admin.seminars.show', $seminar) }}" class="p-1.5 rounded-lg ...">
            <svg class="w-4 h-4" ...chevron right.../>
        </a>
    </td>
</tr>
@empty
<tr><td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">No seminars found.</td></tr>
@endforelse
```

4. Add pagination: `{{ $seminars->links() }}`

### Step 7.2 — Update `seminars/create.blade.php`

Change the `<form>` tag:

```blade
{{-- Before --}}
<form class="p-6 space-y-5" method="POST" action="#" onsubmit="alert('Backend not yet implemented'); return false;">

{{-- After --}}
<form class="p-6 space-y-5" method="POST" action="{{ route('admin.seminars.store') }}">
```

Add `@csrf` if not already present (it already is).

Separate the `date` and `time` fields (they already exist in the form design — keep them as-is, controller merges them into `schedule`).

Add `@error` messages under each field.

Add an Organization multi-select at the bottom of the form (before the action buttons):

```blade
@if($organizations->isNotEmpty())
<div>
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Partner Organizations</label>
    <select name="organizations[]" multiple
            class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 h-28">
        @foreach($organizations as $org)
        <option value="{{ $org->id }}">{{ $org->name }}</option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-400">Hold Ctrl/Cmd to select multiple.</p>
</div>
@endif
```

Add a Premium toggle:

```blade
<div>
    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
        <input type="checkbox" name="is_premium" value="1" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
        Premium event (requires paid subscription)
    </label>
</div>
```

### Step 7.3 — Update `seminars/show.blade.php`

Replace all hardcoded strings with `$seminar` model fields:

```blade
{{-- Title --}}
<h2 ...>{{ $seminar->title }}</h2>

{{-- Type badge --}}
<span ...>{{ ucfirst(str_replace('_', ' ', $seminar->type)) }}</span>

{{-- Status badge --}}
<span ...>{{ $seminar->schedule > now() ? 'Upcoming' : 'Past' }}</span>

{{-- Detail grid --}}
<div><p class="text-xs text-gray-400 mb-0.5">Date</p><p ...>{{ $seminar->schedule->format('M d, Y') }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Time</p><p ...>{{ $seminar->schedule->format('g:i A') }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Presenter</p><p ...>{{ $seminar->presenter ?? '—' }}</p></div>
<div><p class="text-xs text-gray-400 mb-0.5">Capacity</p><p ...>{{ $seminar->registrants->count() }} / {{ $seminar->capacity ?? '∞' }}</p></div>
```

Replace hardcoded registrants loop:

```blade
@forelse($seminar->registrants as $reg)
<tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
    <td class="px-5 py-3">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 text-xs font-bold">
                {{ strtoupper(substr($reg->user->name, 0, 1)) }}
            </div>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $reg->user->name }}</p>
        </div>
    </td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ ucfirst($reg->user->role) }}</td>
    <td class="px-5 py-3 text-sm text-gray-500">{{ $reg->registered_at->format('M d, Y') }}</td>
    <td class="px-5 py-3">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ ucfirst($reg->status) }}
        </span>
    </td>
</tr>
@empty
<tr><td colspan="4" class="px-5 py-6 text-center text-sm text-gray-400">No registrants yet.</td></tr>
@endforelse
```

Wire the edit button in the header to `route('admin.seminars.edit', $seminar)`.

Wire the delete action button:

```blade
<form method="POST" action="{{ route('admin.seminars.destroy', $seminar) }}"
      onsubmit="return confirm('Delete this seminar?')">
    @csrf @method('DELETE')
    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-error-200 ...">
        Delete Seminar
    </button>
</form>
```

### Step 7.4 — Create `seminars/edit.blade.php`

Copy `seminars/create.blade.php`, change:
- `@section('title', 'Edit Seminar')`
- form `action="{{ route('admin.seminars.update', $seminar) }}"` with `@method('PUT')`
- Pre-fill each field: `value="{{ old('title', $seminar->title) }}"`, etc.
- Pre-select schedule fields: `value="{{ old('date', $seminar->schedule->format('Y-m-d')) }}"` and `value="{{ old('time', $seminar->schedule->format('H:i')) }}"`
- Pre-select organizations: `selected` when `$seminar->organizations->contains($org->id)`

### Step 7.5 — Commit

```bash
git add resources/views/admin/seminars/
git commit -m "feat: update seminar views with real data, wire create form, add edit view"
```

---

## Task 8: Flash Message Support

**Why:** Controllers redirect with `->with('success', ...)` and `->with('error', ...)` session flash messages. The admin layout needs to render these if it doesn't already.

### Step 8.1 — Check if flash messages are rendered

Open `resources/views/layouts/admin.blade.php` and search for `session('success')`.

If flash messages are already rendered, skip this task.

If not, add this block inside the `<main>` content area, just before `@yield('content')`:

```blade
@if(session('success'))
<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-success-50 border border-success-200 text-success-700 dark:bg-success-500/10 dark:border-success-500/20 dark:text-success-400 text-sm">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-error-50 border border-error-200 text-error-700 dark:bg-error-500/10 dark:border-error-500/20 dark:text-error-400 text-sm">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('error') }}
</div>
@endif
```

### Step 8.2 — Commit

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "feat: add flash message rendering to admin layout"
```

---

## Task 9: Smoke Test

**Why:** Verify the full flow end-to-end without a test database.

### Step 9.1 — Verify routes resolve

```bash
php artisan route:list --name=admin.organizations
php artisan route:list --name=admin.seminars
```

Expected: 8 + 7 = 15 named routes (CRUD + toggle-verified).

### Step 9.2 — Run existing tests

```bash
php artisan test
```

Expected: all existing tests pass (no regressions from the model fix).

### Step 9.3 — Final commit

```bash
git add .
git commit -m "chore: organizations and seminars backend implementation complete"
```

---

## Summary

| Task | Files Touched | Effort |
|------|--------------|--------|
| 1. Fix Organization model | `app/Models/Organization.php` | 5 min |
| 2. Migrations | 2 new migration files + `Seminar` model | 10 min |
| 3. OrganizationAdminController | New controller | 15 min |
| 4. SeminarAdminController | New controller | 15 min |
| 5. Update routes | `routes/admin.php` | 5 min |
| 6. Organization views (×4) | 4 view files | 20 min |
| 7. Seminar views (×4) | 4 view files | 20 min |
| 8. Flash messages | `layouts/admin.blade.php` | 5 min |
| 9. Smoke test | — | 5 min |

**Total estimated time: ~100 minutes**
