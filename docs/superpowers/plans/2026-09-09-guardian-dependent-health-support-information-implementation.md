# Guardian-Dependent Health & Support Information Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add optional, encrypted, dependent-owned Health & Support Information that a dependent and individually authorized verified guardians can manage without exposing it to instructors or relationship reviewers and without affecting relationship verification.

**Architecture:** Store one encrypted `DependentSupportProfile` per dependent and metadata-only audit rows in dedicated tables. Reuse `ParentChildAccount::accessEligible()` as the guardian lifecycle boundary, add one dependent-controlled relationship permission, and route every content operation through a dedicated policy and transactional service. Insert the optional registration page only after the dependent and pending relationship have been committed, so no sensitive text enters the registration wizard session or relationship-review transaction.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent encrypted casts, MySQL, Blade, Alpine.js already present in the application, database notifications, PHPUnit 11, Laravel Pint, Vite/pnpm.

## Global Constraints

- Read `docs/superpowers/specs/2026-09-09-guardian-dependent-health-support-information-design.md` before implementation.
- Begin from Phase 2 completion commit `5a4fa26` or a descendant containing the complete invitation/messaging implementation and verification report.
- Preserve unrelated working-tree changes. Stage only the files named by the current task.
- Use an isolated worktree through `superpowers:using-git-worktrees` when the implementation session permits it.
- Add no Composer or npm packages.
- Health & Support Information is optional. No row means no information was provided.
- Do not add medical documents, diagnoses, classifications, screening, scoring, treatment advice, or emergency-response behavior.
- Do not add support content to `learner_profiles`, `parent_child_accounts`, invitations, chats, notifications, logs, search indexes, analytics, or verification records.
- Detailed support information must never affect guardian identity, dependent identity, invitation, evidence, relationship approval, or relationship activation decisions.
- Detailed support information is unavailable to instructors, unrelated users, and relationship-verification administrators, including administrators covered by the existing global Gate bypass.
- A guardian requires the exact active, verified, `accessEligible()` relationship and `can_manage_support_information = true`.
- Existing and invitation-created relationships default the new permission to false. A newly created dependent's originating relationship sets it true but cannot exercise it until verification succeeds.
- Rejection, revocation, or deactivation resets only that relationship's support permission to false. Reactivation does not restore it automatically.
- Use Laravel encrypted casts for all three content fields. Never assert ciphertext equality because encryption is nondeterministic.
- Do not flash the three sensitive field names into session storage after validation failure.
- Use `php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport --testdox` on Windows. The Phase 2 verification report records a current-directory issue with `php artisan test`.
- Every non-trivial change follows red-green-refactor: write the focused failing test, observe the expected failure, implement the minimum code, rerun the focused test, then commit.
- Test data must be synthetic and must not contain real medical or child data.
- Use database constraints as the final duplicate defense and policies/query scopes as the record-level authorization boundary.
- Sensitive GET responses must send `Cache-Control: private, no-store`.
- Notifications and audits may contain IDs, action names, and timestamps only; they may not contain support text or changed-field values.

## File Structure

### Create

- `database/migrations/2026_09_09_100000_create_dependent_support_information.php` — support tables and relationship permission.
- `app/Models/DependentSupportProfile.php` — encrypted dependent-owned content and actor relationships.
- `app/Models/DependentSupportInformationAudit.php` — metadata-only actions.
- `app/Policies/DependentSupportProfilePolicy.php` — self and exact-relationship authorization.
- `app/Http/Requests/DependentSupport/StoreDependentSupportInformationRequest.php` — normalized support input and purpose acknowledgement.
- `app/Http/Requests/DependentSupport/UpdateGuardianSupportAccessRequest.php` — dependent-controlled permission validation.
- `app/Services/DependentSupportInformationService.php` — transactions, optimistic concurrency, hard deletion, permission changes, audits, and after-commit notifications.
- `app/Notifications/DependentSupportInformationChangedNotification.php` — privacy-safe notice to the dependent after guardian changes.
- `app/Notifications/GuardianSupportAccessChangedNotification.php` — privacy-safe notice after a dependent changes guardian access.
- `app/Http/Controllers/Auth/DependentSupportRegistrationController.php` — short-lived post-creation setup page, save, and skip.
- `app/Http/Controllers/Learner/DependentSupportInformationController.php` — dependent self-service CRUD.
- `app/Http/Controllers/Parent/DependentSupportInformationController.php` — authorized guardian CRUD.
- `resources/views/auth/child/step6-support-information.blade.php` — optional registration page and purpose notice.
- `resources/views/dependent-support-information/edit.blade.php` — shared ongoing editor and removal confirmation.
- `tests/Feature/DependentSupport/DependentSupportSchemaTest.php` — schema, casts, encryption, and relation contracts.
- `tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php` — actor and relationship-state matrix.
- `tests/Feature/DependentSupport/DependentSupportValidationTest.php` — data minimization and no-flash rules.
- `tests/Feature/DependentSupport/DependentSupportServiceTest.php` — CRUD, audits, concurrency, and hard deletion.
- `tests/Feature/DependentSupport/DependentSupportRegistrationTest.php` — optional post-creation flow.
- `tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php` — learner and guardian pages and denial paths.
- `tests/Feature/DependentSupport/DependentSupportPermissionTest.php` — independent guardian permission control and lifecycle resets.
- `tests/Feature/DependentSupport/DependentSupportNotificationTest.php` — payload privacy and fresh authorization.
- `tests/Feature/DependentSupport/DependentSupportVerificationIsolationTest.php` — no effect on Phase 1 or Phase 2 decisions.
- `docs/superpowers/verification/2026-09-09-guardian-dependent-health-support-information-e2e.md` — final command and browser evidence.

### Modify

- `app/Models/User.php` — one `dependentSupportProfile()` relation and relationship pivot exposure for the boolean permission.
- `app/Models/ParentChildAccount.php` — fillable/cast/permission allowlist entry.
- `app/Providers/AppServiceProvider.php` — policy registration and sensitive-subject exemption from the global admin bypass.
- `bootstrap/app.php` — prevent the three content fields from being flashed.
- `app/Http/Controllers/Auth/ParentRegistrationController.php` — redirect a completed relationship submission to the optional support step using IDs only.
- `app/Http/Controllers/Learner/ParentVisibilityController.php` — dependent-controlled permission endpoint.
- `app/Services/GuardianRelationshipVerificationService.php` — reset support permission on rejected, revoked, and inactive transitions.
- `app/Services/Admin/UserRelationshipService.php` — admin-created relationships default support access false.
- `app/Services/ParentChildInvitationService.php` — invitation-created relationships default support access false.
- `routes/auth.php` — optional registration routes.
- `routes/web.php` — learner, guardian, and permission routes.
- `resources/views/auth/child/done.blade.php` — completion copy acknowledging the optional step.
- `resources/views/learner/parent/index.blade.php` — per-guardian support permission control.
- `resources/views/learner/dashboard.blade.php` — dependent self-service entry point.
- `resources/views/parent/children/index.blade.php` — guardian action shown only when the exact relationship permits it.
- `tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php` — preserve existing wizard/evidence assertions with the new redirect.
- `tests/Feature/GuardianRelationshipLifecycleTest.php` — lifecycle regression for the new permission.
- `tests/Feature/Parent/ParentChildInvitationFlowTest.php` — invitation default regression.
- `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php` — verification isolation regression only; preserve any unrelated local edits.

---

### Task 1: Add encrypted dependent-owned storage

**Files:**

- Create: `database/migrations/2026_09_09_100000_create_dependent_support_information.php`
- Create: `app/Models/DependentSupportProfile.php`
- Create: `app/Models/DependentSupportInformationAudit.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/ParentChildAccount.php`
- Create: `tests/Feature/DependentSupport/DependentSupportSchemaTest.php`

**Interfaces:**

- Produces: `User::dependentSupportProfile(): HasOne`.
- Produces: `DependentSupportProfile::CONTENT_FIELDS`, `NOTICE_VERSION`, and encrypted field casts.
- Produces: `ParentChildAccount::$can_manage_support_information` and support for `withPermission('can_manage_support_information')`.
- Produces: metadata-only `DependentSupportInformationAudit` rows used by the service in Task 4.

- [ ] **Step 1: Write the failing schema and encryption test**

Create `tests/Feature/DependentSupport/DependentSupportSchemaTest.php`:

```php
<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportInformationAudit;
use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DependentSupportSchemaTest extends TestCase
{
    public function test_schema_and_relationship_permission_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('dependent_support_profiles', [
            'dependent_user_id',
            'relevant_health_considerations',
            'accessibility_support_needs',
            'additional_relevant_information',
            'privacy_notice_version',
            'purpose_acknowledged_at',
            'purpose_acknowledged_by_user_id',
            'created_by_user_id',
            'updated_by_user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('dependent_support_information_audits', [
            'dependent_user_id', 'actor_user_id', 'parent_child_account_id',
            'action', 'changed_fields', 'occurred_at',
        ]));
        $this->assertTrue(Schema::hasColumn('parent_child_accounts', 'can_manage_support_information'));
    }

    public function test_support_content_is_encrypted_and_one_record_per_dependent(): void
    {
        $dependent = User::factory()->create();
        $actor = User::factory()->create();

        $profile = DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'relevant_health_considerations' => 'Synthetic participation consideration',
            'accessibility_support_needs' => 'Synthetic reading support',
            'additional_relevant_information' => null,
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $actor->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);

        $raw = DB::table('dependent_support_profiles')->where('id', $profile->id)->first();

        $this->assertNotSame('Synthetic participation consideration', $raw->relevant_health_considerations);
        $this->assertNotSame('Synthetic reading support', $raw->accessibility_support_needs);
        $this->assertSame('Synthetic participation consideration', $profile->fresh()->relevant_health_considerations);
        $this->assertSame($profile->id, $dependent->dependentSupportProfile()->firstOrFail()->id);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $actor->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
    }

    public function test_relationship_permission_defaults_false_and_is_queryable(): void
    {
        $guardian = User::factory()->create();
        $dependent = User::factory()->create();
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
        ]);

        $this->assertFalse($relationship->fresh()->can_manage_support_information);
        $this->assertFalse(ParentChildAccount::query()
            ->withPermission('can_manage_support_information')
            ->whereKey($relationship->id)
            ->exists());
    }

    public function test_audit_casts_changed_field_names_without_content(): void
    {
        $dependent = User::factory()->create();
        $audit = DependentSupportInformationAudit::query()->create([
            'dependent_user_id' => $dependent->id,
            'action' => DependentSupportInformationAudit::ACTION_UPDATED,
            'changed_fields' => ['accessibility_support_needs'],
            'occurred_at' => now(),
        ]);

        $this->assertSame(['accessibility_support_needs'], $audit->fresh()->changed_fields);
    }
}
```

