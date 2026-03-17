# Admin Development Guide

## 📋 Table of Contents
1. [System Overview](#system-overview)
2. [Role Architecture](#role-architecture)
3. [Getting Started](#getting-started)
4. [Admin Features to Build](#admin-features-to-build)
5. [Database Schema Reference](#database-schema-reference)
6. [Implementation Examples](#implementation-examples)
7. [Testing Checklist](#testing-checklist)

---

## 🎯 System Overview

### Purpose
This platform is a **Sex Education Learning Management System** with three distinct user roles:

| Role | Purpose | Responsibilities |
|------|---------|-----------------|
| **Admin** | System Manager | Manage subscriptions, payments, users, platform settings |
| **Instructor** | Content Creator | Create modules, lessons, quizzes; manage learners |
| **Learner** | Student | Take courses, complete quizzes, earn certificates |

### What You're Building
You are building the **Admin Panel** - the system management layer that handles:
- ✅ Subscription plan management
- ✅ Payment processing and tracking
- ✅ User management (create instructors, manage roles)
- ✅ Platform settings and configuration
- ✅ Analytics and reporting

**Note:** The instructor panel (content management) is already built and working. Your job is to build the admin system management features.

---

## 🔐 Role Architecture

### Migration Already Completed
The role separation has been implemented via migration:
```
database/migrations/2026_02_10_000000_create_instructor_role_and_migrate_admins.php
```

### Admin Permissions (Already Defined)
```php
// System Management
'manage subscriptions'
'manage payments'
'manage users'
'manage platform settings'
'view system analytics'

// Plus all instructor permissions:
'view modules', 'create modules', 'edit modules', 'delete modules'
'view lessons', 'create lessons', 'edit lessons', 'delete lessons'
// ... etc
```

### Current Users
- **User ID 1**: Super Admin (has admin role)
- **All other previous admins**: Migrated to instructor role

### How to Check Role
```php
// In controllers
if (!auth()->user()->hasRole('admin')) {
    abort(403, 'Unauthorized');
}

// In views
@role('admin')
    <!-- Admin-only content -->
@endrole
```

---

## 🚀 Getting Started

### File Structure
```
app/
├── Http/
│   └── Controllers/
│       ├── Admin/              ← YOUR WORK HERE (empty, ready for you)
│       │   ├── DashboardController.php
│       │   ├── SubscriptionController.php
│       │   ├── UserManagementController.php
│       │   ├── PlatformSettingsController.php
│       │   └── PaymentController.php
│       └── Instructor/         ← ALREADY BUILT (content management)

resources/
└── views/
    ├── admin/                  ← YOUR WORK HERE
    │   ├── dashboard.blade.php  ← Placeholder created, needs enhancement
    │   ├── subscriptions/
    │   ├── users/
    │   ├── settings/
    │   └── payments/
    └── instructor/             ← ALREADY BUILT
```

### Routes Setup
Routes are in `routes/web.php`:

```php
// Admin Routes (YOUR AREA)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])
        ->name('dashboard');
    
    // TODO: Add your routes here for subscriptions, users, settings, payments
});

// Instructor Routes (ALREADY WORKING)
Route::middleware(['auth', 'role:instructor'])->prefix('instructor')->name('instructor.')->group(function () {
    // All content management routes already here
});
```

### Access URLs
- **Admin Dashboard**: `http://localhost:8000/admin/dashboard`
- **Instructor Dashboard**: `http://localhost:8000/instructor/dashboard`
- **Learner Dashboard**: `http://localhost:8000/learner/dashboard`

---

## 📦 Admin Features to Build

### 1. Subscription Management 🎯 **HIGH PRIORITY**

#### Purpose
Manage subscription plans that instructors and organizations can purchase.

#### Database Tables
**Existing Table:** `subscriptions`
```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('plan_type', ['free', 'basic', 'premium', 'enterprise']);
    $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly']);
    $table->decimal('price', 10, 2);
    $table->timestamp('start_date');
    $table->timestamp('end_date')->nullable();
    $table->enum('status', ['active', 'inactive', 'pending', 'cancelled', 'expired']);
    $table->boolean('auto_renew')->default(false);
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

**NEW Table Needed:** `subscription_plans`
```php
Schema::create('subscription_plans', function (Blueprint $table) {
    $table->id();
    $table->string('name');                  // e.g., "Basic Plan"
    $table->string('slug')->unique();        // e.g., "basic"
    $table->text('description')->nullable();
    $table->decimal('monthly_price', 10, 2);
    $table->decimal('quarterly_price', 10, 2)->nullable();
    $table->decimal('yearly_price', 10, 2)->nullable();
    $table->json('features');                // JSON array of features
    $table->integer('max_modules')->nullable();
    $table->integer('max_learners')->nullable();
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

#### Features to Build

##### A. Plan Management
**Controller:** `SubscriptionController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::orderBy('sort_order')->get();
        return view('admin.subscriptions.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscriptions.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:subscription_plans',
            'description' => 'nullable|string',
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'nullable|numeric|min:0',
            'yearly_price' => 'nullable|numeric|min:0',
            'features' => 'required|array',
            'max_modules' => 'nullable|integer',
            'max_learners' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $plan = SubscriptionPlan::create($validated);
        
        return redirect()->route('admin.subscriptions.index')
            ->with('success', 'Subscription plan created successfully!');
    }

    // Add edit(), update(), destroy() methods
}
```

**Routes:**
```php
Route::resource('subscriptions', SubscriptionController::class);
Route::get('subscriptions/{plan}/subscribers', [SubscriptionController::class, 'subscribers'])
    ->name('subscriptions.subscribers');
```

**Views:**
- `admin/subscriptions/index.blade.php` - List all plans
- `admin/subscriptions/create.blade.php` - Create new plan
- `admin/subscriptions/edit.blade.php` - Edit plan
- `admin/subscriptions/subscribers.blade.php` - View subscribers

##### B. Active Subscriptions Dashboard
Display all active subscriptions with:
- User name
- Plan type
- Start/end dates
- Status
- Actions (cancel, extend, modify)

---

### 2. User Management 👥 **HIGH PRIORITY**

#### Purpose
Manage all platform users, create instructors, assign roles.

#### Features to Build

**Controller:** `UserManagementController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        // Filter by role
        if ($request->has('role') && $request->role != 'all') {
            $query->role($request->role);
        }
        
        // Search
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }
        
        $users = $query->paginate(20);
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'admin')->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function edit(User $user)
    {
        // Prevent editing super admin
        if ($user->id == 1) {
            abort(403, 'Cannot edit super admin');
        }
        
        $roles = Role::where('name', '!=', 'admin')->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|exists:roles,name',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Prevent deleting super admin and self
        if ($user->id == 1 || $user->id == auth()->id()) {
            abort(403, 'Cannot delete this user');
        }

        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }
}
```

**Routes:**
```php
Route::resource('users', UserManagementController::class);
Route::post('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
    ->name('users.toggle-status');
```

**Views:**
- `admin/users/index.blade.php` - User listing with search and filters
- `admin/users/create.blade.php` - Create instructor
- `admin/users/edit.blade.php` - Edit user
- `admin/users/show.blade.php` - User details

**Features:**
- ✅ Create instructors with credentials
- ✅ Assign/change roles
- ✅ Reset passwords
- ✅ Deactivate/activate users
- ✅ View user activity
- ✅ Search and filter

---

### 3. Payment Management 💰 **MEDIUM PRIORITY**

#### Purpose
Track payment transactions, process refunds, generate reports.

#### Database Table
**Existing:** `payments`
```php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
    $table->decimal('amount', 10, 2);
    $table->string('payment_method', 50)->nullable();
    $table->string('transaction_id')->unique()->nullable();
    $table->enum('status', ['pending', 'completed', 'failed', 'refunded']);
    $table->timestamp('paid_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

#### Features to Build

**Controller:** `PaymentController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['subscription.user']);
        
        // Filter by status
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }
        
        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $payments = $query->latest()->paginate(20);
        
        // Stats
        $stats = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending' => Payment::where('status', 'pending')->count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'refunded' => Payment::where('status', 'refunded')->count(),
        ];
        
        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function show(Payment $payment)
    {
        $payment->load('subscription.user');
        return view('admin.payments.show', compact('payment'));
    }

    public function processRefund(Request $request, Payment $payment)
    {
        if ($payment->status != 'completed') {
            return back()->with('error', 'Only completed payments can be refunded');
        }

        $payment->update([
            'status' => 'refunded',
            'notes' => 'Refunded by admin: ' . $request->reason,
        ]);

        // Update subscription status
        $payment->subscription->update(['status' => 'cancelled']);

        return redirect()->route('admin.payments.index')
            ->with('success', 'Payment refunded successfully');
    }
}
```

**Views:**
- `admin/payments/index.blade.php` - Payment list with filters
- `admin/payments/show.blade.php` - Payment details
- `admin/payments/reports.blade.php` - Revenue reports

---

### 4. Platform Settings ⚙️ **LOW PRIORITY**

#### Purpose
Configure platform-wide settings.

#### Database Table (NEW)
```php
Schema::create('platform_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->text('value')->nullable();
    $table->string('type')->default('text'); // text, number, boolean, json
    $table->string('group')->default('general');
    $table->text('description')->nullable();
    $table->timestamps();
});
```

#### Setting Groups
1. **General**: Site name, logo, contact email
2. **Email**: SMTP settings, notification preferences
3. **Features**: Enable/disable modules, quiz limits
4. **Gamification**: Points per quiz, achievement rewards
5. **Security**: Password rules, session timeout

**Controller:** `PlatformSettingsController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token', '_method') as $key => $value) {
            PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings updated successfully');
    }
}
```

---

### 5. Analytics Dashboard 📊 **MEDIUM PRIORITY**

#### Features
- Total users over time (chart)
- Revenue trends
- Popular modules
- Subscription conversion rates
- Active vs inactive users
- Recent activity feed

**Recommended Package:** Laravel Charts or Chart.js

**Controller Method Example:**
```php
public function analytics()
{
    $data = [
        'users_by_month' => User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get(),
        
        'revenue_by_month' => Payment::where('status', 'completed')
            ->selectRaw('DATE_FORMAT(paid_at, "%Y-%m") as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get(),
        
        'popular_modules' => Module::withCount('enrollments')
            ->orderBy('enrollments_count', 'desc')
            ->take(10)
            ->get(),
    ];
    
    return view('admin.analytics.index', compact('data'));
}
```

---

## 🗄️ Database Schema Reference

### Models You'll Work With

#### SubscriptionPlan (NEW - You need to create this)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description',
        'monthly_price', 'quarterly_price', 'yearly_price',
        'features', 'max_modules', 'max_learners',
        'is_active', 'sort_order'
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'plan_type', 'slug');
    }
}
```

#### Subscription (Already exists)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_type', 'billing_cycle', 'price',
        'start_date', 'end_date', 'status', 'auto_renew', 'notes'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive()
    {
        return $this->status === 'active' &&
               (!$this->end_date || $this->end_date->isFuture());
    }
}
```

#### Payment (Already exists)
```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'subscription_id', 'amount', 'payment_method',
        'transaction_id', 'status', 'paid_at', 'notes'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
```

---

## 💡 Implementation Examples

### Example 1: Subscription Plan Create Form

**View:** `admin/subscriptions/create.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Subscription Plan
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.subscriptions.store') }}">
                        @csrf

                        <!-- Plan Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Plan Name</label>
                            <input type="text" name="name" class="mt-1 block w-full rounded-md border-gray-300" required>
                        </div>

                        <!-- Slug -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input type="text" name="slug" class="mt-1 block w-full rounded-md border-gray-300" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                        </div>

                        <!-- Pricing -->
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Monthly Price</label>
                                <input type="number" name="monthly_price" step="0.01" class="mt-1 block w-full rounded-md border-gray-300" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Quarterly Price</label>
                                <input type="number" name="quarterly_price" step="0.01" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Yearly Price</label>
                                <input type="number" name="yearly_price" step="0.01" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <!-- Features (using Alpine.js for dynamic fields) -->
                        <div class="mb-4" x-data="{ features: [''] }">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
                            <template x-for="(feature, index) in features" :key="index">
                                <div class="flex gap-2 mb-2">
                                    <input type="text" :name="'features[' + index + ']'" 
                                           class="flex-1 rounded-md border-gray-300" 
                                           placeholder="Feature description">
                                    <button type="button" @click="features.splice(index, 1)" 
                                            class="px-3 py-2 bg-red-500 text-white rounded">Remove</button>
                                </div>
                            </template>
                            <button type="button" @click="features.push('')" 
                                    class="px-4 py-2 bg-blue-500 text-white rounded">Add Feature</button>
                        </div>

                        <!-- Limits -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Modules (leave empty for unlimited)</label>
                                <input type="number" name="max_modules" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Learners (leave empty for unlimited)</label>
                                <input type="number" name="max_learners" class="mt-1 block w-full rounded-md border-gray-300">
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                                <span class="ml-2 text-sm text-gray-700">Active (visible to users)</span>
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Create Plan
                            </button>
                            <a href="{{ route('admin.subscriptions.index') }}" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

### Example 2: User Management Index

**View:** `admin/users/index.blade.php`
```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                User Management
            </h2>
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Create New User
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search users..." 
                           value="{{ request('search') }}"
                           class="flex-1 rounded-md border-gray-300">
                    
                    <select name="role" class="rounded-md border-gray-300">
                        <option value="all">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        Filter
                    </button>
                </form>
            </div>

            <!-- User Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $user->hasRole('admin') ? 'bg-purple-100 text-purple-800' : '' }}
                                        {{ $user->hasRole('instructor') ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $user->hasRole('learner') ? 'bg-green-100 text-green-800' : '' }}">
                                        {{ $user->roles->first()->name ?? 'No role' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    @if($user->id != 1 && $user->id != auth()->id())
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" 
                                                    onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">No users found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-6 py-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

---

## ✅ Testing Checklist

### Before Starting Development
- [ ] Log in as admin (user ID 1) at `/admin/dashboard`
- [ ] Verify you can access admin dashboard
- [ ] Verify instructor cannot access admin routes
- [ ] Check all permissions are working with `php artisan permission:show`

### Subscription Management
- [ ] Create subscription plan with all pricing tiers
- [ ] Edit existing plan
- [ ] Activate/deactivate plan
- [ ] View list of subscribers for a plan
- [ ] Assign subscription to instructor
- [ ] Verify subscription limits work (max modules, learners)

### User Management
- [ ] Create new instructor account
- [ ] Create new learner account
- [ ] Edit user details
- [ ] Change user role
- [ ] Reset user password
- [ ] Search and filter users
- [ ] Verify cannot delete super admin (ID 1)
- [ ] Verify cannot delete self

### Payment Management
- [ ] View payment history
- [ ] Filter by status (pending, completed, refunded)
- [ ] Filter by date range
- [ ] Process refund
- [ ] View payment details
- [ ] Verify revenue calculations

### Platform Settings
- [ ] Update general settings
- [ ] Update email settings
- [ ] Toggle feature flags
- [ ] Verify settings persist after refresh

### Analytics
- [ ] View user growth chart
- [ ] View revenue trends
- [ ] Check popular modules report
- [ ] Verify data accuracy

---

## 📚 Helpful Resources

### Laravel Documentation
- [Spatie Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [Laravel Validation](https://laravel.com/docs/12.x/validation)
- [Eloquent Relationships](https://laravel.com/docs/12.x/eloquent-relationships)
- [Blade Templates](https://laravel.com/docs/12.x/blade)

### UI Components (Already in project)
- Tailwind CSS classes
- Alpine.js for interactivity
- Use existing instructor views as reference

### Database Commands
```bash
# Create migration
php artisan make:migration create_subscription_plans_table

# Create model
php artisan make:model SubscriptionPlan -m

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Check permissions
php artisan permission:show
```

---

## 🎯 Development Priority Order

### Phase 1: Foundation (Week 1)
1. ✅ Create SubscriptionPlan model and migration
2. ✅ Build subscription CRUD
3. ✅ Create admin navigation layout

### Phase 2: Core Features (Week 2)
1. ✅ User management (create instructors)
2. ✅ Payment viewing and tracking
3. ✅ Basic analytics dashboard

### Phase 3: Enhancement (Week 3)
1. ✅ Platform settings
2. ✅ Advanced analytics with charts
3. ✅ Email notifications for subscriptions

### Phase 4: Polish (Week 4)
1. ✅ UI/UX improvements
2. ✅ Testing and bug fixes
3. ✅ Documentation

---

## 🔍 Code Review Checklist

Before submitting your work:
- [ ] All routes have proper middleware (`auth`, `role:admin`)
- [ ] Form validation implemented on all inputs
- [ ] Success/error messages displayed to user
- [ ] Database queries optimized (use eager loading)
- [ ] No N+1 query problems
- [ ] Views use existing layout (`x-app-layout`)
- [ ] Tailwind CSS classes consistent with project style
- [ ] Permissions checked in controllers
- [ ] CSRF tokens in all forms
- [ ] User cannot delete or modify super admin (ID 1)

---

## ❓ FAQ

**Q: Can I modify instructor features?**
A: No, instructor features are complete. Focus only on admin features.

**Q: What if I need a new permission?**
A: Add it to the existing migration or create a new one. Ensure it's in the 'admin' role.

**Q: How do I test as admin?**
A: Log in as user ID 1 (the super admin account).

**Q: Should I create APIs?**
A: No, stick to traditional Laravel web routes and Blade views for now.

**Q: What about mobile responsiveness?**
A: Yes! Use Tailwind responsive classes (sm:, md:, lg:).

---

## 🤝 Support

If you encounter issues:
1. Check this guide first
2. Review existing instructor code as reference
3. Check Laravel and Spatie Permission documentation
4. Ask your teammate (the one who built instructor features)

---

**Good luck! 🚀**

Remember: The instructor panel is already working perfectly. Your job is to build the **system management layer** that handles subscriptions, payments, and platform administration. Keep the code clean, test thoroughly, and follow Laravel best practices!