- [ ] **Step 2: Run the test and verify the expected failure**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportSchemaTest.php --testdox
```

Expected: FAIL because the tables, models, relationship, and permission do not exist.

- [ ] **Step 3: Create the migration**

Create `database/migrations/2026_09_09_100000_create_dependent_support_information.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->boolean('can_manage_support_information')
                ->default(false)
                ->after('can_approve_content');
        });

        Schema::create('dependent_support_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dependent_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->text('relevant_health_considerations')->nullable();
            $table->text('accessibility_support_needs')->nullable();
            $table->text('additional_relevant_information')->nullable();
            $table->string('privacy_notice_version', 32);
            $table->timestamp('purpose_acknowledged_at');
            $table->foreignId('purpose_acknowledged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('dependent_support_information_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dependent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_child_account_id')->nullable()->constrained('parent_child_accounts')->nullOnDelete();
            $table->string('action', 32)->index();
            $table->json('changed_fields')->nullable();
            $table->timestamp('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependent_support_information_audits');
        Schema::dropIfExists('dependent_support_profiles');

        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->dropColumn('can_manage_support_information');
        });
    }
};
```

- [ ] **Step 4: Add the focused models**

Create `app/Models/DependentSupportProfile.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DependentSupportProfile extends Model
{
    public const NOTICE_VERSION = '2026-09-09-v1';

    public const CONTENT_FIELDS = [
        'relevant_health_considerations',
        'accessibility_support_needs',
        'additional_relevant_information',
    ];

    protected $fillable = [
        'dependent_user_id',
        'relevant_health_considerations',
        'accessibility_support_needs',
        'additional_relevant_information',
        'privacy_notice_version',
        'purpose_acknowledged_at',
        'purpose_acknowledged_by_user_id',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'relevant_health_considerations' => 'encrypted',
            'accessibility_support_needs' => 'encrypted',
            'additional_relevant_information' => 'encrypted',
            'purpose_acknowledged_at' => 'datetime',
        ];
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dependent_user_id');
    }

    public function purposeAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purpose_acknowledged_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
```

Create `app/Models/DependentSupportInformationAudit.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DependentSupportInformationAudit extends Model
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_REMOVED = 'removed';
    public const ACTION_PERMISSION_GRANTED = 'permission_granted';
    public const ACTION_PERMISSION_REVOKED = 'permission_revoked';

    public $timestamps = false;

    protected $fillable = [
        'dependent_user_id',
        'actor_user_id',
        'parent_child_account_id',
        'action',
        'changed_fields',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_fields' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function dependent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dependent_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function relationship(): BelongsTo
    {
        return $this->belongsTo(ParentChildAccount::class, 'parent_child_account_id');
    }
}
```

- [ ] **Step 5: Wire the existing model relations and permission allowlist**

Add to `User`:

```php
use Illuminate\Database\Eloquent\Relations\HasOne;

public function dependentSupportProfile(): HasOne
{
    return $this->hasOne(DependentSupportProfile::class, 'dependent_user_id');
}
```

Add `can_manage_support_information` to the pivot columns in `guardians()`, `children()`, and the legacy singular `parent()` relation so existing view queries expose the new boolean consistently. Do not add `dependentSupportProfile` to `$with` or any broad eager-loading list.

Add the field to `ParentChildAccount::$fillable`, cast it to boolean, and extend only the existing allowlist:

```php
$allowed = [
    'can_view_progress',
    'can_view_quiz_answers',
    'can_approve_content',
    'can_manage_support_information',
];
```

- [ ] **Step 6: Run schema tests and focused formatting**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportSchemaTest.php --testdox
vendor\bin\pint --test app/Models/DependentSupportProfile.php app/Models/DependentSupportInformationAudit.php app/Models/User.php app/Models/ParentChildAccount.php database/migrations/2026_09_09_100000_create_dependent_support_information.php tests/Feature/DependentSupport/DependentSupportSchemaTest.php
```

Expected: all schema tests PASS and Pint reports no formatting changes required.

- [ ] **Step 7: Commit Task 1**

```powershell
git add database/migrations/2026_09_09_100000_create_dependent_support_information.php app/Models/DependentSupportProfile.php app/Models/DependentSupportInformationAudit.php app/Models/User.php app/Models/ParentChildAccount.php tests/Feature/DependentSupport/DependentSupportSchemaTest.php
git commit -m "feat: add encrypted dependent support storage"
```

### Task 2: Enforce the sensitive authorization boundary

**Files:**

- Create: `app/Policies/DependentSupportProfilePolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php`

**Interfaces:**

- Consumes: `ParentChildAccount::accessEligible()` and `withPermission('can_manage_support_information')`.
- Produces: standard `create`, `view`, `update`, and `delete` policy abilities for `DependentSupportProfile`.
- Produces: an argument-aware global Gate exception that applies only when the authorization subject is `DependentSupportProfile` or its class name.

- [ ] **Step 1: Write the failing authorization matrix**

Create helpers in `tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php` that create an active dependent, an active approved guardian, a profile, and a relationship. Add tests with these assertions:

```php
$this->assertTrue(Gate::forUser($dependent)->allows('view', $profile));
$this->assertTrue(Gate::forUser($dependent)->allows('update', $profile));
$this->assertTrue(Gate::forUser($dependent)->allows('delete', $profile));
$this->assertTrue(Gate::forUser($dependent)->allows('create', [DependentSupportProfile::class, $dependent]));

$relationship->update(['can_manage_support_information' => true]);
$this->assertTrue(Gate::forUser($guardian)->allows('view', $profile));

$relationship->update(['can_manage_support_information' => false]);
$this->assertFalse(Gate::forUser($guardian)->allows('view', $profile));

$relationship->update([
    'relationship_status' => ParentChildAccount::STATUS_PENDING,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
    'relationship_verified_at' => null,
    'can_manage_support_information' => true,
]);
$this->assertFalse(Gate::forUser($guardian)->allows('view', $profile));
```

Add separate tests proving an instructor, unrelated learner, suspended guardian, and administrator are denied. For the administrator regression, also assert the existing bypass still permits an unrelated ordinary policy ability:

```php
$admin->assignRole('admin');
$this->assertFalse(Gate::forUser($admin)->allows('view', $profile));
$this->assertTrue(Gate::forUser($admin)->allows('view', $dependent));
```

- [ ] **Step 2: Run the authorization test and verify it fails**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php --testdox
```

Expected: FAIL because the policy is not registered and the current global administrator callback returns true.

- [ ] **Step 3: Implement the dedicated policy**

Create `app/Policies/DependentSupportProfilePolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;

class DependentSupportProfilePolicy
{
    public function create(User $actor, User $dependent): bool
    {
        return $this->canManage($actor, $dependent);
    }

    public function view(User $actor, DependentSupportProfile $profile): bool
    {
        return $this->canManage($actor, $profile->dependent);
    }

    public function update(User $actor, DependentSupportProfile $profile): bool
    {
        return $this->view($actor, $profile);
    }

    public function delete(User $actor, DependentSupportProfile $profile): bool
    {
        return $this->view($actor, $profile);
    }

    private function canManage(User $actor, User $dependent): bool
    {
        if ($actor->status !== User::STATUS_ACTIVE || $dependent->status !== User::STATUS_ACTIVE) {
            return false;
        }

        if ((int) $actor->id === (int) $dependent->id) {
            return true;
        }

        return ParentChildAccount::query()
            ->accessEligible()
            ->withPermission('can_manage_support_information')
            ->where('parent_user_id', $actor->id)
            ->where('child_user_id', $dependent->id)
            ->exists();
    }
}
```

- [ ] **Step 4: Register the policy without widening administrator access**

In `AppServiceProvider`, import `DependentSupportProfile` and `DependentSupportProfilePolicy`. Replace the current Gate callback with an argument-aware callback:

```php
Gate::before(function (?User $user, string $ability, array $arguments = []) {
    $subject = $arguments[0] ?? null;

    if ($subject instanceof DependentSupportProfile || $subject === DependentSupportProfile::class) {
        return null;
    }

    if ($user?->hasRole('admin')) {
        return true;
    }

    return null;
});

Gate::policy(DependentSupportProfile::class, DependentSupportProfilePolicy::class);
```

Do not replace or weaken the existing Gate mappings for `User`, `Module`, `Lesson`, or other models.

- [ ] **Step 5: Run authorization and existing parent-policy tests**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php tests/Feature/ParentChildMonitoringTest.php --testdox
vendor\bin\pint --test app/Policies/DependentSupportProfilePolicy.php app/Providers/AppServiceProvider.php tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php
```

Expected: the support matrix passes, administrators remain denied for support content, and existing parent monitoring authorization remains green.

- [ ] **Step 6: Commit Task 2**

```powershell
git add app/Policies/DependentSupportProfilePolicy.php app/Providers/AppServiceProvider.php tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php
git commit -m "feat: restrict dependent support access"
```

### Task 3: Normalize minimal input and prevent session flashing

**Files:**

- Create: `app/Http/Requests/DependentSupport/StoreDependentSupportInformationRequest.php`
- Create: `app/Http/Requests/DependentSupport/UpdateGuardianSupportAccessRequest.php`
- Modify: `bootstrap/app.php`
- Create: `tests/Feature/DependentSupport/DependentSupportValidationTest.php`

**Interfaces:**

- Produces: `StoreDependentSupportInformationRequest::supportPayload(): array` containing only the three normalized fields.
- Produces: `UpdateGuardianSupportAccessRequest::enabled(): bool`.
- Enforces: 1,000 characters per field, at least one value when Yes is selected, fresh acknowledgement, no file field, and no sensitive old-input flash.

- [ ] **Step 1: Write failing request-validation tests**

Test a valid payload, each field independently, all-whitespace content, missing acknowledgement, arrays instead of strings, HTML tags, `medical_document`, `documents`, values over 1,000 characters, and `has_relevant_support_information = 0`. Use a real test-only POST endpoint defined inside the test setup so Laravel's normal validation redirect is exercised. Assert the invalid response has errors and the session excludes the sensitive keys:

```php
$response = $this->actingAs($dependent)->from('/support-test')->post('/support-test', [
    'has_relevant_support_information' => '1',
    'relevant_health_considerations' => str_repeat('x', 1001),
    'accessibility_support_needs' => 'Synthetic support text',
    'additional_relevant_information' => 'Synthetic private marker',
]);

$response->assertRedirect('/support-test')
    ->assertSessionHasErrors(['relevant_health_considerations', 'purpose_acknowledged']);
$response->assertSessionMissing('_old_input.relevant_health_considerations');
$response->assertSessionMissing('_old_input.accessibility_support_needs');
$response->assertSessionMissing('_old_input.additional_relevant_information');
$this->assertStringNotContainsString('Synthetic private marker', serialize(session()->all()));
```

The test-only route closure type-hints `StoreDependentSupportInformationRequest` and returns `response()->noContent()` so the production request lifecycle is used without waiting for the controllers.

- [ ] **Step 2: Run the validation test and verify it fails**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportValidationTest.php --testdox
```

Expected: FAIL because the request classes and no-flash configuration do not exist.

- [ ] **Step 3: Implement the support request**

Create `app/Http/Requests/DependentSupport/StoreDependentSupportInformationRequest.php`:

```php
<?php

namespace App\Http\Requests\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDependentSupportInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (DependentSupportProfile::CONTENT_FIELDS as $field) {
            $value = $this->input($field);
            $normalized[$field] = is_string($value) && trim($value) !== '' ? trim($value) : null;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'has_relevant_support_information' => ['required', 'boolean'],
            'relevant_health_considerations' => ['nullable', 'string', 'max:1000'],
            'accessibility_support_needs' => ['nullable', 'string', 'max:1000'],
            'additional_relevant_information' => ['nullable', 'string', 'max:1000'],
            'purpose_acknowledged' => [
                Rule::requiredIf($this->boolean('has_relevant_support_information')),
                'nullable',
                'accepted',
            ],
            'expected_updated_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'medical_document' => ['prohibited'],
            'documents' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('has_relevant_support_information')) {
                return;
            }

            $hasContent = collect($this->supportPayload())->contains(
                fn (?string $value): bool => $value !== null,
            );

            if (! $hasContent) {
                $validator->errors()->add(
                    'has_relevant_support_information',
                    'Provide at least one relevant support detail or choose Skip for now.',
                );
            }

            foreach (DependentSupportProfile::CONTENT_FIELDS as $field) {
                $value = $this->input($field);

                if (is_string($value) && strip_tags($value) !== $value) {
                    $validator->errors()->add($field, 'Use plain text only.');
                }
            }
        }];
    }

    public function supportPayload(): array
    {
        return collect(DependentSupportProfile::CONTENT_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $this->input($field)])
            ->all();
    }
}
```

The two likely document keys are explicitly prohibited. Laravel ignores unrelated scalar keys because the service consumes only `supportPayload()`.

- [ ] **Step 4: Implement the permission request**

Create `app/Http/Requests/DependentSupport/UpdateGuardianSupportAccessRequest.php`:

```php
<?php

namespace App\Http\Requests\DependentSupport;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGuardianSupportAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }
}
```

- [ ] **Step 5: Prevent sensitive old-input flashing globally**

Change the empty exception configuration in `bootstrap/app.php` to:

```php
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->dontFlash([
        'relevant_health_considerations',
        'accessibility_support_needs',
        'additional_relevant_information',
    ]);
})->create();
```

The names are unique to this feature, so this does not suppress unrelated form values.

- [ ] **Step 6: Run validation tests and formatting**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportValidationTest.php --testdox
vendor\bin\pint --test app/Http/Requests/DependentSupport/StoreDependentSupportInformationRequest.php app/Http/Requests/DependentSupport/UpdateGuardianSupportAccessRequest.php bootstrap/app.php tests/Feature/DependentSupport/DependentSupportValidationTest.php
```

Expected: all validation and no-flash assertions PASS.

- [ ] **Step 7: Commit Task 3**

```powershell
git add app/Http/Requests/DependentSupport/StoreDependentSupportInformationRequest.php app/Http/Requests/DependentSupport/UpdateGuardianSupportAccessRequest.php bootstrap/app.php tests/Feature/DependentSupport/DependentSupportValidationTest.php
git commit -m "feat: validate minimal support information"
```

### Task 4: Implement transactional CRUD, audit, and concurrency

**Files:**

- Create: `app/Services/DependentSupportInformationService.php`
- Create: `tests/Feature/DependentSupport/DependentSupportServiceTest.php`

**Interfaces:**

- Consumes: the policy from Task 2 and normalized content from Task 3.
- Produces: `save(User $dependent, User $actor, array $payload, ?string $expectedUpdatedAt): DependentSupportProfile`.
- Produces: `saveDuringRegistration(ParentChildAccount $relationship, User $guardian, array $payload): DependentSupportProfile`.
- Produces: `remove(DependentSupportProfile $profile, User $actor, string $expectedUpdatedAt): void`.
- Produces: `setGuardianAccess(ParentChildAccount $relationship, User $dependent, bool $enabled): ParentChildAccount`.
- Produces: `resetGuardianAccessForLifecycle(ParentChildAccount $relationship, User $actor): void`.

- [ ] **Step 1: Write failing service tests**

Cover these behaviors in `DependentSupportServiceTest`:

```php
$created = $service->save($dependent, $dependent, [
    'relevant_health_considerations' => null,
    'accessibility_support_needs' => 'Synthetic reading support',
    'additional_relevant_information' => null,
], null);

$this->assertSame($dependent->id, $created->dependent_user_id);
$this->assertDatabaseHas('dependent_support_information_audits', [
    'dependent_user_id' => $dependent->id,
    'actor_user_id' => $dependent->id,
    'action' => DependentSupportInformationAudit::ACTION_CREATED,
]);

$expected = $created->getRawOriginal('updated_at');
$updated = $service->save($dependent, $dependent, [
    'relevant_health_considerations' => null,
    'accessibility_support_needs' => 'Updated synthetic support',
    'additional_relevant_information' => null,
], $expected);
$this->assertSame('Updated synthetic support', $updated->accessibility_support_needs);
```

Also test:

- create rejects an already-created row when no version was supplied;
- update rejects a stale `expected_updated_at`;
- stale update after delete does not recreate the record;
- hard deletion removes the encrypted row and leaves a metadata-only `removed` audit;
- audit JSON contains only names from `DependentSupportProfile::CONTENT_FIELDS`;
- `saveDuringRegistration` accepts only the exact guardian and exact nonterminal relationship;
- `setGuardianAccess` accepts only the active dependent's own access-eligible relationship;
- granting one guardian does not alter a second relationship;
- unauthorized service calls throw `AuthorizationException` rather than leaking record existence.

- [ ] **Step 2: Run the service test and verify it fails**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportServiceTest.php --testdox
```

Expected: FAIL because `DependentSupportInformationService` does not exist.

- [ ] **Step 3: Implement the transactional service**

Create `app/Services/DependentSupportInformationService.php` with these exact public methods and private boundaries:

```php
<?php

namespace App\Services;

use App\Models\DependentSupportInformationAudit;
use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DependentSupportInformationService
{
    public function save(
        User $dependent,
        User $actor,
        array $payload,
        ?string $expectedUpdatedAt,
    ): DependentSupportProfile {
        return DB::transaction(function () use ($dependent, $actor, $payload, $expectedUpdatedAt): DependentSupportProfile {
            $profile = DependentSupportProfile::query()
                ->where('dependent_user_id', $dependent->id)
                ->lockForUpdate()
                ->first();

            if ($profile) {
                Gate::forUser($actor)->authorize('update', $profile);
                $this->assertFresh($profile, $expectedUpdatedAt);
            } else {
                Gate::forUser($actor)->authorize('create', [DependentSupportProfile::class, $dependent]);

                if ($expectedUpdatedAt !== null) {
                    throw ValidationException::withMessages([
                        'support_information' => 'This information changed. Reload and review the latest version.',
                    ]);
                }
            }

            $values = $this->contentValues($payload);
            $changedFields = $this->changedFields($profile, $values);

            if ($profile) {
                $profile->update([
                    ...$values,
                    'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                    'purpose_acknowledged_at' => now(),
                    'purpose_acknowledged_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
                $action = DependentSupportInformationAudit::ACTION_UPDATED;
            } else {
                $profile = DependentSupportProfile::query()->create([
                    'dependent_user_id' => $dependent->id,
                    ...$values,
                    'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                    'purpose_acknowledged_at' => now(),
                    'purpose_acknowledged_by_user_id' => $actor->id,
                    'created_by_user_id' => $actor->id,
                    'updated_by_user_id' => $actor->id,
                ]);
                $action = DependentSupportInformationAudit::ACTION_CREATED;
            }

            $this->audit($dependent->id, $actor->id, null, $action, $changedFields);

            return $profile->fresh();
        });
    }

    public function saveDuringRegistration(
        ParentChildAccount $relationship,
        User $guardian,
        array $payload,
    ): DependentSupportProfile {
        return DB::transaction(function () use ($relationship, $guardian, $payload): DependentSupportProfile {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);

            if ((int) $locked->parent_user_id !== (int) $guardian->id
                || $locked->trashed()
                || in_array($locked->relationship_status, [
                    ParentChildAccount::STATUS_REJECTED,
                    ParentChildAccount::STATUS_REVOKED,
                    ParentChildAccount::STATUS_INACTIVE,
                ], true)) {
                throw new AuthorizationException('The dependent setup link is no longer available.');
            }

            if (DependentSupportProfile::query()->where('dependent_user_id', $locked->child_user_id)->exists()) {
                throw ValidationException::withMessages([
                    'support_information' => 'Support information already exists. Use the dependent settings after verification.',
                ]);
            }

            $values = $this->contentValues($payload);
            $profile = DependentSupportProfile::query()->create([
                'dependent_user_id' => $locked->child_user_id,
                ...$values,
                'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
                'purpose_acknowledged_at' => now(),
                'purpose_acknowledged_by_user_id' => $guardian->id,
                'created_by_user_id' => $guardian->id,
                'updated_by_user_id' => $guardian->id,
            ]);

            $this->audit(
                $locked->child_user_id,
                $guardian->id,
                $locked->id,
                DependentSupportInformationAudit::ACTION_CREATED,
                array_keys(array_filter($values, fn (?string $value): bool => $value !== null)),
            );

            return $profile->fresh();
        });
    }

    public function remove(
        DependentSupportProfile $profile,
        User $actor,
        string $expectedUpdatedAt,
    ): void {
        DB::transaction(function () use ($profile, $actor, $expectedUpdatedAt): void {
            $locked = DependentSupportProfile::query()->lockForUpdate()->findOrFail($profile->id);
            Gate::forUser($actor)->authorize('delete', $locked);
            $this->assertFresh($locked, $expectedUpdatedAt);

            $relationshipId = $this->relationshipIdFor($actor, (int) $locked->dependent_user_id);
            $dependentId = (int) $locked->dependent_user_id;
            $locked->delete();

            $this->audit(
                $dependentId,
                $actor->id,
                $relationshipId,
                DependentSupportInformationAudit::ACTION_REMOVED,
                null,
            );
        });
    }

    public function setGuardianAccess(
        ParentChildAccount $relationship,
        User $dependent,
        bool $enabled,
    ): ParentChildAccount {
        return DB::transaction(function () use ($relationship, $dependent, $enabled): ParentChildAccount {
            $locked = ParentChildAccount::query()
                ->accessEligible()
                ->whereKey($relationship->id)
                ->where('child_user_id', $dependent->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || (int) $dependent->id !== (int) $locked->child_user_id) {
                throw new AuthorizationException('You cannot change this guardian relationship.');
            }

            if ((bool) $locked->can_manage_support_information === $enabled) {
                return $locked;
            }

            $locked->update(['can_manage_support_information' => $enabled]);
            $this->audit(
                $dependent->id,
                $dependent->id,
                $locked->id,
                $enabled
                    ? DependentSupportInformationAudit::ACTION_PERMISSION_GRANTED
                    : DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
                null,
            );

            return $locked->fresh();
        });
    }

    public function resetGuardianAccessForLifecycle(
        ParentChildAccount $relationship,
        User $actor,
    ): void {
        if (! $relationship->can_manage_support_information) {
            return;
        }

        $relationship->update(['can_manage_support_information' => false]);
        $this->audit(
            $relationship->child_user_id,
            $actor->id,
            $relationship->id,
            DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
            null,
        );
    }

    private function assertFresh(DependentSupportProfile $profile, ?string $expected): void
    {
        if ($expected === null || $expected !== $profile->getRawOriginal('updated_at')) {
            throw ValidationException::withMessages([
                'support_information' => 'This information changed. Reload and review the latest version.',
            ]);
        }
    }

    private function contentValues(array $payload): array
    {
        return collect(DependentSupportProfile::CONTENT_FIELDS)
            ->mapWithKeys(function (string $field) use ($payload): array {
                $value = $payload[$field] ?? null;

                return [$field => is_string($value) && trim($value) !== '' ? trim($value) : null];
            })
            ->all();
    }

    private function changedFields(?DependentSupportProfile $profile, array $values): array
    {
        return collect($values)
            ->filter(fn (?string $value, string $field): bool => ! $profile || $profile->{$field} !== $value)
            ->keys()
            ->values()
            ->all();
    }

    private function relationshipIdFor(User $actor, int $dependentId): ?int
    {
        if ((int) $actor->id === $dependentId) {
            return null;
        }

        return ParentChildAccount::query()
            ->where('parent_user_id', $actor->id)
            ->where('child_user_id', $dependentId)
            ->value('id');
    }

    private function audit(
        int $dependentId,
        ?int $actorId,
        ?int $relationshipId,
        string $action,
        ?array $changedFields,
    ): void {
        DependentSupportInformationAudit::query()->create([
            'dependent_user_id' => $dependentId,
            'actor_user_id' => $actorId,
            'parent_child_account_id' => $relationshipId,
            'action' => $action,
            'changed_fields' => $changedFields,
            'occurred_at' => now(),
        ]);
    }
}
```

Import `Illuminate\Database\QueryException`. Wrap both transaction-return blocks in `save()` and `saveDuringRegistration()` with this exact duplicate translation, using the applicable dependent ID:

```php
try {
    return DB::transaction($operation);
} catch (QueryException $exception) {
    if ($this->isDependentUniqueConflict($exception, $dependentId)) {
        throw ValidationException::withMessages([
            'support_information' => 'This information changed. Reload and review the latest version.',
        ]);
    }

    throw $exception;
}
```

Assign the existing transaction closure to `$operation` and set `$dependentId` to `$dependent->id` in `save()` or `$relationship->child_user_id` in `saveDuringRegistration()`. Add this helper:

```php
private function isDependentUniqueConflict(QueryException $exception, int $dependentId): bool
{
    $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
    $message = strtolower($exception->getMessage());

    return in_array($sqlState, ['23000', '23505'], true)
        && str_contains($message, 'dependent_support_profiles')
        && DependentSupportProfile::query()
            ->where('dependent_user_id', $dependentId)
            ->exists();
}
```

This converts only the support table's unique conflict and rethrows every other database failure.

- [ ] **Step 4: Run the service tests**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportServiceTest.php tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php --testdox
vendor\bin\pint --test app/Services/DependentSupportInformationService.php tests/Feature/DependentSupport/DependentSupportServiceTest.php
```

Expected: CRUD, encryption, audit, permission isolation, hard deletion, and stale-write tests PASS.

- [ ] **Step 5: Commit Task 4**

```powershell
git add app/Services/DependentSupportInformationService.php tests/Feature/DependentSupport/DependentSupportServiceTest.php
git commit -m "feat: manage dependent support information"
```

### Task 5: Apply safe permission defaults and lifecycle resets

**Files:**

- Modify: `app/Services/Admin/UserRelationshipService.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Modify: `app/Services/GuardianRelationshipVerificationService.php`
- Create: `tests/Feature/DependentSupport/DependentSupportPermissionTest.php`
- Modify: `tests/Feature/GuardianRelationshipLifecycleTest.php`
- Modify: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`

**Interfaces:**

- Consumes: `DependentSupportInformationService::resetGuardianAccessForLifecycle()`.
- Enforces: admin-created and invitation-created relationships start false.
- Enforces: rejection, revocation, and deactivation clear only the affected relationship's permission; approval does not grant it.

- [ ] **Step 1: Write failing default and lifecycle tests**

In `DependentSupportPermissionTest`, construct two independently verified relationships for one dependent. Enable both flags, revoke or deactivate Guardian A through `GuardianRelationshipVerificationService`, and assert:

```php
$this->assertFalse($relationshipA->fresh()->can_manage_support_information);
$this->assertTrue($relationshipB->fresh()->can_manage_support_information);
$this->assertDatabaseHas('dependent_support_information_audits', [
    'dependent_user_id' => $dependent->id,
    'parent_child_account_id' => $relationshipA->id,
    'action' => DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
]);
```

Add cases for:

- final rejection;
- resubmission-required rejection;
- pending-claim closure;
- deactivation followed by reactivation request and approval;
- approval of an invitation-created relationship leaving the permission false;
- approval of a new-dependent relationship preserving its preconfigured true flag;
- an administrator-attached relationship starting false.

Extend `ParentChildInvitationFlowTest` with an assertion on the relationship produced by acceptance:

```php
$this->assertFalse($acceptedInvitation->parentChildAccount->can_manage_support_information);
```

Extend the existing multiple-guardian lifecycle test rather than creating a duplicate Phase 1 scenario.

- [ ] **Step 2: Run the focused tests and verify they fail**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportPermissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
```

Expected: FAIL because relationship factories/services do not set or reset the new permission.

- [ ] **Step 3: Set false in non-registration relationship creation paths**

Add this field to the attribute payload in `UserRelationshipService::attachParentChild()`:

```php
'can_manage_support_information' => false,
```

Add the same field to the `$payload` built by `ParentChildInvitationService` when an accepted invitation creates or restores a relationship:

```php
'can_manage_support_information' => false,
```

This explicit assignment is required on restored soft-deleted rows so stale historical permission values cannot return.

- [ ] **Step 4: Reset access inside every closing lifecycle transaction**

Inject `DependentSupportInformationService` into `GuardianRelationshipVerificationService`:

```php
public function __construct(
    private readonly GuardianRelationshipEvidenceService $evidence,
    private readonly GuardianInvitationConversationService $invitationConversations,
    private readonly DependentSupportInformationService $supportInformation,
) {}
```

Convert the rejection and close-pending update callbacks from arrow functions to closures. Before returning their relationship update arrays, call:

```php
$this->supportInformation->resetGuardianAccessForLifecycle($locked, $admin);
```

Make the same call inside `revoke()` after verifying the relationship is active, and inside `deactivate()` before setting `relationship_status` to inactive. In `requestReactivation()`, explicitly keep the field false in the update array:

```php
'can_manage_support_information' => false,
```

Do not call the reset helper from `approve()`. Approval must preserve the originating registration relationship's dormant true value and must leave every invitation/admin-created relationship false.

- [ ] **Step 5: Run the relationship and support permission matrix**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportPermissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/GuardianInvitationMessagingTest.php --testdox
vendor\bin\pint --test app/Services/Admin/UserRelationshipService.php app/Services/ParentChildInvitationService.php app/Services/GuardianRelationshipVerificationService.php tests/Feature/DependentSupport/DependentSupportPermissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php
```

Expected: support defaults and resets PASS, and Phase 1/Phase 2 lifecycle tests remain green.

- [ ] **Step 6: Commit Task 5**

```powershell
git add app/Services/Admin/UserRelationshipService.php app/Services/ParentChildInvitationService.php app/Services/GuardianRelationshipVerificationService.php tests/Feature/DependentSupport/DependentSupportPermissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php
git commit -m "feat: reset sensitive guardian access"
```

### Task 6: Insert the optional post-creation registration step

**Files:**

- Create: `app/Http/Controllers/Auth/DependentSupportRegistrationController.php`
- Modify: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Modify: `routes/auth.php`
- Create: `resources/views/auth/child/step6-support-information.blade.php`
- Modify: `resources/views/auth/child/done.blade.php`
- Create: `tests/Feature/DependentSupport/DependentSupportRegistrationTest.php`
- Modify: `tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php`

**Interfaces:**

- Produces: session key `pending_child_support_setup` containing only dependent ID, relationship ID, and expiry timestamp.
- Produces routes `parent.create-child.support-information`, `.store`, and `.skip`.
- Consumes: `saveDuringRegistration()` and the Task 3 request.

- [ ] **Step 1: Write failing registration-flow tests**

In `DependentSupportRegistrationTest`, use an approved guardian and a pending relationship to exercise the new routes. Seed this marker:

```php
$marker = [
    'dependent_user_id' => $dependent->id,
    'parent_child_account_id' => $relationship->id,
    'expires_at' => now()->addMinutes(30)->timestamp,
];
```

Cover:

- GET renders the purpose notice and optional language with `Cache-Control: private, no-store`;
- POST saves encrypted information and redirects to the existing done route;
- skip creates no record and redirects to done;
- the serialized session contains none of the submitted support text;
- expired, mismatched, unrelated, rejected, revoked, and missing markers are denied without revealing record existence;
- abandoning the optional route leaves the dependent and relationship rows intact;
- successful save or skip removes the marker;
- a direct completion request clears a leftover marker defensively.

Update the existing full registration test so relationship submission expects the support-step redirect and asserts the originating relationship has `can_manage_support_information = true` while still pending.

- [ ] **Step 2: Run registration tests and verify they fail**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportRegistrationTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php --testdox
```

Expected: FAIL because the routes, controller, marker, and view do not exist.

- [ ] **Step 3: Add the registration routes**

Inside the existing `['verified', 'guardian.verified']` create-child group in `routes/auth.php`, import `DependentSupportRegistrationController` and add these routes before the done route:

```php
Route::get('parent/create-child/support-information', [DependentSupportRegistrationController::class, 'show'])
    ->name('parent.create-child.support-information');
Route::post('parent/create-child/support-information', [DependentSupportRegistrationController::class, 'store'])
    ->name('parent.create-child.support-information.store');
Route::post('parent/create-child/support-information/skip', [DependentSupportRegistrationController::class, 'skip'])
    ->name('parent.create-child.support-information.skip');
```

- [ ] **Step 4: Create the registration controller**

Create `app/Http/Controllers/Auth/DependentSupportRegistrationController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DependentSupportRegistrationController extends Controller
{
    public const SESSION_KEY = 'pending_child_support_setup';
    public const MARKER_MINUTES = 30;

    public function __construct(
        private readonly DependentSupportInformationService $supportInformation,
    ) {}

    public function show(Request $request): Response
    {
        [$relationship, $dependent] = $this->context($request);

        return response()
            ->view('auth.child.step6-support-information', compact('relationship', 'dependent'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(StoreDependentSupportInformationRequest $request): RedirectResponse
    {
        [$relationship] = $this->context($request);
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);

        if ($request->boolean('has_relevant_support_information')) {
            $this->supportInformation->saveDuringRegistration(
                $relationship,
                $guardian,
                $request->supportPayload(),
            );
        }

        return $this->finish($request, $request->boolean('has_relevant_support_information'));
    }

    public function skip(Request $request): RedirectResponse
    {
        $this->context($request);

        return $this->finish($request, false);
    }

    private function context(Request $request): array
    {
        $guardian = $request->user();
        $marker = $request->session()->get(self::SESSION_KEY);

        abort_unless($guardian instanceof User
            && $guardian->status === User::STATUS_ACTIVE
            && $guardian->isParentVerificationApproved()
            && $guardian->hasCompletedGuardianOnboarding()
            && is_array($marker)
            && (int) ($marker['expires_at'] ?? 0) >= now()->timestamp, 404);

        $relationship = ParentChildAccount::query()
            ->whereKey((int) ($marker['parent_child_account_id'] ?? 0))
            ->where('parent_user_id', $guardian->id)
            ->where('child_user_id', (int) ($marker['dependent_user_id'] ?? 0))
            ->firstOrFail();

        abort_if(in_array($relationship->relationship_status, [
            ParentChildAccount::STATUS_REJECTED,
            ParentChildAccount::STATUS_REVOKED,
            ParentChildAccount::STATUS_INACTIVE,
        ], true), 404);

        return [$relationship, $relationship->child()->firstOrFail()];
    }

    private function finish(Request $request, bool $saved): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('parent.create-child.done')->with(
            'support_information_result',
            $saved ? 'saved' : 'skipped',
        );
    }
}
```

- [ ] **Step 5: Redirect the existing transaction without coupling the data**

In `ParentRegistrationController::createChildAccountFromSession()`, set this field only on the relationship created with the dependent:

```php
'can_manage_support_information' => true,
```

After the transaction and admin notification, continue forgetting `child_step1`, `child_step2`, `child_step3`, and `pending_child_registration`. Add the marker and redirect:

```php
session([
    DependentSupportRegistrationController::SESSION_KEY => [
        'dependent_user_id' => $child->id,
        'parent_child_account_id' => $verification->id,
        'expires_at' => now()->addMinutes(DependentSupportRegistrationController::MARKER_MINUTES)->timestamp,
    ],
    'child_created_name' => $step1['first_name'],
    'child_registration_result' => [
        'status' => VerificationStatus::Pending->value,
    ],
]);

return redirect()->route('parent.create-child.support-information');
```

No support value is accepted by or passed into `createChildAccountFromSession()` or `GuardianRelationshipVerificationService`.

In `childDone()`, add `DependentSupportRegistrationController::SESSION_KEY` to the keys forgotten after reading the completion state.

- [ ] **Step 6: Build the optional page**

Create `resources/views/auth/child/step6-support-information.blade.php` using the existing auth split layout and six-step explicit stepper. The functional form contract is:

```blade
<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold text-white">Optional support information</h2>
            <p class="max-w-xs text-lg text-white/80">Share only what is relevant to learning, accessibility, participation, or safety.</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Dependent Info', 'active' => false, 'done' => true],
        ['label' => 'Location', 'active' => false, 'done' => true],
        ['label' => 'Credentials', 'active' => false, 'done' => true],
        ['label' => 'Validation', 'active' => false, 'done' => true],
        ['label' => 'Relationship', 'active' => false, 'done' => true],
        ['label' => 'Support', 'active' => true, 'done' => false],
    ]" />

    <section x-data="{ hasInformation: false }" class="space-y-5">
        <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
            <h1 class="text-2xl font-bold text-purple-950">Relevant Health & Support Information</h1>
            <p class="mt-2 text-sm text-gray-700">This is optional. It helps you and an authorized guardian record relevant support needs. It is not used for diagnosis, treatment, or Guardian-Dependent approval.</p>
            <p class="mt-2 text-xs text-gray-600">Do not use this page for emergencies or upload medical documents.</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('parent.create-child.support-information.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="has_relevant_support_information" :value="hasInformation ? 1 : 0">
            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-4">
                <input type="checkbox" x-model="hasInformation" class="mt-1 rounded border-gray-300">
                <span><strong>I want to provide relevant support information.</strong><span class="mt-1 block text-xs text-gray-600">You can skip this now and add information later.</span></span>
            </label>

            <div x-cloak x-show="hasInformation" class="space-y-4">
                <label class="block text-sm font-medium">Relevant health considerations
                    <textarea name="relevant_health_considerations" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="block text-sm font-medium">Accessibility or learning-support needs
                    <textarea name="accessibility_support_needs" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="block text-sm font-medium">Additional relevant participation or safety information
                    <textarea name="additional_relevant_information" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
                    <input type="checkbox" name="purpose_acknowledged" value="1" class="mt-0.5 rounded border-amber-300">
                    <span>I understand the stated purpose and choose to provide only information relevant to platform support.</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded-xl bg-purple-700 px-6 py-3 font-semibold text-white">Continue</button>
        </form>

        <form method="POST" action="{{ route('parent.create-child.support-information.skip') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-gray-300 px-6 py-3 font-semibold text-gray-700">Skip for now</button>
        </form>
    </section>
</x-auth-split-layout>
```

Do not use `old()` for any of the three sensitive textareas. Update the done-page stepper to show the Support step completed and add a short statement that support information was saved or skipped without echoing content.

- [ ] **Step 7: Run registration and existing relationship evidence tests**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportRegistrationTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php --testdox
pnpm.cmd build
vendor\bin\pint --test app/Http/Controllers/Auth/DependentSupportRegistrationController.php app/Http/Controllers/Auth/ParentRegistrationController.php routes/auth.php tests/Feature/DependentSupport/DependentSupportRegistrationTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php
```

Expected: optional save/skip and existing evidence upload tests PASS; Vite builds successfully.

- [ ] **Step 8: Commit Task 6**

```powershell
git add app/Http/Controllers/Auth/DependentSupportRegistrationController.php app/Http/Controllers/Auth/ParentRegistrationController.php routes/auth.php resources/views/auth/child/step6-support-information.blade.php resources/views/auth/child/done.blade.php tests/Feature/DependentSupport/DependentSupportRegistrationTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php
git commit -m "feat: add optional dependent support step"
```

### Task 7: Add dependent self-service management

**Files:**

- Create: `app/Http/Controllers/Learner/DependentSupportInformationController.php`
- Modify: `routes/web.php`
- Create: `resources/views/dependent-support-information/edit.blade.php`
- Modify: `resources/views/learner/dashboard.blade.php`
- Create: `tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php`

**Interfaces:**

- Produces learner routes `learner.support-information.edit`, `.save`, and `.destroy`.
- Consumes `DependentSupportInformationService::save()` and `remove()`.
- Produces the shared editor later reused by the guardian controller.

- [ ] **Step 1: Write failing learner HTTP tests**

Cover:

- a dependent can open an empty editor and create a record;
- a dependent can view decrypted content through the authorized page;
- update requires the current raw `updated_at` version;
- stale update returns validation feedback and preserves newer content;
- delete requires the current version and hard-deletes the row;
- an administrator, instructor, unrelated learner, guest, and suspended user cannot use these routes as a shortcut to another dependent;
- GET sends `Cache-Control: private, no-store`;
- HTML escapes a synthetic `<script>` marker;
- the dashboard link contains no content or content-presence badge.

Use this update payload shape:

```php
[
    'has_relevant_support_information' => '1',
    'accessibility_support_needs' => 'Updated synthetic support',
    'purpose_acknowledged' => '1',
    'expected_updated_at' => $profile->getRawOriginal('updated_at'),
]
```

- [ ] **Step 2: Run the HTTP test and verify it fails**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php --filter=learner --testdox
```

Expected: FAIL because the self-service routes, controller, and editor do not exist.

- [ ] **Step 3: Add learner routes**

Inside the existing authenticated `learn` / `learner.` / `profile.completed` group in `routes/web.php`:

```php
Route::get('/my-support-information', [DependentSupportInformationController::class, 'edit'])
    ->name('support-information.edit');
Route::put('/my-support-information', [DependentSupportInformationController::class, 'save'])
    ->name('support-information.save');
Route::delete('/my-support-information', [DependentSupportInformationController::class, 'destroy'])
    ->name('support-information.destroy');
```

Alias the import to avoid collision with the guardian controller added in Task 8.

- [ ] **Step 4: Implement the learner controller**

Create `app/Http/Controllers/Learner/DependentSupportInformationController.php`:

```php
<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Models\DependentSupportProfile;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DependentSupportInformationController extends Controller
{
    public function __construct(
        private readonly DependentSupportInformationService $supportInformation,
    ) {}

    public function edit(Request $request): Response
    {
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);
        $profile = $dependent->dependentSupportProfile()->first();

        $profile
            ? Gate::forUser($dependent)->authorize('view', $profile)
            : Gate::forUser($dependent)->authorize('create', [DependentSupportProfile::class, $dependent]);

        return response()->view('dependent-support-information.edit', [
            'dependent' => $dependent,
            'profile' => $profile,
            'saveRoute' => route('learner.support-information.save'),
            'deleteRoute' => route('learner.support-information.destroy'),
            'backRoute' => route('learner.dashboard'),
            'viewerContext' => 'dependent',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(StoreDependentSupportInformationRequest $request): RedirectResponse
    {
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);

        if (! $request->boolean('has_relevant_support_information')) {
            return back()->with('info', 'No support information was saved.');
        }

        $this->supportInformation->save(
            $dependent,
            $dependent,
            $request->supportPayload(),
            $request->validated('expected_updated_at'),
        );

        return redirect()->route('learner.support-information.edit')
            ->with('success', 'Your support information was saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expected_updated_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'confirm_removal' => ['accepted'],
        ]);
        $dependent = $request->user();
        abort_unless($dependent instanceof User, 403);
        $profile = $dependent->dependentSupportProfile()->firstOrFail();

        $this->supportInformation->remove($profile, $dependent, $validated['expected_updated_at']);

        return redirect()->route('learner.support-information.edit')
            ->with('success', 'Your support information was removed.');
    }
}
```

- [ ] **Step 5: Create the shared editor**

Create `resources/views/dependent-support-information/edit.blade.php`. Use `layouts.learner-app`, escaped Blade values, the same purpose statement as registration, the three 1,000-character textareas, acknowledgement checkbox, hidden `has_relevant_support_information=1`, and this concurrency value:

```blade
@if($profile)
    <input type="hidden" name="expected_updated_at" value="{{ $profile->getRawOriginal('updated_at') }}">
@endif
```

Render values directly from the authorized model, never from `old()`:

```blade
<textarea name="relevant_health_considerations" maxlength="1000">{{ $profile?->relevant_health_considerations }}</textarea>
<textarea name="accessibility_support_needs" maxlength="1000">{{ $profile?->accessibility_support_needs }}</textarea>
<textarea name="additional_relevant_information" maxlength="1000">{{ $profile?->additional_relevant_information }}</textarea>
```

Use a confirmation block controlled by Alpine for removal. The DELETE form must include the version and explicit confirmation:

```blade
@if($profile)
    <form method="POST" action="{{ $deleteRoute }}" x-data="{ confirming: false }">
        @csrf
        @method('DELETE')
        <input type="hidden" name="expected_updated_at" value="{{ $profile->getRawOriginal('updated_at') }}">
        <button type="button" @click="confirming = true" class="text-sm font-semibold text-red-700">Remove all information</button>
        <div x-cloak x-show="confirming" role="alertdialog" aria-modal="true" aria-labelledby="remove-support-title" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4">
            <h2 id="remove-support-title" class="font-semibold text-red-900">Remove all Health & Support Information?</h2>
            <p class="mt-1 text-sm text-red-800">This removes the active encrypted record. Account backups follow the platform retention policy.</p>
            <input type="hidden" name="confirm_removal" value="1">
            <button type="submit" class="mt-3 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Confirm removal</button>
            <button type="button" @click="confirming = false" class="ml-2 text-sm font-semibold text-gray-700">Cancel</button>
        </div>
    </form>
@endif
```

Show last-updated time and the updater's name only when authorized. Do not show an audit-history list or content-presence indicator.

Add a neutral “Health & Support Information” link to `resources/views/learner/dashboard.blade.php`. It must not state whether information exists.

- [ ] **Step 6: Run learner HTTP tests and build**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php --filter=learner --testdox
pnpm.cmd build
vendor\bin\pint --test app/Http/Controllers/Learner/DependentSupportInformationController.php routes/web.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php
```

Expected: learner CRUD, escaped output, no-store, and stale-write tests PASS; Vite builds.

- [ ] **Step 7: Commit Task 7**

```powershell
git add app/Http/Controllers/Learner/DependentSupportInformationController.php routes/web.php resources/views/dependent-support-information/edit.blade.php resources/views/learner/dashboard.blade.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php
git commit -m "feat: let dependents manage support details"
```

### Task 8: Add exact-relationship guardian management

**Files:**

- Create: `app/Http/Controllers/Parent/DependentSupportInformationController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/parent/children/index.blade.php`
- Modify: `tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php`
- Modify: `tests/Feature/Parent/ParentChildrenActionsUiTest.php`

**Interfaces:**

- Produces routes `parent.children.support-information.edit`, `.save`, and `.destroy`.
- Consumes the Task 2 policy and Task 4 service.
- Reuses the Task 7 editor without creating a second health-information UI.

- [ ] **Step 1: Write failing guardian HTTP tests**

Add cases for:

- an active verified guardian with permission can open and update the shared record;
- an eligible guardian with permission can create the record when none exists;
- the page uses the same dependent-owned row rather than a guardian-specific copy;
- another verified guardian without permission receives 403;
- pending, rejected, revoked, inactive, suspended, or soft-deleted relationships receive 403;
- an instructor, administrator, unrelated user, and guest receive 403 or the normal login redirect;
- removing through Guardian A hard-deletes the shared record but does not modify Guardian B's relationship;
- stale guardian updates and deletes are rejected;
- GET has `private, no-store`;
- the children index shows the action only for the exact eligible permission-bearing relationship.

- [ ] **Step 2: Run the guardian tests and verify they fail**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php --testdox
```

Expected: FAIL because no guardian support routes or controller exist.

- [ ] **Step 3: Add guardian routes**

Inside the existing `parent.` group with `verified` and `guardian.verified` middleware:

```php
Route::get('/children/{child}/support-information', [ParentDependentSupportInformationController::class, 'edit'])
    ->name('children.support-information.edit');
Route::put('/children/{child}/support-information', [ParentDependentSupportInformationController::class, 'save'])
    ->name('children.support-information.save');
Route::delete('/children/{child}/support-information', [ParentDependentSupportInformationController::class, 'destroy'])
    ->name('children.support-information.destroy');
```

- [ ] **Step 4: Implement the guardian controller**

Create `app/Http/Controllers/Parent/DependentSupportInformationController.php` with the same three actions as the learner controller, but always use the authenticated guardian as actor and the route-bound child as dependent:

```php
<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Models\DependentSupportProfile;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class DependentSupportInformationController extends Controller
{
    public function __construct(
        private readonly DependentSupportInformationService $supportInformation,
    ) {}

    public function edit(Request $request, User $child): Response
    {
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);
        $profile = $child->dependentSupportProfile()->first();

        $profile
            ? Gate::forUser($guardian)->authorize('view', $profile)
            : Gate::forUser($guardian)->authorize('create', [DependentSupportProfile::class, $child]);

        return response()->view('dependent-support-information.edit', [
            'dependent' => $child,
            'profile' => $profile,
            'saveRoute' => route('parent.children.support-information.save', $child),
            'deleteRoute' => route('parent.children.support-information.destroy', $child),
            'backRoute' => route('parent.children.index'),
            'viewerContext' => 'guardian',
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(StoreDependentSupportInformationRequest $request, User $child): RedirectResponse
    {
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);

        if (! $request->boolean('has_relevant_support_information')) {
            return back()->with('info', 'No support information was saved.');
        }

        $this->supportInformation->save(
            $child,
            $guardian,
            $request->supportPayload(),
            $request->validated('expected_updated_at'),
        );

        return redirect()->route('parent.children.support-information.edit', $child)
            ->with('success', 'Support information was saved.');
    }

    public function destroy(Request $request, User $child): RedirectResponse
    {
        $validated = $request->validate([
            'expected_updated_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'confirm_removal' => ['accepted'],
        ]);
        $guardian = $request->user();
        abort_unless($guardian instanceof User, 403);
        $profile = $child->dependentSupportProfile()->firstOrFail();

        $this->supportInformation->remove($profile, $guardian, $validated['expected_updated_at']);

        return redirect()->route('parent.children.support-information.edit', $child)
            ->with('success', 'Support information was removed.');
    }
}
```

Do not authorize via the general `ParentChildPolicy::view`, because that policy checks progress permission rather than the sensitive permission.

- [ ] **Step 5: Add the permission-aware guardian action**

The `User::children()` pivot list already gains `can_manage_support_information` in Task 1. In `resources/views/parent/children/index.blade.php`, show this action only when the row itself is active, verified, and permission-enabled:

```blade
@if(
    $child->pivot?->relationship_status === \App\Models\ParentChildAccount::STATUS_ACTIVE
    && $child->pivot?->relationship_verified_status === \App\Models\ParentChildAccount::VERIFICATION_VERIFIED
    && $child->pivot?->relationship_verified_at
    && $child->pivot?->can_manage_support_information
)
    <a href="{{ route('parent.children.support-information.edit', $child) }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-purple-700 hover:text-purple-900">
        Health & Support Information
    </a>
@endif
```

The controller policy remains the security boundary; hiding the link is only a UI affordance.

- [ ] **Step 6: Run guardian, parent UI, and policy tests**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php --testdox
pnpm.cmd build
vendor\bin\pint --test app/Http/Controllers/Parent/DependentSupportInformationController.php routes/web.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php
```

Expected: guardian allow/deny matrix and exact-row UI visibility PASS; Vite builds.

- [ ] **Step 7: Commit Task 8**

```powershell
git add app/Http/Controllers/Parent/DependentSupportInformationController.php routes/web.php resources/views/parent/children/index.blade.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php
git commit -m "feat: allow authorized guardian support access"
```

### Task 9: Add dependent-controlled per-guardian access

**Files:**

- Modify: `app/Http/Controllers/Learner/ParentVisibilityController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/learner/parent/index.blade.php`
- Modify: `tests/Feature/DependentSupport/DependentSupportPermissionTest.php`

**Interfaces:**

- Produces route `learner.parent.support-information-access.update`.
- Consumes `UpdateGuardianSupportAccessRequest::enabled()` and `DependentSupportInformationService::setGuardianAccess()`.
- Operates on one route-bound `ParentChildAccount`, not a guardian user ID.

- [ ] **Step 1: Write failing permission-controller tests**

Cover:

- a dependent grants Guardian A and only Guardian A gains access;
- revoking Guardian A leaves Guardian B unchanged;
- the relationship must belong to the authenticated dependent;
- the relationship must remain active, verified, and access-eligible;
- a guardian, administrator, instructor, unrelated learner, guest, and suspended user cannot change the flag;
- a malformed boolean is rejected;
- the page clearly explains that access includes viewing, editing, and removal;
- no support content or content-presence state appears on the guardian list.

- [ ] **Step 2: Run permission tests and verify they fail**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportPermissionTest.php --testdox
```

Expected: FAIL because no permission endpoint or control exists.

- [ ] **Step 3: Add the exact-relationship route and controller method**

Inside the learner route group:

```php
Route::patch('/my-parent/{parentChildAccount}/support-information-access', [ParentVisibilityController::class, 'updateSupportInformationAccess'])
    ->name('parent.support-information-access.update');
```

Add to `ParentVisibilityController`:

```php
public function updateSupportInformationAccess(
    UpdateGuardianSupportAccessRequest $request,
    ParentChildAccount $parentChildAccount,
    DependentSupportInformationService $supportInformation,
): RedirectResponse {
    $dependent = $request->user();
    abort_unless($dependent instanceof User, 403);

    $supportInformation->setGuardianAccess(
        $parentChildAccount,
        $dependent,
        $request->enabled(),
    );

    return redirect()->route('learner.parent.index')->with(
        'success',
        $request->enabled()
            ? 'Guardian support-information access enabled.'
            : 'Guardian support-information access disabled.',
    );
}
```

Import the request, model, service, user, and redirect-response types. Do not add admin authorization or reuse the admin relationship permission endpoint.

- [ ] **Step 4: Add the per-relationship control**

Inside each guardian card in `resources/views/learner/parent/index.blade.php`:

```blade
<form method="POST" action="{{ route('learner.parent.support-information-access.update', $parentLink) }}" class="mt-4 rounded-xl border border-purple-100 bg-purple-50 p-4">
    @csrf
    @method('PATCH')
    <input type="hidden" name="enabled" value="0">
    <label class="flex items-start justify-between gap-4">
        <span>
            <span class="block text-sm font-semibold text-purple-950">Health & Support Information access</span>
            <span class="mt-1 block text-xs text-gray-600">Allows this guardian to view, edit, and remove your optional support information.</span>
        </span>
        <input type="checkbox" name="enabled" value="1" class="mt-1 rounded border-purple-300"
               @checked($parentLink->can_manage_support_information)
               onchange="this.form.submit()">
    </label>
</form>
```

Retain the guardian identity and relationship context already shown by the page. Do not show support content, field names, or whether a support profile exists.

- [ ] **Step 5: Run permission, visibility, and authorization tests**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportPermissionTest.php tests/Feature/DependentSupport/DependentSupportAuthorizationTest.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php --testdox
pnpm.cmd build
vendor\bin\pint --test app/Http/Controllers/Learner/ParentVisibilityController.php routes/web.php tests/Feature/DependentSupport/DependentSupportPermissionTest.php
```

Expected: each relationship's permission changes independently, denial paths remain closed, and Vite builds.

- [ ] **Step 6: Commit Task 9**

```powershell
git add app/Http/Controllers/Learner/ParentVisibilityController.php routes/web.php resources/views/learner/parent/index.blade.php tests/Feature/DependentSupport/DependentSupportPermissionTest.php
git commit -m "feat: let dependents control guardian access"
```

### Task 10: Add privacy-safe notifications after commit

**Files:**

- Create: `app/Notifications/DependentSupportInformationChangedNotification.php`
- Create: `app/Notifications/GuardianSupportAccessChangedNotification.php`
- Modify: `app/Services/DependentSupportInformationService.php`
- Create: `tests/Feature/DependentSupport/DependentSupportNotificationTest.php`

**Interfaces:**

- Produces database-only notification type `dependent_support_information_changed`.
- Produces database-only notification type `guardian_support_access_changed`.
- Adds after-commit dispatch to successful guardian content actions and dependent permission changes.

- [ ] **Step 1: Write failing notification privacy tests**

Cover:

- a guardian create/update/remove sends one notification to the dependent;
- a dependent self-change sends no duplicate notification;
- granting or revoking permission sends one notification to the affected guardian;
- a failed or stale transaction sends no notification;
- registration submission sends a privacy-safe notice to the newly created dependent;
- serialized notification data contains no submitted text, field names, notice acknowledgement, diagnosis wording, email, or audit payload;
- notification channels equal `['database']`;
- a revoked guardian cannot use an older enabled-access deep link.

Use a unique synthetic marker and assert it is absent from every database notification payload:

```php
$marker = 'PRIVATE-SYNTHETIC-MARKER-42';
$payloads = $dependent->notifications()->get()->pluck('data')->all();
$this->assertStringNotContainsString($marker, json_encode($payloads, JSON_THROW_ON_ERROR));
$this->assertStringNotContainsString('accessibility_support_needs', json_encode($payloads, JSON_THROW_ON_ERROR));
```

- [ ] **Step 2: Run notification tests and verify they fail**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportNotificationTest.php --testdox
```

Expected: FAIL because notification classes and after-commit dispatch do not exist.

- [ ] **Step 3: Create the dependent notification**

Create `app/Notifications/DependentSupportInformationChangedNotification.php`:

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DependentSupportInformationChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $actor,
        private readonly string $action,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $verb = match ($this->action) {
            'created' => 'added',
            'removed' => 'removed',
            default => 'updated',
        };

        return [
            'type' => 'dependent_support_information_changed',
            'title' => 'Support information '.$verb,
            'message' => $this->actor->name.' '.$verb.' your optional Health & Support Information.',
            'actor_user_id' => $this->actor->id,
            'action' => $this->action,
            'action_url' => route('learner.support-information.edit'),
            'severity' => 'info',
        ];
    }
}
```

- [ ] **Step 4: Create the guardian-access notification**

Create `app/Notifications/GuardianSupportAccessChangedNotification.php`:

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuardianSupportAccessChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $dependent,
        private readonly bool $enabled,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'guardian_support_access_changed',
            'title' => $this->enabled ? 'Support access enabled' : 'Support access disabled',
            'message' => $this->dependent->name.($this->enabled
                ? ' allowed you to manage their optional Health & Support Information.'
                : ' removed your access to their optional Health & Support Information.'),
            'dependent_user_id' => $this->dependent->id,
            'enabled' => $this->enabled,
            'action_url' => $this->enabled
                ? route('parent.children.support-information.edit', $this->dependent)
                : route('parent.children.index'),
            'severity' => 'info',
        ];
    }
}
```

Neither class accepts a support profile or content payload.

- [ ] **Step 5: Dispatch only after successful commits**

In `DependentSupportInformationService`, import the two notifications, `Log`, and `Throwable`. Add these helpers:

```php
private function notifyDependentAfterCommit(int $dependentId, int $actorId, string $action): void
{
    if ($dependentId === $actorId) {
        return;
    }

    DB::afterCommit(function () use ($dependentId, $actorId, $action): void {
        try {
            $dependent = User::query()->find($dependentId);
            $actor = User::query()->find($actorId);

            if ($dependent && $actor) {
                $dependent->notify(new DependentSupportInformationChangedNotification($actor, $action));
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to send dependent support-information notification.', [
                'dependent_user_id' => $dependentId,
                'actor_user_id' => $actorId,
                'action' => $action,
                'exception' => $exception::class,
            ]);
        }
    });
}

private function notifyGuardianAccessAfterCommit(
    int $guardianId,
    int $dependentId,
    bool $enabled,
): void {
    DB::afterCommit(function () use ($guardianId, $dependentId, $enabled): void {
        try {
            $guardian = User::query()->find($guardianId);
            $dependent = User::query()->find($dependentId);

            if ($guardian && $dependent) {
                $guardian->notify(new GuardianSupportAccessChangedNotification($dependent, $enabled));
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to send guardian support-access notification.', [
                'guardian_user_id' => $guardianId,
                'dependent_user_id' => $dependentId,
                'enabled' => $enabled,
                'exception' => $exception::class,
            ]);
        }
    });
}
```

Call `notifyDependentAfterCommit()` after the audit is written in `save()`, `saveDuringRegistration()`, and `remove()`. Pass only IDs and `created`, `updated`, or `removed`.

Call `notifyGuardianAccessAfterCommit()` only when `setGuardianAccess()` actually changes the boolean. Do not call it from `resetGuardianAccessForLifecycle()` because the existing relationship-status notification already explains administrative or party-driven closure.

- [ ] **Step 6: Run notification and notification-center regressions**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportNotificationTest.php tests/Feature/Learner/LearnerNotificationReadFlowTest.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php --testdox
vendor\bin\pint --test app/Notifications/DependentSupportInformationChangedNotification.php app/Notifications/GuardianSupportAccessChangedNotification.php app/Services/DependentSupportInformationService.php tests/Feature/DependentSupport/DependentSupportNotificationTest.php
```

Expected: database-only notifications, content-free payloads, after-commit behavior, and existing notification routing PASS.

- [ ] **Step 7: Commit Task 10**

```powershell
git add app/Notifications/DependentSupportInformationChangedNotification.php app/Notifications/GuardianSupportAccessChangedNotification.php app/Services/DependentSupportInformationService.php tests/Feature/DependentSupport/DependentSupportNotificationTest.php
git commit -m "feat: notify support access changes safely"
```

### Task 11: Prove verification isolation and prevent accidental exposure

**Files:**

- Create: `tests/Feature/DependentSupport/DependentSupportVerificationIsolationTest.php`
- Modify: `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`
- Modify: `tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php`

**Interfaces:**

- Consumes the complete Phase 3 feature.
- Produces regression evidence that support information is absent from verification, invitation, chat, instructor, notification, and broad profile paths.
- Application files are changed in this task only if a failing test identifies an actual accidental load or disclosure.

- [ ] **Step 1: Write relationship-decision isolation tests**

In `DependentSupportVerificationIsolationTest`, create two equivalent under-review relationships with valid synthetic evidence. Give one dependent a support profile and leave the other without one. Capture queries only while calling relationship approval:

```php
$queries = [];
DB::listen(function ($query) use (&$queries): void {
    $queries[] = strtolower($query->sql);
});

$approvedWithSupport = $service->approve($relationshipWithSupport, $admin);
$approvedWithoutSupport = $service->approve($relationshipWithoutSupport, $admin);

$this->assertSame($approvedWithoutSupport->relationship_status, $approvedWithSupport->relationship_status);
$this->assertSame($approvedWithoutSupport->relationship_verified_status, $approvedWithSupport->relationship_verified_status);
$this->assertFalse(collect($queries)->contains(
    fn (string $sql): bool => str_contains($sql, 'dependent_support_profiles'),
));
```

Add equivalent rejection coverage. Assert the encrypted profile values and timestamps are unchanged after approve/reject.

- [ ] **Step 2: Write admin, instructor, invitation, and chat exposure tests**

Use a unique support marker and assert it is absent from:

- `admin.parent-verifications.relationships.show` HTML;
- the centralized verification index;
- relationship verification audit notes;
- guardian evidence document metadata;
- Phase 2 invitation detail and guardian profile HTML;
- Phase 2 invitation conversation payload/history;
- parent progress and quiz HTML;
- instructor learner/enrollment pages reachable in the existing test suite;
- learner and guardian notification database payloads other than the intentionally generic support notification.

Also POST the three support field names to relationship approval and invitation response endpoints and assert the relationship decision is based only on the existing request allowlist. The support profile must remain unchanged.

- [ ] **Step 3: Run the new tests and observe any real failures**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport/DependentSupportVerificationIsolationTest.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Parent/GuardianInvitationMessagingTest.php --testdox
```

Expected: either PASS immediately because the architecture is isolated, or FAIL at the exact existing query/view that accidentally loaded or rendered the support relation.

- [ ] **Step 4: If a test exposes data, remove only the proven exposure path**

Apply the minimum correction at the shared source identified by the failing test:

- remove `dependentSupportProfile` from the offending eager-load or serializer;
- replace broad `toArray()` use with the existing relationship/profile field allowlist;
- keep support fields out of request `only()` arrays;
- keep notification construction restricted to IDs and action names;
- do not add redaction middleware or a second serialization framework.

Rerun the exact failing test after each correction. Do not make speculative edits when all isolation tests already pass.

- [ ] **Step 5: Run the complete Phase 3 security matrix**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport --testdox
php vendor/bin/phpunit --do-not-cache-result tests/Feature/GuardianRelationshipSchemaTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/ParentChildMonitoringTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php --testdox
```

Expected: all Phase 3 tests and the focused Phase 1/Phase 2 relationship, invitation, monitoring, and suspension matrix PASS.

- [ ] **Step 6: Commit Task 11**

If no application file needed correction:

```powershell
git add tests/Feature/DependentSupport/DependentSupportVerificationIsolationTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/DependentSupport/DependentSupportHttpFlowTest.php
git commit -m "test: prove support data stays isolated"
```

If an application file needed a proven privacy correction, stage that exact file with the tests and use:

```powershell
git commit -m "fix: prevent dependent support disclosure"
```

### Task 12: Run end-to-end QA and write the verification report

**Files:**

- Create: `docs/superpowers/verification/2026-09-09-guardian-dependent-health-support-information-e2e.md`
- Modify only if a test proves a defect: files already named in Tasks 1-11.

**Interfaces:**

- Produces auditable evidence for the Phase 3 acceptance criteria.
- Records known baseline failures separately from Phase 3 results.

- [ ] **Step 1: Confirm the migration works forward and backward on a disposable test database**

Use the configured test database only. Never run rollback against the developer's normal database.

```powershell
php artisan migrate:fresh --env=testing --force
php artisan migrate:rollback --env=testing --step=1 --force
php artisan migrate --env=testing --force
```

Expected: all three commands exit 0; the Phase 3 migration rolls back and reapplies cleanly.

- [ ] **Step 2: Run focused tests, formatting, and frontend build**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport --testdox
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/GuardianRelationshipSchemaTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/ParentChildMonitoringTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Learner/LearnerNotificationReadFlowTest.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php --testdox
vendor\bin\pint --test app/Models/DependentSupportProfile.php app/Models/DependentSupportInformationAudit.php app/Models/User.php app/Models/ParentChildAccount.php app/Policies/DependentSupportProfilePolicy.php app/Providers/AppServiceProvider.php app/Http/Requests/DependentSupport app/Services/DependentSupportInformationService.php app/Notifications/DependentSupportInformationChangedNotification.php app/Notifications/GuardianSupportAccessChangedNotification.php app/Http/Controllers/Auth/DependentSupportRegistrationController.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Controllers/Learner/DependentSupportInformationController.php app/Http/Controllers/Learner/ParentVisibilityController.php app/Http/Controllers/Parent/DependentSupportInformationController.php routes/auth.php routes/web.php database/migrations/2026_09_09_100000_create_dependent_support_information.php tests/Feature/DependentSupport
pnpm.cmd build
```

Expected: focused PHPUnit commands exit 0, Pint exits 0, and Vite exits 0.

- [ ] **Step 3: Run the full PHPUnit suite and compare with the Phase 2 baseline**

```powershell
php vendor/bin/phpunit --do-not-cache-result
```

Expected target: exit 0. If the environment still reproduces Phase 2 baseline limitations, the Phase 3 report must show exact counts and prove no new Phase 3 or focused-regression failure was introduced. The recorded Phase 2 baseline at `aa00211` was 1,285 tests and 6,214 assertions with 13 errors and 4 failures, including missing GD and unrelated/order-sensitive failures. Do not silently classify a new failure as baseline.

- [ ] **Step 4: Perform browser QA with synthetic data**

Use the in-app browser when available. Record observed results for:

1. Create a dependent through all existing steps and confirm relationship evidence submits before the optional page.
2. Skip support information and confirm account/relationship creation remains complete.
3. Create another dependent, submit each optional field, and confirm no medical document control exists.
4. Sign in as the dependent, view, edit, clear one field, and remove the record.
5. Confirm validation errors do not repopulate sensitive text after a new page response.
6. Confirm the purpose notice is understandable, optionality is prominent, and keyboard focus reaches every control and removal confirmation.
7. Activate Guardian A, grant access as the dependent, and confirm Guardian A can view/edit the same record.
8. Add Guardian B and confirm Guardian B is denied until separately granted.
9. Revoke Guardian A and confirm Guardian B remains active while Guardian A immediately loses access.
10. Reactivate Guardian A and confirm support access remains disabled until the dependent grants it again.
11. Confirm an instructor and relationship-review administrator cannot open detailed support URLs.
12. Confirm the centralized verification page, invitation page, chat, notifications, progress, quiz, and instructor pages do not display the synthetic private marker.
13. Confirm browser back navigation does not show a cached sensitive page after logout or permission revocation.
14. Confirm support information never changes relationship approval or rejection results.

If no browser session is available, record the limitation explicitly and do not claim browser QA passed.

- [ ] **Step 5: Write the verification report**

Create `docs/superpowers/verification/2026-09-09-guardian-dependent-health-support-information-e2e.md` with the title `Guardian-Dependent Health & Support Information - E2E Verification`, the date, and these sections in order:

1. `Code revision and execution mode` — run `git rev-parse HEAD` and state whether testing used an isolated worktree or the shared checkout.
2. `Environment` — record the outputs of `php --version`, `php artisan --version`, `php vendor/bin/phpunit --version`, `node --version`, and `pnpm.cmd --version`; record the testing database driver and database name without credentials; record the browser name/version or state that no browser was available.
3. `Verification commands` — use a table with the exact command, exit code, test/assertion counts, and observed result for every command in Steps 1-3.
4. `Browser scenarios` — use a table with one row for each of the fourteen scenarios in Step 4 and columns for result and evidence or limitation.
5. `Privacy and authorization checks` — record observed encryption, no-flash, no-store, policy, multiple-guardian, and denial results.
6. `Verification isolation` — record relationship decision and query/render isolation results.
7. `Full-suite comparison` — state exact current counts and compare them with the Phase 2 baseline without relabeling new failures as pre-existing.
8. `Remaining limitations` — list only observed limitations; write `None` when there are none.
9. `Production privacy review` — record the owner and status of Data Protection Officer or qualified Philippine privacy review for the notice, lawful basis, retention, backup handling, and data-subject request procedure. An uncompleted governance review is a production-release limitation, not a reason to claim the code performs legal adjudication.

Do not leave empty result cells or unexecuted commands marked as passed.

- [ ] **Step 6: Verify the report and implementation diff**

```powershell
rg -n "not yet run|pending result|replace before commit" docs/superpowers/verification/2026-09-09-guardian-dependent-health-support-information-e2e.md
git diff --check
git status --short
```

Expected: `rg` returns no unresolved template markers, `git diff --check` is clean, and status contains only intentional Phase 3 report or defect-fix files plus any pre-existing unrelated changes.

- [ ] **Step 7: Commit the verification report**

```powershell
git add docs/superpowers/verification/2026-09-09-guardian-dependent-health-support-information-e2e.md
git commit -m "docs: record dependent support verification"
```

## Final Acceptance Checklist

- [ ] Dependent creation succeeds when the optional section is skipped.
- [ ] No empty or negative health record is created for a skip.
- [ ] Submitted content is encrypted at rest and absent from ordinary sessions, logs, notifications, URLs, evidence, and broad queries.
- [ ] The dependent can create, view, update, and hard-delete their own record.
- [ ] Each guardian requires an independently active, verified, permission-enabled relationship.
- [ ] Invitation-created, admin-created, existing, and reactivated relationships do not gain support access automatically.
- [ ] The originating new-dependent relationship's permission remains dormant until relationship approval.
- [ ] Revoking or deactivating Guardian A does not affect Guardian B.
- [ ] Instructors, unrelated users, and relationship-review administrators are denied detailed access.
- [ ] The global administrator Gate bypass defers to the sensitive policy without breaking unrelated administrator abilities.
- [ ] Health & Support Information does not influence identity, invitation, evidence, or relationship decisions.
- [ ] No medical document, diagnosis, assessment, treatment, or emergency-response feature was introduced.
- [ ] Metadata audits contain no support values.
- [ ] Notifications are database-only, after-commit, and content-free.
- [ ] Stale update/delete attempts do not overwrite or recreate information.
- [ ] Sensitive responses use private, no-store caching.
- [ ] Focused Phase 1, Phase 2, and Phase 3 tests pass.
- [ ] Full-suite results introduce no new failures relative to the documented baseline.
- [ ] Browser QA is completed or its unavailability is stated explicitly.
- [ ] Production release records the required Philippine privacy-governance review or clearly remains blocked from production pending that review.
