# Guardian–Dependent Relationship Verification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Phase:** 1 of 3 — Relationship Verification and Multiple Guardians

**Approved design:** [Guardian–Dependent Relationship Verification and Multiple Guardians Design](../specs/2026-09-07-guardian-dependent-relationship-verification-design.md)

**Goal:** Make every new Guardian–Dependent relationship independently evidence-based, administrator-reviewed, permission-scoped, and safe for multiple guardians per dependent.

**Architecture:** Extend `ParentChildAccount` as the authoritative relationship aggregate, add configuration-driven verification pathways and versioned evidence metadata, and centralize all state transitions in `GuardianRelationshipVerificationService`. Preserve current child-account verification and invitation behavior through explicit compatibility adapters while migrating authorization to verified-active relationship scopes.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, Blade, Alpine.js, Tailwind CSS, PHPUnit 11, private Laravel filesystem storage, database notifications.

---

## Global Constraints

- Do not create a parallel Guardian–Dependent relationship or verification system.
- Every new relationship requires administrative review before relationship privileges activate.
- Relationship type is a claim; it never establishes legal or custodial authority automatically.
- Relationship access requires active user accounts, approved guardian identity, an active and verified relationship, and the requested per-relationship permission.
- Keep child-account verification separate from relationship verification.
- Preserve existing active legacy access while preventing legacy bypasses for new claims.
- Evidence is private and available only through authorized application routes.
- Accept at most 10 PDF, JPEG, PNG, or WebP files per submission, with a 5 MB limit per file.
- Submitted evidence rounds are immutable; resubmission creates a new round.
- Default approved permissions remain progress viewing and quiz viewing enabled, content approval disabled.
- Use neutral “administrative verification of submitted evidence” copy; do not claim legal adjudication.
- Health & Support Information and invitation profile/messaging enhancements are outside Phase 1.
- Do not add new dependencies.
- Use TDD for every behavior change and commit each independently testable task.
- Preserve unrelated working-tree changes; stage only files belonging to the current task.

---

---

## Planned File Structure

### Create

- `app/Services/GuardianRelationshipEvidenceService.php` — evidence hashing, private storage, staged-file transfer, and cleanup.
- `app/Support/GuardianRelationshipEvidenceRules.php` — shared array-based upload validation rules.
- `app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php` — dependent-creation relationship evidence validation.
- `app/Http/Requests/DeactivateGuardianRelationshipRequest.php` — voluntary deactivation validation.
- `app/Http/Requests/Admin/UpdateGuardianRelationshipPermissionsRequest.php` — relationship-scoped permission validation.
- `app/Http/Controllers/GuardianRelationshipLifecycleController.php` — guardian/dependent deactivation and reactivation endpoints.
- `database/migrations/2026_09_07_100000_harden_guardian_relationship_verification.php` — additive pathway, evidence-round, audit, and indexing changes.
- `tests/Feature/GuardianRelationshipSchemaTest.php` — schema and model contract coverage.
- `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php` — upload, duplicate, round, and privacy coverage.
- `tests/Feature/GuardianRelationshipLifecycleTest.php` — transition and independent-relationship coverage.
- `docs/superpowers/verification/2026-09-07-guardian-dependent-relationship-verification-e2e.md` — final command and browser-QA evidence.

### Modify

- `config/guardian_relationships.php`
- `app/Support/GuardianRelationshipTypes.php`
- `app/Models/ParentChildAccount.php`
- `app/Models/GuardianRelationshipVerificationDocument.php`
- `app/Models/GuardianRelationshipVerificationAudit.php`
- `app/Models/User.php`
- `app/Services/GuardianRelationshipVerificationService.php`
- `app/Services/ParentChildVerificationService.php`
- `app/Http/Requests/StoreGuardianRelationshipVerificationRequest.php`
- `app/Http/Requests/Admin/AttachParentChildRequest.php`
- `app/Http/Requests/Admin/ReviewGuardianRelationshipVerificationRequest.php`
- `app/Http/Controllers/GuardianRelationshipVerificationController.php`
- `app/Http/Controllers/ParentInvitationController.php`
- `app/Http/Controllers/Auth/ParentRegistrationController.php`
- `app/Http/Controllers/Admin/ParentChildVerificationController.php`
- `app/Http/Controllers/Admin/UserRelationshipAdminController.php`
- `app/Services/Admin/UserRelationshipService.php`
- `app/Policies/ParentChildPolicy.php`
- `app/Http/Controllers/ParentController.php`
- `app/Http/Controllers/Learner/DashboardController.php`
- `app/Http/Controllers/Learner/ParentVisibilityController.php`
- `app/Http/Controllers/Learner/ModuleController.php`
- `app/Http/Controllers/Instructor/UserController.php`
- `app/Http/Controllers/Instructor/EnrollmentController.php`
- `app/Services/Chat/ChatAuthorizationService.php`
- `app/Services/Moderation/SuspensionAppealService.php`
- `app/Services/ParentChildInvitationService.php`
- `app/Http/Requests/Parent/SendParentChildInvitationRequest.php`
- `app/Notifications/RelationshipVerificationStatusNotification.php`
- `resources/views/parent/relationship-verifications/show.blade.php`
- `resources/views/auth/child/step5-relationship-verification.blade.php`
- `resources/views/admin/parent-verifications/index.blade.php`
- `resources/views/admin/parent-verifications/show-relationship.blade.php`
- `resources/views/admin/users/relationships/index.blade.php`
- `routes/web.php`
- `routes/admin.php`
- `database/seeders/ParentMonitoringSeeder.php`
- `database/seeders/TestUserSeeder.php`
- `tests/Unit/GuardianRelationshipTypesTest.php`
- `tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php`
- `tests/Feature/Auth/ParentChildVerificationResubmissionTest.php`
- `tests/Feature/Parent/ParentChildInvitationFlowTest.php`
- `tests/Feature/Parent/ParentChildrenActionsUiTest.php`
- `tests/Feature/ParentChildMonitoringTest.php`
- `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`
- `tests/Feature/Admin/AdminParentChildVerificationUiTest.php`
- `tests/Feature/Admin/AdminUserRelationshipMutationTest.php`
- `tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php`
- `tests/Unit/Chat/ChatAuthorizationServiceTest.php`
- `tests/Feature/Chat/ChatHttpFlowTest.php`
- `tests/Feature/Chat/ChatPageRenderTest.php`
- `tests/Feature/Instructor/DashboardTest.php`
- `tests/Feature/Moderation/SuspensionAppealSubmissionTest.php`

### Delete

- `app/Http/Requests/Admin/ToggleParentChildVerificationRequest.php` — remove the direct verification bypass after its route and UI are replaced by centralized review actions.

---

---

### Task 1: Define Verification Pathways and State Vocabulary

**Files:**
- Modify: `app/Models/ParentChildAccount.php`
- Modify: `config/guardian_relationships.php`
- Modify: `app/Support/GuardianRelationshipTypes.php`
- Test: `tests/Unit/GuardianRelationshipTypesTest.php`

**Interfaces:**
- Consumes: Existing relationship type strings stored on `parent_child_accounts` and `parent_child_invitations`.
- Produces: `GuardianRelationshipTypes::selectableValues(): array`, `pathway(?string): string`, `pathwayLabel(?string): string`, `acceptedDocumentTypes(?string): array`, `requiresCircumstances(?string): bool`, and `initialVerificationStatus(?string): string`.

- [ ] **Step 1: Write failing pathway-policy tests**

Add these cases to `tests/Unit/GuardianRelationshipTypesTest.php`:

```php
public function test_every_selectable_relationship_requires_admin_verification(): void
{
    foreach (GuardianRelationshipTypes::selectableValues() as $type) {
        $this->assertTrue(GuardianRelationshipTypes::requiresVerification($type), $type);
        $this->assertSame('pending', GuardianRelationshipTypes::initialVerificationStatus($type));
    }

    $this->assertNotContains(GuardianRelationshipTypes::LEGACY_PARENT, GuardianRelationshipTypes::selectableValues());
}

public function test_relationship_types_map_to_the_expected_evidence_pathways(): void
{
    $this->assertSame('biological_parent', GuardianRelationshipTypes::pathway('biological_mother'));
    $this->assertSame('adoptive_parent', GuardianRelationshipTypes::pathway('adoptive_parent'));
    $this->assertSame('non_parental_care', GuardianRelationshipTypes::pathway('grandmother'));
    $this->assertSame('non_parental_care', GuardianRelationshipTypes::pathway('aunt'));
    $this->assertSame('custom_care', GuardianRelationshipTypes::pathway('other'));
    $this->assertSame('legacy', GuardianRelationshipTypes::pathway('parent'));
}

public function test_non_parental_pathways_accept_flexible_evidence_and_require_context(): void
{
    $types = GuardianRelationshipTypes::acceptedDocumentTypes('aunt');

    $this->assertContains('court_order', $types);
    $this->assertContains('agency_document', $types);
    $this->assertContains('care_arrangement', $types);
    $this->assertContains('other_supporting_document', $types);
    $this->assertTrue(GuardianRelationshipTypes::requiresCircumstances('aunt'));
}
```

- [ ] **Step 2: Run the policy tests and confirm failure**

Run:

```powershell
php artisan test tests/Unit/GuardianRelationshipTypesTest.php
```

Expected: FAIL because pathway and selectable-value methods do not exist and relatives currently bypass verification.

- [ ] **Step 3: Add one vocabulary on the existing relationship model**

Add constants to `ParentChildAccount` instead of introducing parallel enum types that would force casts through every existing Blade view and service:

```php
public const STATUS_PENDING = 'pending';
public const STATUS_ACTIVE = 'active';
public const STATUS_REJECTED = 'rejected';
public const STATUS_INACTIVE = 'inactive';
public const STATUS_REVOKED = 'revoked';

public const VERIFICATION_PENDING = 'pending';
public const VERIFICATION_UNDER_REVIEW = 'under_review';
public const VERIFICATION_RESUBMISSION_REQUIRED = 'resubmission_required';
public const VERIFICATION_VERIFIED = 'verified';
public const VERIFICATION_REJECTED = 'rejected';
public const VERIFICATION_REVOKED = 'revoked';
```

Use these constants in every new scope and transition. Keep persisted values as strings so current queries, forms, and legacy records remain compatible.

- [ ] **Step 4: Replace declaration-based configuration with pathway configuration**

Update `config/guardian_relationships.php` so every selectable type maps to a pathway and every pathway declares accepted evidence:

```php
'document_types' => [
    'civil_registry_record' => 'Civil Registry or Parentage Record',
    'adoption_order' => 'Adoption-Related Order or Record',
    'court_order' => 'Court Order',
    'guardianship_document' => 'Guardianship Documentation',
    'agency_document' => 'Government or Agency Documentation',
    'official_appointment' => 'Official Appointment Documentation',
    'care_arrangement' => 'Custody or Caregiving Arrangement Evidence',
    'other_official_document' => 'Other Official Supporting Evidence',
    'other_supporting_document' => 'Other Contextual Supporting Evidence',
],

'pathways' => [
    'biological_parent' => [
        'label' => 'Biological Parent Evidence Review',
        'document_types' => ['civil_registry_record', 'court_order', 'agency_document', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['civil_registry_record', 'court_order', 'agency_document'],
        'requires_circumstances' => false,
    ],
    'adoptive_parent' => [
        'label' => 'Adoptive Parent Evidence Review',
        'document_types' => ['adoption_order', 'court_order', 'agency_document', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['adoption_order', 'court_order', 'agency_document'],
        'requires_circumstances' => false,
    ],
    'non_parental_care' => [
        'label' => 'Non-Parental Care or Custody Review',
        'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document'],
        'requires_circumstances' => true,
    ],
    'guardianship' => [
        'label' => 'Guardianship or Authority Review',
        'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment'],
        'requires_circumstances' => false,
    ],
    'court_appointed_guardianship' => [
        'label' => 'Court-Appointed Guardianship Review',
        'document_types' => ['court_order', 'official_appointment', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['court_order', 'official_appointment'],
        'requires_circumstances' => false,
    ],
    'custom_care' => [
        'label' => 'Custom Care Relationship Review',
        'document_types' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document', 'other_supporting_document'],
        'required_any_of' => ['court_order', 'guardianship_document', 'agency_document', 'official_appointment', 'care_arrangement', 'other_official_document'],
        'requires_circumstances' => true,
    ],
],

'types' => [
    'biological_mother' => ['pathway' => 'biological_parent'],
    'biological_father' => ['pathway' => 'biological_parent'],
    'adoptive_parent' => ['pathway' => 'adoptive_parent'],
    'foster_parent' => ['pathway' => 'non_parental_care'],
    'grandmother' => ['pathway' => 'non_parental_care'],
    'grandfather' => ['pathway' => 'non_parental_care'],
    'aunt' => ['pathway' => 'non_parental_care'],
    'uncle' => ['pathway' => 'non_parental_care'],
    'older_sister' => ['pathway' => 'non_parental_care'],
    'older_brother' => ['pathway' => 'non_parental_care'],
    'legal_guardian' => ['pathway' => 'guardianship'],
    'court_appointed_guardian' => ['pathway' => 'court_appointed_guardianship'],
    'relative' => ['pathway' => 'non_parental_care'],
    'family_friend' => ['pathway' => 'non_parental_care'],
    'caregiver' => ['pathway' => 'non_parental_care'],
    'other' => ['pathway' => 'custom_care'],
],
```

Retain the existing status labels and rejection reasons, add `claim_closed => Relationship claim withdrawn or administratively closed`, and change `not_required` to `Legacy Declaration` so it cannot be mistaken for current administrative approval.

- [ ] **Step 5: Implement the pathway API**

Update `GuardianRelationshipTypes` with these exact semantics:

```php
public static function selectableValues(): array
{
    return array_values(array_filter(
        self::values(),
        static fn (string $type): bool => $type !== self::LEGACY_PARENT,
    ));
}

public static function requiresVerification(?string $type): bool
{
    return $type !== self::LEGACY_PARENT && array_key_exists((string) $type, config('guardian_relationships.types', []));
}

public static function pathway(?string $type): string
{
    if ($type === self::LEGACY_PARENT) {
        return 'legacy';
    }

    return (string) config("guardian_relationships.types.{$type}.pathway", 'custom_care');
}

public static function pathwayLabel(?string $type): string
{
    $pathway = self::pathway($type);

    return (string) config("guardian_relationships.pathways.{$pathway}.label", 'Relationship Evidence Review');
}

public static function initialVerificationStatus(?string $type): string
{
    return $type === self::LEGACY_PARENT ? 'reserved' : 'pending';
}

public static function acceptedDocumentTypes(?string $type): array
{
    $pathway = self::pathway($type);
    $keys = (array) config("guardian_relationships.pathways.{$pathway}.document_types", []);
    $labels = (array) config('guardian_relationships.document_types', []);

    return array_values(array_filter($keys, static fn (string $key): bool => array_key_exists($key, $labels)));
}

public static function requiredDocumentTypes(?string $type): array
{
    return (array) config('guardian_relationships.pathways.'.self::pathway($type).'.required_any_of', []);
}

public static function requiresCircumstances(?string $type): bool
{
    return (bool) config('guardian_relationships.pathways.'.self::pathway($type).'.requires_circumstances', false);
}
```

- [ ] **Step 6: Run the policy tests**

Run:

```powershell
php artisan test tests/Unit/GuardianRelationshipTypesTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit the policy vocabulary**

```powershell
git add app/Models/ParentChildAccount.php app/Support/GuardianRelationshipTypes.php config/guardian_relationships.php tests/Unit/GuardianRelationshipTypesTest.php
git commit -m "feat: define guardian verification pathways"
```

---

---

### Task 2: Add Relationship and Evidence Schema Contracts

**Files:**
- Create: `database/migrations/2026_09_07_100000_harden_guardian_relationship_verification.php`
- Create: `tests/Feature/GuardianRelationshipSchemaTest.php`
- Modify: `app/Models/ParentChildAccount.php`
- Modify: `app/Models/GuardianRelationshipVerificationDocument.php`
- Modify: `app/Models/GuardianRelationshipVerificationAudit.php`

**Interfaces:**
- Consumes: Task 1 pathway strings and relationship-model state constants.
- Produces: `ParentChildAccount::scopeVerifiedActive()`, `scopeAccessEligible()`, `scopeWithPermission()`, `isVerifiedActive()`, evidence-round metadata, and audit round metadata.

- [ ] **Step 1: Write failing schema and model tests**

Create `tests/Feature/GuardianRelationshipSchemaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuardianRelationshipSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_and_evidence_round_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('parent_child_accounts', [
            'verification_pathway', 'current_evidence_round', 'relationship_deactivated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('guardian_relationship_verification_documents', [
            'submission_round', 'document_side', 'pairing_key', 'display_order',
            'content_sha256', 'submitted_at', 'superseded_at',
        ]));
        $this->assertTrue(Schema::hasColumn('guardian_relationship_verification_audits', 'submission_round'));
    }

    public function test_verified_active_scope_excludes_pending_and_revoked_guardians(): void
    {
        $guardian = User::factory()->create([
            'status' => 'active',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $dependent = User::factory()->create(['status' => 'active']);

        $active = ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => 'active',
            'relationship_verified_status' => 'verified',
            'relationship_verified_at' => now(),
        ]);

        $this->assertTrue(ParentChildAccount::verifiedActive()->whereKey($active->getKey())->exists());

        $active->update(['relationship_status' => 'revoked', 'relationship_verified_status' => 'revoked']);

        $this->assertFalse(ParentChildAccount::verifiedActive()->whereKey($active->getKey())->exists());
    }
}
```

- [ ] **Step 2: Run the schema test and confirm failure**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipSchemaTest.php
```

Expected: FAIL because the migration columns and model scopes do not exist.

- [ ] **Step 3: Create the additive migration**

Create `database/migrations/2026_09_07_100000_harden_guardian_relationship_verification.php` with these operations:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->string('verification_pathway', 64)->nullable()->after('relationship_custom')->index();
            $table->unsignedSmallInteger('current_evidence_round')->default(0)->after('relationship_verified_status');
            $table->timestamp('relationship_deactivated_at')->nullable()->after('relationship_verification_revoked_at');
            $table->index(
                ['parent_user_id', 'relationship_status', 'relationship_verified_status'],
                'pc_guardian_access_idx'
            );
            $table->index(
                ['child_user_id', 'relationship_status', 'relationship_verified_status'],
                'pc_dependent_access_idx'
            );
        });

        Schema::table('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->unsignedSmallInteger('submission_round')->default(1)->after('document_type');
            $table->string('document_side', 16)->default('not_applicable')->after('submission_round');
            $table->uuid('pairing_key')->nullable()->after('document_side');
            $table->unsignedSmallInteger('display_order')->default(0)->after('pairing_key');
            $table->char('content_sha256', 64)->nullable()->after('size_bytes');
            $table->timestamp('submitted_at')->nullable()->after('content_sha256');
            $table->timestamp('superseded_at')->nullable()->after('submitted_at');
            $table->unique(
                ['parent_child_account_id', 'submission_round', 'content_sha256'],
                'grvd_round_hash_unique'
            );
            $table->index(
                ['parent_child_account_id', 'submission_round', 'display_order'],
                'grvd_round_order_idx'
            );
        });

        Schema::table('guardian_relationship_verification_audits', function (Blueprint $table): void {
            $table->unsignedSmallInteger('submission_round')->nullable()->after('new_status');
        });

        $pathways = [
            'biological_mother' => 'biological_parent',
            'biological_father' => 'biological_parent',
            'adoptive_parent' => 'adoptive_parent',
            'foster_parent' => 'non_parental_care',
            'grandmother' => 'non_parental_care',
            'grandfather' => 'non_parental_care',
            'aunt' => 'non_parental_care',
            'uncle' => 'non_parental_care',
            'older_sister' => 'non_parental_care',
            'older_brother' => 'non_parental_care',
            'legal_guardian' => 'guardianship',
            'court_appointed_guardian' => 'court_appointed_guardianship',
            'relative' => 'non_parental_care',
            'family_friend' => 'non_parental_care',
            'caregiver' => 'non_parental_care',
            'other' => 'custom_care',
        ];

        DB::table('parent_child_accounts')->orderBy('id')->chunkById(100, function ($rows) use ($pathways): void {
            foreach ($rows as $row) {
                $preserveLegacyAccess = $row->relationship_status === 'active'
                    && $row->verification_status === 'approved'
                    && $row->relationship_verified_at !== null
                    && in_array($row->relationship_verified_status, ['not_required', 'reserved'], true);

                $updates = [
                    'verification_pathway' => $preserveLegacyAccess
                        ? 'legacy'
                        : ($pathways[$row->relationship_type] ?? 'legacy'),
                ];

                if ($preserveLegacyAccess) {
                    $updates['is_legacy_relationship'] = true;
                    $updates['relationship_verified_status'] = 'verified';
                }

                DB::table('parent_child_accounts')->where('id', $row->id)->update($updates);
            }
        });

        DB::table('guardian_relationship_verification_documents')->update([
            'submission_round' => 1,
            'document_side' => 'not_applicable',
            'submitted_at' => DB::raw('created_at'),
        ]);

        DB::table('guardian_relationship_verification_documents')
            ->pluck('parent_child_account_id')
            ->unique()
            ->each(function (int $relationshipId): void {
                DB::table('parent_child_accounts')
                    ->where('id', $relationshipId)
                    ->update(['current_evidence_round' => 1]);
            });
    }

    public function down(): void
    {
        Schema::table('guardian_relationship_verification_audits', function (Blueprint $table): void {
            $table->dropColumn('submission_round');
        });

        Schema::table('guardian_relationship_verification_documents', function (Blueprint $table): void {
            $table->dropUnique('grvd_round_hash_unique');
            $table->dropIndex('grvd_round_order_idx');
            $table->dropColumn([
                'submission_round', 'document_side', 'pairing_key', 'display_order',
                'content_sha256', 'submitted_at', 'superseded_at',
            ]);
        });

        Schema::table('parent_child_accounts', function (Blueprint $table): void {
            $table->dropIndex('pc_guardian_access_idx');
            $table->dropIndex('pc_dependent_access_idx');
            $table->dropIndex(['verification_pathway']);
            $table->dropColumn(['verification_pathway', 'current_evidence_round', 'relationship_deactivated_at']);
        });
    }
};
```

- [ ] **Step 4: Add model metadata and verified-active scopes**

Add the new fields to `$fillable` and casts, then add these methods to `ParentChildAccount`:

```php
use Illuminate\Database\Eloquent\Builder;

public function scopeVerifiedActive(Builder $query): Builder
{
    return $query
        ->where('relationship_status', self::STATUS_ACTIVE)
        ->where('relationship_verified_status', self::VERIFICATION_VERIFIED)
        ->whereNotNull('relationship_verified_at');
}

public function scopeAccessEligible(Builder $query): Builder
{
    return $query
        ->verifiedActive()
        ->whereHas('parent', fn (Builder $parent) => $parent
            ->where('status', User::STATUS_ACTIVE)
            ->where('parent_verification_status', 'approved'))
        ->whereHas('child', fn (Builder $child) => $child->where('status', User::STATUS_ACTIVE))
        ->where(function (Builder $childVerification): void {
            $childVerification
                ->whereNull('verification_document_path')
                ->orWhere('verification_status', 'approved');
        });
}

public function scopeWithPermission(Builder $query, string $permission): Builder
{
    $allowed = ['can_view_progress', 'can_view_quiz_answers', 'can_approve_content'];
    if (! in_array($permission, $allowed, true)) {
        throw new InvalidArgumentException('Unknown guardian relationship permission.');
    }

    return $query->where($permission, true);
}

public function isVerifiedActive(): bool
{
    return $this->relationship_status === self::STATUS_ACTIVE
        && $this->relationship_verified_status === self::VERIFICATION_VERIFIED
        && $this->relationship_verified_at !== null;
}

public function hasVerifiedRelationshipRequirement(): bool
{
    return $this->relationship_verified_status === self::VERIFICATION_VERIFIED;
}
```

Import `InvalidArgumentException`. Change the existing `isVerified()` compatibility method to `return $this->isVerifiedActive();` so it cannot reintroduce the legacy child-verification predicate.

Add `verification_pathway`, `current_evidence_round`, and `relationship_deactivated_at` to the relationship model. Cast the round to integer and the deactivation timestamp to datetime.

Add the new evidence fields to `GuardianRelationshipVerificationDocument::$fillable` and cast round/order to integer and submission/superseded timestamps to datetime. Add `submission_round` to `GuardianRelationshipVerificationAudit::$fillable` and cast it to integer.

- [ ] **Step 5: Run the schema and pathway tests**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipSchemaTest.php tests/Unit/GuardianRelationshipTypesTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit schema and model contracts**

```powershell
git add database/migrations/2026_09_07_100000_harden_guardian_relationship_verification.php app/Models/ParentChildAccount.php app/Models/GuardianRelationshipVerificationDocument.php app/Models/GuardianRelationshipVerificationAudit.php tests/Feature/GuardianRelationshipSchemaTest.php
git commit -m "feat: add guardian evidence rounds"
```

---

### Task 3: Build Private Multi-Document Evidence Storage

**Files:**
- Create: `app/Support/GuardianRelationshipEvidenceRules.php`
- Create: `app/Services/GuardianRelationshipEvidenceService.php`
- Create: `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

**Interfaces:**
- Consumes: Task 2 document metadata columns.
- Produces: `GuardianRelationshipEvidenceRules::for(array $acceptedTypes): array`, `metadataErrors(array): array`, `GuardianRelationshipEvidenceService::storeUploadedRound(ParentChildAccount, User, int, array, array &$storedPaths): Collection`, and `deleteStoredPaths(array): void`.

- [ ] **Step 1: Write failing evidence-storage tests**

Create `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php` with tests that call the service directly:

```php
public function test_multiple_documents_are_stored_privately_in_one_round(): void
{
    Storage::fake('local');
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
    $storedPaths = [];

    $documents = app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
        $relationship,
        $guardian,
        1,
        [
            [
                'document_type' => 'adoption_order',
                'document_side' => 'front',
                'pairing_key' => 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9',
                'file' => UploadedFile::fake()->image('order-front.jpg'),
            ],
            [
                'document_type' => 'adoption_order',
                'document_side' => 'back',
                'pairing_key' => 'f2f07af0-1e32-45fb-9f37-02a6a653a2d9',
                'file' => UploadedFile::fake()->image('order-back.jpg'),
            ],
        ],
        $storedPaths,
    );

    $this->assertCount(2, $documents);
    $this->assertSame([0, 1], $documents->pluck('display_order')->all());
    $this->assertSame(['front', 'back'], $documents->pluck('document_side')->all());
    $this->assertTrue($documents->every(fn ($document) => $document->disk === 'local'));
    $this->assertTrue($documents->every(fn ($document) => strlen($document->content_sha256) === 64));
    foreach ($storedPaths as $path) {
        Storage::disk('local')->assertExists($path);
    }
}

public function test_exact_duplicate_content_is_rejected_and_new_files_are_cleaned_up(): void
{
    Storage::fake('local');
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
    $storedPaths = [];
    $first = UploadedFile::fake()->create('same.pdf', 100, 'application/pdf');
    $second = new UploadedFile(
        $first->getPathname(),
        'renamed.pdf',
        'application/pdf',
        null,
        true,
    );

    try {
        app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
            $relationship,
            $guardian,
            1,
            [
                ['document_type' => 'adoption_order', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => $first],
                ['document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => $second],
            ],
            $storedPaths,
        );
        $this->fail('Expected duplicate evidence validation to fail.');
    } catch (ValidationException $exception) {
        $this->assertArrayHasKey('documents', $exception->errors());
    }

    $this->assertDatabaseCount('guardian_relationship_verification_documents', 0);
    $this->assertSame([], Storage::disk('local')->allFiles());
}
```

Add `test_front_and_back_metadata_requires_one_uuid_pair_with_unique_sides()`. Assert that a front/back pair with the same UUID and document type has no metadata errors, while a missing pairing key, a key on `not_applicable`, repeated `front` side, or mixed document types under one key returns field-specific errors.

Add this fixture helper in the same test class:

```php
private function pendingRelationship(string $type): array
{
    $guardian = User::factory()->create([
        'role' => 'learner',
        'status' => User::STATUS_ACTIVE,
        'is_parent_registration' => true,
        'parent_verification_status' => 'approved',
    ]);
    $guardian->assignRole('learner');

    $dependent = User::factory()->create([
        'role' => 'learner',
        'status' => User::STATUS_ACTIVE,
    ]);
    $dependent->assignRole('learner');

    $relationship = ParentChildAccount::query()->create([
        'parent_user_id' => $guardian->id,
        'child_user_id' => $dependent->id,
        'relationship_type' => $type,
        'verification_pathway' => GuardianRelationshipTypes::pathway($type),
        'relationship_status' => ParentChildAccount::STATUS_PENDING,
        'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
        'current_evidence_round' => 0,
        'can_view_progress' => true,
        'can_view_quiz_answers' => true,
        'can_approve_content' => false,
        'verification_status' => 'approved',
    ]);

    return [$guardian, $dependent, $relationship];
}
```

Import `ParentChildAccount`, `User`, `GuardianRelationshipTypes`, `UploadedFile`, `Storage`, and `ValidationException` explicitly at the top of the test.

- [ ] **Step 2: Run the evidence tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
```

Expected: FAIL because the evidence rules and service do not exist.

- [ ] **Step 3: Add shared nested-array validation rules**

Create `app/Support/GuardianRelationshipEvidenceRules.php`:

```php
<?php

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

final class GuardianRelationshipEvidenceRules
{
    public static function for(array $acceptedTypes): array
    {
        return [
            'documents' => ['required', 'array', 'min:1', 'max:10'],
            'documents.*.document_type' => ['required', 'string', Rule::in($acceptedTypes)],
            'documents.*.document_side' => ['required', 'string', Rule::in(['front', 'back', 'not_applicable'])],
            'documents.*.pairing_key' => ['nullable', 'uuid'],
            'documents.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public static function metadataErrors(array $documents): array
    {
        $errors = [];
        $pairTypes = [];
        $pairSides = [];

        foreach (array_values($documents) as $index => $document) {
            $side = (string) ($document['document_side'] ?? '');
            $pairingKey = trim((string) ($document['pairing_key'] ?? ''));
            $documentType = (string) ($document['document_type'] ?? '');

            if ($side === 'not_applicable') {
                if ($pairingKey !== '') {
                    $errors["documents.{$index}.pairing_key"] = 'A single-sided document cannot have a front/back pairing key.';
                }

                continue;
            }

            if (! in_array($side, ['front', 'back'], true) || ! Str::isUuid($pairingKey)) {
                $errors["documents.{$index}.pairing_key"] = 'Front and back files require the same valid pairing key.';

                continue;
            }

            if (isset($pairTypes[$pairingKey]) && $pairTypes[$pairingKey] !== $documentType) {
                $errors["documents.{$index}.document_type"] = 'Front and back files in a pair must use the same document type.';
            }

            if (isset($pairSides[$pairingKey][$side])) {
                $errors["documents.{$index}.document_side"] = 'A front/back pair cannot contain the same side twice.';
            }

            $pairTypes[$pairingKey] = $documentType;
            $pairSides[$pairingKey][$side] = true;
        }

        return $errors;
    }
}
```

- [ ] **Step 4: Implement private evidence storage and hashing**

Create `app/Services/GuardianRelationshipEvidenceService.php`:

```php
<?php

namespace App\Services;

use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\GuardianRelationshipEvidenceRules;
use App\Support\GuardianRelationshipTypes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class GuardianRelationshipEvidenceService
{
    public function storeUploadedRound(
        ParentChildAccount $relationship,
        User $guardian,
        int $round,
        array $documents,
        array &$storedPaths,
    ): Collection {
        $storedPaths = [];
        $hashes = [];

        $acceptedTypes = GuardianRelationshipTypes::acceptedDocumentTypes(
            $relationship->relationship_type,
        );
        Validator::make(
            ['documents' => $documents],
            GuardianRelationshipEvidenceRules::for($acceptedTypes),
        )->validate();

        $metadataErrors = GuardianRelationshipEvidenceRules::metadataErrors($documents);
        if ($metadataErrors !== []) {
            throw ValidationException::withMessages($metadataErrors);
        }
        try {
            return collect($documents)->values()->map(function (array $item, int $index) use (
                $relationship,
                $guardian,
                $round,
                &$storedPaths,
                &$hashes,
            ): GuardianRelationshipVerificationDocument {
                $file = $item['file'];
                if (! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages(['documents' => 'Every evidence item must contain a file.']);
                }
                $realPath = $file->getRealPath();
                if (! is_string($realPath)) {
                    throw ValidationException::withMessages(['documents' => 'An evidence file could not be read.']);
                }

                $hash = hash_file('sha256', $realPath);
                if (! is_string($hash) || in_array($hash, $hashes, true)) {
                    throw ValidationException::withMessages(['documents' => 'The same evidence file cannot be uploaded twice.']);
                }

                if (GuardianRelationshipVerificationDocument::query()
                    ->where('parent_child_account_id', $relationship->id)
                    ->where('submission_round', $round)
                    ->where('content_sha256', $hash)
                    ->exists()) {
                    throw ValidationException::withMessages(['documents' => 'The same evidence file cannot be uploaded twice.']);
                }

                $hashes[] = $hash;
                $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
                $filename = Str::uuid().'.'.$extension;
                $path = $file->storeAs(
                    'guardian-relationship-verifications/'.$relationship->id.'/round-'.$round,
                    $filename,
                    'local',
                );

                if (! is_string($path)) {
                    throw ValidationException::withMessages(['documents' => 'An evidence file could not be stored.']);
                }

                $storedPaths[] = $path;

                return GuardianRelationshipVerificationDocument::query()->create([
                    'parent_child_account_id' => $relationship->id,
                    'uploaded_by_user_id' => $guardian->id,
                    'document_type' => (string) $item['document_type'],
                    'submission_round' => $round,
                    'document_side' => (string) $item['document_side'],
                    'pairing_key' => $item['pairing_key'] ?? null,
                    'display_order' => $index,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => basename((string) $file->getClientOriginalName()),
                    'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                    'size_bytes' => (int) ($file->getSize() ?: 0),
                    'content_sha256' => $hash,
                    'submitted_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }
    }

    public function deleteStoredPaths(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('local')->delete(array_values(array_unique($paths)));
        }
    }
}
```

- [ ] **Step 5: Run the evidence tests**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the evidence service**

```powershell
git add app/Support/GuardianRelationshipEvidenceRules.php app/Services/GuardianRelationshipEvidenceService.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
git commit -m "feat: store multi-document relationship evidence"
```

---

---

### Task 4: Enforce the Relationship Lifecycle State Machine

**Files:**
- Create: `tests/Feature/GuardianRelationshipLifecycleTest.php`
- Modify: `app/Services/GuardianRelationshipVerificationService.php`
- Modify: `app/Notifications/RelationshipVerificationStatusNotification.php`

**Interfaces:**
- Consumes: Task 2 states/scopes and Task 3 evidence service.
- Produces: `submit(ParentChildAccount, User, array, ?string, ?array &$storedPaths = null): ParentChildAccount`, `approve(ParentChildAccount, User): ParentChildAccount`, `reject(ParentChildAccount, User, string, ?string, bool): ParentChildAccount`, `closePendingClaim(ParentChildAccount, User, ?string): ParentChildAccount`, `revoke(ParentChildAccount, User, string, ?string): ParentChildAccount`, `deactivate(ParentChildAccount, User, ?string): ParentChildAccount`, and `requestReactivation(ParentChildAccount, User): ParentChildAccount`.

- [ ] **Step 1: Write failing lifecycle and isolation tests**

Create `tests/Feature/GuardianRelationshipLifecycleTest.php` with these cases:

```php
public function test_submission_approval_and_revocation_use_explicit_states(): void
{
    Storage::fake('local');
    Notification::fake();
    [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();

    $submitted = app(GuardianRelationshipVerificationService::class)->submit(
        $relationship,
        $guardian,
        [[
            'document_type' => 'civil_registry_record',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => UploadedFile::fake()->create('record.pdf', 100, 'application/pdf'),
        ]],
        null,
    );

    $this->assertSame('pending', $submitted->relationship_status);
    $this->assertSame('under_review', $submitted->relationship_verified_status);
            $this->assertSame(1, $submitted->current_evidence_round);

    $approved = app(GuardianRelationshipVerificationService::class)->approve($submitted, $admin);
    $this->assertSame('active', $approved->relationship_status);
    $this->assertSame('verified', $approved->relationship_verified_status);

    $revoked = app(GuardianRelationshipVerificationService::class)->revoke(
        $approved,
        $admin,
        'cannot_verify',
        'Evidence was later invalidated.',
    );
    $this->assertSame('revoked', $revoked->relationship_status);
    $this->assertSame('revoked', $revoked->relationship_verified_status);
}

public function test_revoking_one_guardian_does_not_change_another_guardian(): void
{
    [$admin, $firstGuardian, $dependent, $first] = $this->actorsAndVerifiedRelationship();
    $secondGuardian = $this->approvedGuardian();
    $second = $this->verifiedRelationship($secondGuardian, $dependent);

    app(GuardianRelationshipVerificationService::class)->revoke($first, $admin, 'cannot_verify', null);

    $this->assertSame('revoked', $first->fresh()->relationship_status);
    $this->assertSame('active', $second->fresh()->relationship_status);
    $this->assertSame('verified', $second->fresh()->relationship_verified_status);
}

public function test_admin_cannot_approve_without_current_round_evidence(): void
{
    [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();
    $relationship->update(['relationship_verified_status' => 'under_review']);

    $this->expectException(InvalidArgumentException::class);
    app(GuardianRelationshipVerificationService::class)->approve($relationship, $admin);
}
```

Add `test_admin_can_close_an_unsubmitted_or_resubmission_required_claim()` and assert the result is operationally and administratively `rejected`, retains all prior documents, records `claim_closed`, and grants no access.

Add `test_resubmission_creates_a_new_round_and_marks_only_the_previous_round_superseded()`: submit round 1, request resubmission, submit different evidence, then assert round 1 rows have `superseded_at`, round 2 rows do not, and no round-1 file metadata or storage path changed.

Use these fixtures so the transition preconditions are explicit:

```php
private function actorsAndPendingRelationship(): array
{
    $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
    $admin->assignRole('admin');
    $guardian = $this->approvedGuardian();
    $dependent = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
    $dependent->assignRole('learner');

    $relationship = ParentChildAccount::query()->create([
        'parent_user_id' => $guardian->id,
        'child_user_id' => $dependent->id,
        'relationship_type' => 'biological_mother',
        'verification_pathway' => 'biological_parent',
        'relationship_status' => ParentChildAccount::STATUS_PENDING,
        'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
        'current_evidence_round' => 0,
        'can_view_progress' => true,
        'can_view_quiz_answers' => true,
        'can_approve_content' => false,
        'verification_status' => 'approved',
    ]);

    return [$admin, $guardian, $dependent, $relationship];
}

private function actorsAndVerifiedRelationship(): array
{
    [$admin, $guardian, $dependent, $relationship] = $this->actorsAndPendingRelationship();
    $relationship->update([
        'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
        'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
        'current_evidence_round' => 1,
        'relationship_verified_at' => now(),
    ]);

    return [$admin, $guardian, $dependent, $relationship->fresh()];
}

private function approvedGuardian(): User
{
    $guardian = User::factory()->create([
        'role' => 'learner',
        'status' => User::STATUS_ACTIVE,
        'is_parent_registration' => true,
        'parent_verification_status' => 'approved',
    ]);
    $guardian->assignRole('learner');

    return $guardian;
}

private function verifiedRelationship(User $guardian, User $dependent): ParentChildAccount
{
    return ParentChildAccount::query()->create([
        'parent_user_id' => $guardian->id,
        'child_user_id' => $dependent->id,
        'relationship_type' => 'aunt',
        'verification_pathway' => 'non_parental_care',
        'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
        'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
        'current_evidence_round' => 1,
        'relationship_verified_at' => now(),
        'can_view_progress' => true,
        'can_view_quiz_answers' => true,
        'can_approve_content' => false,
        'verification_status' => 'approved',
    ]);
}
```

Import `InvalidArgumentException`, `Notification`, `Storage`, and `UploadedFile` alongside the relationship, user, and service classes.

- [ ] **Step 2: Run the lifecycle tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipLifecycleTest.php
```

Expected: FAIL because submission still accepts the old two-file payload and decisions do not enforce state guards.

- [ ] **Step 3: Inject evidence storage and implement guarded submission**

Refactor the service constructor and `submit` method to this contract:

```php
public function __construct(private readonly GuardianRelationshipEvidenceService $evidence) {}

public function submit(
    ParentChildAccount $relationship,
    User $guardian,
    array $documents,
    ?string $circumstances,
    ?array &$storedPaths = null,
): ParentChildAccount {
    $storedPaths = [];

    try {
        return DB::transaction(function () use ($relationship, $guardian, $documents, $circumstances, &$storedPaths): ParentChildAccount {
            $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
            $this->assertGuardianOwns($locked, $guardian);
            $this->assertState($locked, [
                ParentChildAccount::VERIFICATION_PENDING,
                ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
            ]);

            if (! $locked->verificationAudits()->where('action', 'claim_created')->exists()) {
                $this->audit(
                    $locked,
                    $guardian,
                    'claim_created',
                    null,
                    ParentChildAccount::VERIFICATION_PENDING,
                );
            }

            $round = ((int) $locked->current_evidence_round) + 1;
            if ($locked->current_evidence_round > 0) {
                $locked->verificationDocuments()
                    ->whereNull('superseded_at')
                    ->update(['superseded_at' => now()]);
            }
            $this->evidence->storeUploadedRound($locked, $guardian, $round, $documents, $storedPaths);

            $previous = (string) $locked->relationship_verified_status;
            $locked->update([
                'verification_pathway' => $locked->verification_pathway ?: GuardianRelationshipTypes::pathway($locked->relationship_type),
                'relationship_status' => ParentChildAccount::STATUS_PENDING,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                'current_evidence_round' => $round,
                'relationship_notes' => $circumstances,
                'relationship_verification_submitted_at' => now(),
                'relationship_verification_reviewed_by' => null,
                'relationship_verification_reviewed_at' => null,
                'relationship_verification_rejection_reason' => null,
                'relationship_verification_rejection_note' => null,
                'relationship_verification_revoked_at' => null,
                'relationship_deactivated_at' => null,
            ]);

            $action = $previous === ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED
                ? 'resubmitted'
                : 'submitted';
            $this->audit(
                $locked,
                $guardian,
                $action,
                $previous,
                ParentChildAccount::VERIFICATION_UNDER_REVIEW,
                $round,
            );
            $this->notifyAfterCommit($locked->id, $action, true);

            return $locked->fresh(['parent', 'child', 'verificationDocuments']);
        });
    } catch (Throwable $exception) {
        $this->evidence->deleteStoredPaths($storedPaths);
        throw $exception;
    }
}
```

`assertGuardianOwns()` must require matching `parent_user_id`, an active guardian account, and approved guardian identity. `assertState()` must throw `InvalidArgumentException('This relationship cannot transition from its current verification state.')` unless the current verification status is in the supplied list. Import `GuardianRelationshipTypes` and `Throwable` in the service.

- [ ] **Step 4: Implement guarded administrative decisions**

Use one locked transaction per action. Apply these exact updates:

```php
// approve: only from under_review with at least one document in current_evidence_round
[
    'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
    'relationship_verification_reviewed_by' => $admin->id,
    'relationship_verification_reviewed_at' => now(),
    'relationship_verification_rejection_reason' => null,
    'relationship_verification_rejection_note' => null,
    'relationship_verification_revoked_at' => null,
    'relationship_deactivated_at' => null,
    'relationship_verified_at' => now(),
]

// reject with allowResubmission=true: only from under_review
[
    'relationship_status' => ParentChildAccount::STATUS_PENDING,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
    'relationship_verification_reviewed_by' => $admin->id,
    'relationship_verification_reviewed_at' => now(),
    'relationship_verification_rejection_reason' => $reasonCode,
    'relationship_verification_rejection_note' => $note,
    'relationship_verified_at' => null,
]

// reject with allowResubmission=false: only from under_review
[
    'relationship_status' => ParentChildAccount::STATUS_REJECTED,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_REJECTED,
    'relationship_verification_reviewed_by' => $admin->id,
    'relationship_verification_reviewed_at' => now(),
    'relationship_verification_rejection_reason' => $reasonCode,
    'relationship_verification_rejection_note' => $note,
    'relationship_verified_at' => null,
]

// revoke: only from active + verified
[
    'relationship_status' => ParentChildAccount::STATUS_REVOKED,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
    'relationship_verification_reviewed_by' => $admin->id,
    'relationship_verification_reviewed_at' => now(),
    'relationship_verification_rejection_reason' => $reasonCode,
    'relationship_verification_rejection_note' => $note,
    'relationship_verification_revoked_at' => now(),
]
```

Every administrative method must first call `assertAdministrator(User $actor)`, which requires `$actor->status === User::STATUS_ACTIVE && $actor->hasRole('admin')`; otherwise throw `AuthorizationException`. Import `Illuminate\Auth\Access\AuthorizationException`. Do not update the legacy `verification_status` field in these relationship decisions. That field continues representing child-account verification where a `verification_document_path` exists.

Implement `closePendingClaim()` only for verification status `pending` or `resubmission_required`. Under the same row lock, write:

```php
[
    'relationship_status' => ParentChildAccount::STATUS_REJECTED,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_REJECTED,
    'relationship_verification_reviewed_by' => $admin->id,
    'relationship_verification_reviewed_at' => now(),
    'relationship_verification_rejection_reason' => 'claim_closed',
    'relationship_verification_rejection_note' => $note,
    'relationship_verified_at' => null,
]
```

Audit `claim_closed` with the previous verification state and notify both relationship parties after commit. Do not delete the row or prior evidence.

- [ ] **Step 5: Add voluntary deactivation and reactivation**

Implement:

```php
public function deactivate(ParentChildAccount $relationship, User $actor, ?string $note): ParentChildAccount
{
    return DB::transaction(function () use ($relationship, $actor, $note): ParentChildAccount {
        $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
        $this->assertPartyOrAdmin($locked, $actor);

        if (! $locked->isVerifiedActive()) {
            throw new InvalidArgumentException('Only an active verified relationship can be deactivated.');
        }

        $locked->update([
            'relationship_status' => ParentChildAccount::STATUS_INACTIVE,
            'relationship_deactivated_at' => now(),
        ]);
        $this->audit(
            $locked,
            $actor,
            'deactivated',
            ParentChildAccount::VERIFICATION_VERIFIED,
            ParentChildAccount::VERIFICATION_VERIFIED,
            (int) $locked->current_evidence_round,
            null,
            $note,
        );
        $this->notifyAfterCommit($locked->id, 'deactivated');

        return $locked->fresh(['parent', 'child']);
    });
}

public function requestReactivation(ParentChildAccount $relationship, User $actor): ParentChildAccount
{
    return DB::transaction(function () use ($relationship, $actor): ParentChildAccount {
        $locked = ParentChildAccount::query()->lockForUpdate()->findOrFail($relationship->id);
        $this->assertPartyOrAdmin($locked, $actor);

        if ($locked->relationship_status !== ParentChildAccount::STATUS_INACTIVE
            || $locked->relationship_verified_status !== ParentChildAccount::VERIFICATION_VERIFIED) {
            throw new InvalidArgumentException('Only an inactive previously verified relationship can request reactivation.');
        }

        $locked->update([
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'relationship_verification_submitted_at' => now(),
            'relationship_verification_reviewed_by' => null,
            'relationship_verification_reviewed_at' => null,
            'relationship_verification_rejection_reason' => null,
            'relationship_verification_rejection_note' => null,
            'relationship_verification_revoked_at' => null,
            'relationship_deactivated_at' => null,
            'relationship_verified_at' => null,
        ]);
        $this->audit(
            $locked,
            $actor,
            'reactivation_requested',
            ParentChildAccount::VERIFICATION_VERIFIED,
            ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            (int) $locked->current_evidence_round,
        );
        $this->notifyAfterCommit($locked->id, 'reactivation_requested', true);

        return $locked->fresh(['parent', 'child']);
    });
}
```

`assertPartyOrAdmin()` must require an active actor and then require either the admin role or an ID matching `parent_user_id`/`child_user_id`; throw `AuthorizationException` for every other actor.

Replace the audit helper with this signature and store every supplied field in `GuardianRelationshipVerificationAudit`:

```php
private function audit(
    ParentChildAccount $relationship,
    User $actor,
    string $action,
    ?string $previous,
    ?string $new,
    ?int $submissionRound = null,
    ?string $reasonCode = null,
    ?string $notes = null,
): void
```

Implement `notifyAfterCommit(int $relationshipId, string $action, bool $notifyAdmins = false): void` with `DB::afterCommit`; reload the relationship and notify guardian and dependent through `RelationshipVerificationStatusNotification`, then notify admins for submission/reactivation actions. Wrap each existing Laravel notification send in `try/catch (Throwable)` and log only relationship ID, action, target user ID, and exception class so a post-commit notification transport failure cannot turn a committed decision into a misleading request failure.

- [ ] **Step 6: Update notification messages without sensitive evidence content**

Extend `RelationshipVerificationStatusNotification` for `claim_created`, `submitted`, `resubmitted`, `resubmission_required`, `approved`, `rejected`, `claim_closed`, `revoked`, `deactivated`, and `reactivation_requested`. Notification arrays may contain relationship ID, claimed relationship label, status, action URL, and timestamps, but never document names, document contents, government identifiers, or private administrative notes.

- [ ] **Step 7: Run lifecycle and moderation tests**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
```

Expected: PASS after updating existing moderation assertions to the explicit operational status values.

- [ ] **Step 8: Commit the lifecycle state machine**

```powershell
git add app/Services/GuardianRelationshipVerificationService.php app/Notifications/RelationshipVerificationStatusNotification.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
git commit -m "feat: enforce guardian relationship lifecycle"
```

---

---

### Task 5: Replace the Two-File Guardian Submission Form

**Files:**
- Modify: `app/Http/Requests/StoreGuardianRelationshipVerificationRequest.php`
- Modify: `app/Http/Controllers/GuardianRelationshipVerificationController.php`
- Modify: `resources/views/parent/relationship-verifications/show.blade.php`
- Modify: `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

**Interfaces:**
- Consumes: Task 1 pathway policy, Task 3 nested evidence rules, and Task 4 `submit()`.
- Produces: A 1–10 row categorized evidence form with local preview/remove/replace behavior and a guardian-authorized submission endpoint.

- [ ] **Step 1: Add failing HTTP validation and ownership tests**

Add the following three test cases:

```php
public function test_guardian_submits_multiple_categorized_documents(): void
{
    Storage::fake('local');
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');

    $this->actingAs($guardian)->post(route('parent.relationship-verifications.store', $relationship), [
        'documents' => [
            ['document_type' => 'adoption_order', 'document_side' => 'front', 'pairing_key' => null, 'file' => UploadedFile::fake()->image('front.jpg')],
            ['document_type' => 'other_supporting_document', 'document_side' => 'not_applicable', 'pairing_key' => null, 'file' => UploadedFile::fake()->create('support.pdf', 100, 'application/pdf')],
        ],
        'confirm_submission' => '1',
    ])->assertRedirect(route('parent.relationship-verifications.show', $relationship));

    $this->assertDatabaseCount('guardian_relationship_verification_documents', 2);
    $this->assertDatabaseHas('parent_child_accounts', [
        'id' => $relationship->id,
        'relationship_status' => 'pending',
        'relationship_verified_status' => 'under_review',
        'current_evidence_round' => 1,
    ]);
}

public function test_non_owner_cannot_view_submit_or_download_relationship_evidence(): void
{
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
    $other = User::factory()->create();

    $this->actingAs($other)->get(route('parent.relationship-verifications.show', $relationship))->assertForbidden();
    $this->actingAs($other)->post(route('parent.relationship-verifications.store', $relationship), [])->assertForbidden();
}

public function test_evidence_download_is_limited_to_submitting_guardian_and_authorized_admin(): void
{
    Storage::fake('local');
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('adoptive_parent');
    $document = $this->storeEvidenceFor($relationship, $guardian);
    $otherGuardian = $this->approvedGuardian();
    $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);
    $instructor->assignRole('instructor');
    $admin = $this->adminWithRelationshipReviewPermission();

    foreach ([$dependent, $otherGuardian, $instructor] as $unauthorized) {
        $this->actingAs($unauthorized)
            ->get(route('parent.relationship-verifications.documents.show', [$relationship, $document]))
            ->assertForbidden();
    }

    $this->actingAs($guardian)
        ->get(route('parent.relationship-verifications.documents.show', [$relationship, $document]))
        ->assertOk();
    $this->actingAs($admin)
        ->get(route('admin.parent-verifications.relationships.documents.show', [$relationship, $document]))
        ->assertOk();
}

public function test_submission_requires_a_core_pathway_document_and_context_when_configured(): void
{
    [$guardian, $dependent, $relationship] = $this->pendingRelationship('aunt');

    $this->actingAs($guardian)->post(route('parent.relationship-verifications.store', $relationship), [
        'documents' => [[
            'document_type' => 'other_supporting_document',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf'),
        ]],
        'relationship_notes' => '',
        'confirm_submission' => '1',
    ])->assertSessionHasErrors(['documents', 'relationship_notes']);
}
```

Add these helpers to the same test class for the download case:

```php
private function approvedGuardian(): User
{
    $guardian = User::factory()->create([
        'role' => 'learner',
        'status' => User::STATUS_ACTIVE,
        'is_parent_registration' => true,
        'parent_verification_status' => 'approved',
    ]);
    $guardian->assignRole('learner');

    return $guardian;
}

private function adminWithRelationshipReviewPermission(): User
{
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => User::STATUS_ACTIVE,
    ]);
    $admin->assignRole('admin');

    return $admin;
}

private function storeEvidenceFor(
    ParentChildAccount $relationship,
    User $guardian,
): GuardianRelationshipVerificationDocument {
    $storedPaths = [];

    return app(GuardianRelationshipEvidenceService::class)->storeUploadedRound(
        $relationship,
        $guardian,
        1,
        [[
            'document_type' => 'adoption_order',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => UploadedFile::fake()->create('order.pdf', 100, 'application/pdf'),
        ]],
        $storedPaths,
    )->sole();
}
```

- [ ] **Step 2: Run the HTTP evidence tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
```

Expected: FAIL because the request and controller still expect `document` and `supporting_document`.

- [ ] **Step 3: Implement strict request authorization and after-validation**

Update `StoreGuardianRelationshipVerificationRequest::rules()` to return the complete rule set:

```php
public function rules(): array
{
    $relationship = $this->route('parentChildAccount');
    $type = $relationship instanceof ParentChildAccount
        ? $relationship->relationship_type
        : null;

    return array_merge(
        GuardianRelationshipEvidenceRules::for(
            GuardianRelationshipTypes::acceptedDocumentTypes($type),
        ),
        [
            'relationship_notes' => [
                Rule::requiredIf(GuardianRelationshipTypes::requiresCircumstances($type)),
                'nullable',
                'string',
                'max:1000',
            ],
            'confirm_submission' => ['accepted'],
        ],
    );
}
```

`authorize()` must require the authenticated user to own `parent_user_id`, have `status = active`, have `parent_verification_status = approved`, and the relationship verification status to be `pending` or `resubmission_required`.

Implement `after()` as:

```php
public function after(): array
{
    return [function (Validator $validator): void {
        $relationship = $this->route('parentChildAccount');
        if (! $relationship instanceof ParentChildAccount) {
            return;
        }

        $documents = (array) $this->input('documents', []);
        foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
            $validator->errors()->add($field, $message);
        }

        $submittedTypes = collect($documents)->pluck('document_type')->filter()->all();
        $requiredTypes = GuardianRelationshipTypes::requiredDocumentTypes(
            $relationship->relationship_type,
        );

        if (array_intersect($requiredTypes, $submittedTypes) === []) {
            $validator->errors()->add(
                'documents',
                'At least one core document for this verification pathway is required.',
            );
        }
    }];
}
```

Import `Validator` and the evidence rules helper.

- [ ] **Step 4: Pass the normalized array to the lifecycle service**

Update the controller store action:

```php
$validated = $request->validated();

$this->service->submit(
    $parentChildAccount,
    $request->user(),
    $validated['documents'],
    $validated['relationship_notes'] ?? null,
);
```

In `show()`, eager-load documents ordered by `submission_round DESC, display_order ASC`, pass pathway label, document options, required core types, and whether circumstances are required. The shared guardian authorization helper must require ownership, `User::STATUS_ACTIVE`, and approved guardian identity for show/store/document actions. Keep document downloads private and use this response branch:

```php
return $request->boolean('inline')
    ? Storage::disk($document->disk)->response(
        $document->path,
        $document->original_name,
        [],
        'inline',
    )
    : Storage::disk($document->disk)->download(
        $document->path,
        $document->original_name,
    );
```

Both guardian and admin document actions must first verify `(int) $document->parent_child_account_id === (int) $relationship->id` before returning the file.

- [ ] **Step 5: Build the Alpine evidence-row interface**

Replace the fixed primary/supporting inputs with one Alpine-managed list. Each row must submit these names:

```html
<select :name="`documents[${index}][document_type]`" required></select>
<select :name="`documents[${index}][document_side]`" required>
    <option value="not_applicable">Not applicable</option>
    <option value="front">Front</option>
    <option value="back">Back</option>
</select>
<input :name="`documents[${index}][pairing_key]`" type="hidden" :value="row.pairingKey">
<input :name="`documents[${index}][file]`" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
```

Each row starts with `{ documentType: '', side: 'not_applicable', pairingKey: null, previewUrl: null }`. Provide an “Add back side” action only on an unpaired row: set that row to `front`, assign `crypto.randomUUID()` to its `pairingKey`, and insert a `back` row with the same document type and pairing key. Prevent a second front or back row for that key in the browser; the Task 3 server validation remains authoritative.

The component must initialize one row, allow up to ten, create image/PDF object-URL previews, revoke old object URLs on replacement/removal and page teardown, disable removal when only one row remains, clear a pairing key when a row returns to `not_applicable`, and show required/optional pathway guidance. Submitted rounds render grouped and read-only above the form.

- [ ] **Step 6: Run evidence tests and render assertions**

Run:

```powershell
php artisan test tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php
```

Expected: PASS. Add view assertions for `documents[`, `Add another document`, `Administrative verification`, and the pathway label.

- [ ] **Step 7: Commit the multi-document guardian form**

```powershell
git add app/Http/Requests/StoreGuardianRelationshipVerificationRequest.php app/Http/Controllers/GuardianRelationshipVerificationController.php resources/views/parent/relationship-verifications/show.blade.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php
git commit -m "feat: add multi-document guardian submissions"
```

---

---

### Task 6: Integrate Verification Into Dependent Account Creation

**Files:**
- Create: `app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php`
- Modify: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Modify: `app/Services/ParentChildVerificationService.php`
- Modify: `resources/views/auth/child/step5-relationship-verification.blade.php`
- Modify: `tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php`
- Modify: `tests/Feature/Auth/ParentChildVerificationResubmissionTest.php`

**Interfaces:**
- Consumes: Task 3 evidence rules and Task 4 submission service.
- Produces: All new dependent registrations create a pending relationship and submit an evidence round without conflating relationship approval with child-account approval.

- [ ] **Step 1: Write failing biological, adoptive, and non-parent registration tests**

Add feature cases that complete the existing child registration session and assert:

```php
$this->assertDatabaseHas('parent_child_accounts', [
    'parent_user_id' => $guardian->id,
    'child_user_id' => $child->id,
    'relationship_type' => 'biological_mother',
    'verification_pathway' => 'biological_parent',
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'under_review',
    'can_view_progress' => true,
    'can_view_quiz_answers' => true,
    'can_approve_content' => false,
    'verification_status' => 'pending',
]);
```

The adoptive case must submit at least two documents and assert both have the same `submission_round`. The aunt case must omit `relationship_notes` once, assert validation failure, then submit a core care/custody category plus context successfully. Also assert that child-account approval alone does not change `relationship_verified_status` to `verified`.

- [ ] **Step 2: Run registration tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php
```

Expected: FAIL because biological types currently skip Step 5, the form accepts two fixed files, and child approval can activate the relationship.

- [ ] **Step 3: Add a dedicated Step 5 form request**

Create `StoreChildRelationshipVerificationRequest` using the relationship type stored in `session('child_step1.relationship_type')`:

```php
public function authorize(): bool
{
    $guardian = $this->user();

    return $guardian instanceof User
        && $guardian->status === User::STATUS_ACTIVE
        && $guardian->parent_verification_status === 'approved';
}

public function rules(): array
{
    $type = (string) session('child_step1.relationship_type', '');

    return array_merge(
        GuardianRelationshipEvidenceRules::for(
            GuardianRelationshipTypes::acceptedDocumentTypes($type),
        ),
        [
            'relationship_notes' => [
                Rule::requiredIf(GuardianRelationshipTypes::requiresCircumstances($type)),
                'nullable',
                'string',
                'max:1000',
            ],
            'confirm_submission' => ['accepted'],
        ],
    );
}

public function after(): array
{
    return [function (Validator $validator): void {
        $type = (string) session('child_step1.relationship_type', '');
        $documents = (array) $this->input('documents', []);

        foreach (GuardianRelationshipEvidenceRules::metadataErrors($documents) as $field => $message) {
            $validator->errors()->add($field, $message);
        }

        $requiredTypes = GuardianRelationshipTypes::requiredDocumentTypes($type);
        $submittedTypes = collect($documents)
            ->pluck('document_type')
            ->filter()
            ->all();

        if (array_intersect($requiredTypes, $submittedTypes) === []) {
            $validator->errors()->add(
                'documents',
                'At least one core document for this verification pathway is required.',
            );
        }
    }];
}
```

Import `User`, `GuardianRelationshipEvidenceRules`, `GuardianRelationshipTypes`, `Rule`, and `Validator`.

- [ ] **Step 4: Route every selectable relationship through Step 5**

In `storeChildValidation()`, replace the conditional bypass with:

```php
$relationshipType = (string) ($step1['relationship_type'] ?? '');

if (! in_array($relationshipType, GuardianRelationshipTypes::selectableValues(), true)) {
    return redirect()->route('parent.create-child')
        ->withErrors(['relationship_type' => 'Select a supported guardian relationship.']);
}

return redirect()->route('parent.create-child.relationship-verification');
```

The Step 5 GET route must reject legacy `parent` and provide pathway label, accepted categories, required core categories, and circumstances requirements to the view.

- [ ] **Step 5: Normalize Step 5 documents and create a pending relationship**

Type-hint `StoreChildRelationshipVerificationRequest` in `storeChildRelationshipVerification()` and pass:

```php
return $this->createChildAccountFromSession(
    $request,
    app(RegistrationTempUploadService::class),
    [
        'documents' => $request->validated('documents'),
        'relationship_notes' => $request->validated('relationship_notes', null),
    ],
);
```

Create the relationship with:

```php
[
    'parent_user_id' => $parent->id,
    'child_user_id' => $child->id,
    'can_view_progress' => true,
    'can_view_quiz_answers' => true,
    'can_approve_content' => false,
    'relationship_type' => $step1['relationship_type'],
    'relationship_custom' => $step1['relationship_type'] === GuardianRelationshipTypes::OTHER
        ? ($step1['relationship_custom'] ?? null)
        : null,
    'verification_pathway' => GuardianRelationshipTypes::pathway($step1['relationship_type']),
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'pending',
    'current_evidence_round' => 0,
    'relationship_notes' => $relationshipVerificationPayload['relationship_notes'] ?? null,
    'is_legacy_relationship' => false,
    'verification_status' => VerificationStatus::Pending->value,
    'verification_document_path' => $verificationDocumentPath,
    'relationship_verified_at' => null,
]
```

Move the existing `User`, `LearnerProfile`, and `ParentChildAccount` writes into one outer database transaction. Pass the lifecycle service’s optional stored-path reference so a failed outer commit cannot leave evidence files behind:

```php
$relationshipEvidencePaths = [];

try {
    [$child, $verification] = DB::transaction(function () use (
        $parent,
        $step1,
        $step2,
        $step3,
        $childEmail,
        $barangay,
        $verificationDocumentPath,
        $relationshipVerificationPayload,
        &$relationshipEvidencePaths,
    ): array {
        $child = User::query()->create([
            'name' => trim($step1['first_name'].' '.$step1['last_name']),
            'first_name' => $step1['first_name'],
            'middle_initial' => $step1['middle_initial'] ?? null,
            'last_name' => $step1['last_name'],
            'suffix' => $step1['suffix'] ?? null,
            'email' => $childEmail,
            'birthdate' => $step1['birthdate'],
            'age' => $step1['age'],
            'password' => Hash::make($step3['password']),
            'email_verified_at' => now(),
        ]);
        Role::findOrCreate('learner', 'web');
        $child->assignRole('learner');
        $child->learnerProfile()->create([
            'username' => $step3['username'],
            'birthdate' => $child->birthdate,
            'gender' => $step1['gender'],
            'city_code' => $step2['city_code'],
            'barangay_code' => $step2['barangay_code'],
            'barangay' => $barangay->name,
            'province_code' => '402100000',
            'requires_parental_consent' => true,
        ]);

        $verification = ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'relationship_type' => $step1['relationship_type'],
            'relationship_custom' => $step1['relationship_type'] === GuardianRelationshipTypes::OTHER
                ? ($step1['relationship_custom'] ?? null)
                : null,
            'verification_pathway' => GuardianRelationshipTypes::pathway($step1['relationship_type']),
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'current_evidence_round' => 0,
            'relationship_notes' => $relationshipVerificationPayload['relationship_notes'] ?? null,
            'is_legacy_relationship' => false,
            'verification_status' => VerificationStatus::Pending->value,
            'verification_document_path' => $verificationDocumentPath,
            'relationship_verified_at' => null,
        ]);

        app(GuardianRelationshipVerificationService::class)->submit(
            $verification,
            $parent,
            $relationshipVerificationPayload['documents'],
            $relationshipVerificationPayload['relationship_notes'] ?? null,
            $relationshipEvidencePaths,
        );

        return [$child, $verification->fresh()];
    });
} catch (Throwable $exception) {
    Storage::disk('public')->delete($verificationDocumentPath);
    app(GuardianRelationshipEvidenceService::class)
        ->deleteStoredPaths($relationshipEvidencePaths);

    throw $exception;
}
```

Import `DB`, `Storage`, `Throwable`, and `GuardianRelationshipEvidenceService`. Run admin notification and session cleanup only after this transaction succeeds; do not add DTOs or repositories for these unchanged arrays.

- [ ] **Step 6: Replace the dependent Step 5 fixed fields**

Reuse the Task 5 Alpine row contract in `resources/views/auth/child/step5-relationship-verification.blade.php`. The page must distinguish dependent account validation from relationship evidence and state:

```text
These files are reviewed as evidence for this Guardian–Dependent relationship. Submission does not create a legal determination and does not activate guardian access.
```

- [ ] **Step 7: Stop child-account moderation from approving relationships**

In `ParentChildVerificationService::approveChild()`, keep `verification_status`, child review fields, and child notification updates, but remove writes to `relationship_status` and `relationship_verified_at`. Add a regression assertion that relationship approval still requires `GuardianRelationshipVerificationService::approve()`.

- [ ] **Step 8: Run dependent-registration tests**

Run:

```powershell
php artisan test tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
```

Expected: PASS.

- [ ] **Step 9: Commit dependent-registration integration**

```powershell
git add app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Services/ParentChildVerificationService.php resources/views/auth/child/step5-relationship-verification.blade.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
git commit -m "feat: verify every dependent relationship claim"
```

---

---

### Task 7: Centralize Multi-Guardian Authorization

**Files:**
- Modify: `app/Models/User.php`
- Modify: `app/Policies/ParentChildPolicy.php`
- Modify: `app/Http/Controllers/ParentController.php`
- Modify: `app/Http/Controllers/Learner/DashboardController.php`
- Modify: `app/Http/Controllers/Learner/ParentVisibilityController.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/UserController.php`
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`
- Modify: `app/Services/Chat/ChatAuthorizationService.php`
- Modify: `app/Services/Moderation/SuspensionAppealService.php`
- Test: `tests/Feature/ParentChildMonitoringTest.php`
- Test: `tests/Unit/Chat/ChatAuthorizationServiceTest.php`
- Test: `tests/Feature/Moderation/SuspensionAppealSubmissionTest.php`
- Test: `tests/Feature/Instructor/DashboardTest.php`

**Interfaces:**
- Consumes: Task 2 `verifiedActive()`, `accessEligible()`, and `withPermission()` scopes.
- Produces: Collection-based guardian relations, `accessibleChildLinks()`, `accessibleGuardianLinks()`, and one authorization definition shared by monitoring, chat, moderation, learner, and instructor consumers.

- [ ] **Step 1: Write failing authorization-matrix tests**

Add cases proving that:

```php
// Pending, rejected, inactive, and revoked relationships cannot view progress.
$this->actingAs($guardian)->get(route('parent.children.show', $dependent))->assertForbidden();

// A verified relationship without can_view_quiz_answers cannot open an attempt.
$relationship->update([
    'relationship_status' => 'active',
    'relationship_verified_status' => 'verified',
    'relationship_verified_at' => now(),
    'can_view_quiz_answers' => false,
]);
$this->actingAs($guardian)
    ->get(route('parent.children.quiz-attempts.show', [$dependent, $attempt]))
    ->assertForbidden();

// Suspending the guardian disables access without mutating other relationships.
$guardian->update(['status' => User::STATUS_SUSPENDED]);
$this->actingAs($guardian)->get(route('parent.children.show', $dependent))->assertForbidden();
```

In `ChatAuthorizationServiceTest`, assert only an access-eligible relationship qualifies a guardian/dependent chat pair and a guardian’s dependent-to-instructor relation. In the suspension appeal test, assert an inactive/revoked guardian cannot post as the dependent’s guardian. In the instructor test, assert pending/revoked links are excluded from guardian classifications and displayed connections.

- [ ] **Step 2: Run the authorization tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/ParentChildMonitoringTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Moderation/SuspensionAppealSubmissionTest.php tests/Feature/Instructor/DashboardTest.php
```

Expected: FAIL because consumers currently use `verification_status`, `relationship_verified_at`, or relationship existence independently.

- [ ] **Step 3: Add collection-based user relations**

Add to `User`:

```php
public function guardians(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'parent_child_accounts', 'child_user_id', 'parent_user_id')
        ->withPivot([
            'id', 'relationship_type', 'relationship_custom', 'verification_pathway',
            'relationship_status', 'relationship_verified_status', 'current_evidence_round',
            'can_view_progress', 'can_view_quiz_answers', 'can_approve_content',
            'relationship_verified_at', 'deleted_at',
        ])
        ->wherePivotNull('deleted_at')
        ->withTimestamps();
}

public function accessibleChildLinks(): HasMany
{
    return $this->hasMany(ParentChildAccount::class, 'parent_user_id')->accessEligible();
}

public function accessibleGuardianLinks(): HasMany
{
    return $this->hasMany(ParentChildAccount::class, 'child_user_id')->accessEligible();
}

public function isParent(): bool
{
    return $this->accessibleChildLinks()->exists();
}
```

Import `BelongsToMany` and `HasMany`. Keep `parent()` and `parentChildLink()` only as deprecated compatibility adapters for this phase. Do not add new callers, and migrate every existing security-sensitive caller to `guardians()`, `parentLinks()`, or the accessible link scopes.

- [ ] **Step 4: Replace the monitoring policy with one relationship query**

Implement `ParentChildPolicy::view()` as:

```php
public function view(User $guardian, User $dependent): bool
{
    return ParentChildAccount::query()
        ->accessEligible()
        ->withPermission('can_view_progress')
        ->where('parent_user_id', $guardian->id)
        ->where('child_user_id', $dependent->id)
        ->exists();
}
```

In `ParentController`, fetch the authorized `ParentChildAccount` model with the same pair and `accessEligible()` scope, then read permissions directly from the model rather than an unfiltered pivot.

- [ ] **Step 5: Replace every security-sensitive ad hoc condition**

Use this query shape in chat, suspension appeals, learner dashboards, parent visibility, module approval checks, and instructor relationship displays:

```php
ParentChildAccount::query()
    ->accessEligible()
    ->where('parent_user_id', $guardianId)
    ->where('child_user_id', $dependentId);
```

Apply `withPermission('can_approve_content')` to enrollment approval flows and `withPermission('can_view_quiz_answers')` to quiz-answer flows. Instructor-facing relationship summaries must start from `accessEligible()` and must not display pending, rejected, inactive, or revoked guardian links.

After editing, run this literal audit and inspect every result:

```powershell
rg -n "whereNotNull\('relationship_verified_at'\)|wherePivot\('verification_status'|hasVerifiedRelationshipRequirement\(|parentChildLink\(" app
```

No security-sensitive caller may remain on those legacy predicates. The only permitted occurrences are explicit legacy compatibility or child-account-verification code with an explanatory comment.

- [ ] **Step 6: Run the authorization matrix**

Run:

```powershell
php artisan test tests/Feature/ParentChildMonitoringTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Moderation/SuspensionAppealSubmissionTest.php tests/Feature/Instructor/DashboardTest.php tests/Feature/Chat/ChatHttpFlowTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit centralized authorization**

```powershell
git add app/Models/User.php app/Policies/ParentChildPolicy.php app/Http/Controllers/ParentController.php app/Http/Controllers/Learner/DashboardController.php app/Http/Controllers/Learner/ParentVisibilityController.php app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Instructor/UserController.php app/Http/Controllers/Instructor/EnrollmentController.php app/Services/Chat/ChatAuthorizationService.php app/Services/Moderation/SuspensionAppealService.php tests/Feature/ParentChildMonitoringTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Moderation/SuspensionAppealSubmissionTest.php tests/Feature/Instructor/DashboardTest.php tests/Feature/Chat/ChatHttpFlowTest.php
git commit -m "fix: require verified active guardian links"
```

---

---

### Task 8: Route Administration and Self-Service Through the Lifecycle

**Files:**
- Create: `app/Http/Requests/DeactivateGuardianRelationshipRequest.php`
- Create: `app/Http/Requests/Admin/UpdateGuardianRelationshipPermissionsRequest.php`
- Create: `app/Http/Controllers/GuardianRelationshipLifecycleController.php`
- Modify: `app/Http/Requests/Admin/AttachParentChildRequest.php`
- Modify: `app/Http/Requests/Admin/ReviewGuardianRelationshipVerificationRequest.php`
- Modify: `app/Http/Controllers/Admin/ParentChildVerificationController.php`
- Modify: `app/Http/Controllers/Admin/UserRelationshipAdminController.php`
- Modify: `app/Services/Admin/UserRelationshipService.php`
- Modify: `resources/views/admin/parent-verifications/index.blade.php`
- Modify: `resources/views/admin/parent-verifications/show-relationship.blade.php`
- Modify: `resources/views/admin/users/relationships/index.blade.php`
- Modify: `routes/web.php`
- Modify: `routes/admin.php`
- Delete: `app/Http/Requests/Admin/ToggleParentChildVerificationRequest.php`
- Test: `tests/Feature/Admin/AdminParentChildVerificationUiTest.php`
- Test: `tests/Feature/Admin/AdminUserRelationshipMutationTest.php`
- Test: `tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php`
- Test: `tests/Feature/GuardianRelationshipLifecycleTest.php`

**Interfaces:**
- Consumes: Task 4 lifecycle operations and Task 7 access rules.
- Produces: Central review-only approval/rejection/revocation, pending admin-created claims, evidence-round review UI, and party-authorized deactivation/reactivation.

- [ ] **Step 1: Write failing administration and self-service tests**

Add assertions that:

```php
// Admin attachment creates a claim, never an active verified relationship.
$this->actingAs($admin)->post(route('admin.users.relationships.attach'), [
    'parent_user_id' => $guardian->id,
    'child_user_id' => $dependent->id,
    'relationship_type' => 'aunt',
    'relationship_notes' => 'Provides day-to-day care under the submitted arrangement.',
    'can_view_progress' => 1,
    'can_view_quiz_answers' => 1,
])->assertRedirect();

$this->assertDatabaseHas('parent_child_accounts', [
    'parent_user_id' => $guardian->id,
    'child_user_id' => $dependent->id,
    'verification_pathway' => 'non_parental_care',
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'pending',
    'can_approve_content' => false,
]);

// Direct toggle route is gone.
$this->actingAs($admin)
    ->patch('/admin/users/relationships/verification', [
        'parent_user_id' => $guardian->id,
        'child_user_id' => $dependent->id,
        'is_verified' => 1,
    ])->assertNotFound();

// Either relationship party can deactivate, but an unrelated user cannot.
$this->actingAs($dependent)
    ->post(route('guardian-relationships.deactivate', $relationship), ['note' => 'No longer needed.'])
    ->assertRedirect();
$this->actingAs($unrelated)
    ->post(route('guardian-relationships.reactivate', $relationship))
    ->assertForbidden();
```

Add review-page assertions for pathway label, grouped evidence rounds, current-round marker, administrative-verification disclaimer, and the absence of storage paths/hashes.

Add a permission test that updates Guardian A while Guardian B is linked to the same dependent, then asserts only Guardian A’s three permission fields changed and an audit row with action `permissions_updated` was created.

Add an attachment regression that soft-deletes a historical row, attaches the same guardian/dependent pair, and asserts the original row ID is restored as pending instead of causing the existing unique pair constraint to fail.

- [ ] **Step 2: Run administration tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminUserRelationshipMutationTest.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php tests/Feature/GuardianRelationshipLifecycleTest.php
```

Expected: FAIL because admin attachment can activate links, direct toggle still exists, and lifecycle routes do not exist.

- [ ] **Step 3: Remove direct verification controls**

Remove `is_verified` from `AttachParentChildRequest`, delete `ToggleParentChildVerificationRequest`, remove the verification toggle route and controller method, and replace “Toggle Verification” in the relationship-management view with a link to:

```php
route('admin.parent-verifications.relationships.show', $relationship)
```

Update `UserRelationshipService::attachParentChild()` to always create:

```php
[
    'relationship_type' => $payload['relationship_type'],
    'relationship_custom' => $payload['relationship_type'] === GuardianRelationshipTypes::OTHER
        ? trim((string) ($payload['relationship_custom'] ?? ''))
        : null,
    'verification_pathway' => GuardianRelationshipTypes::pathway($payload['relationship_type']),
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'pending',
    'current_evidence_round' => 0,
    'relationship_notes' => $payload['relationship_notes'] ?? null,
    'can_view_progress' => (bool) ($payload['can_view_progress'] ?? true),
    'can_view_quiz_answers' => (bool) ($payload['can_view_quiz_answers'] ?? true),
    'can_approve_content' => false,
    'relationship_verified_at' => null,
    'is_legacy_relationship' => false,
]
```

Resolve the pair with `ParentChildAccount::withTrashed()->lockForUpdate()` before writing and reject a live existing pair. For a soft-deleted row, call `restore()` and apply:

```php
[
    'relationship_type' => $payload['relationship_type'],
    'relationship_custom' => $payload['relationship_type'] === GuardianRelationshipTypes::OTHER
        ? trim((string) ($payload['relationship_custom'] ?? ''))
        : null,
    'verification_pathway' => GuardianRelationshipTypes::pathway($payload['relationship_type']),
    'relationship_status' => ParentChildAccount::STATUS_PENDING,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
    'relationship_notes' => $payload['relationship_notes'] ?? null,
    'can_view_progress' => (bool) ($payload['can_view_progress'] ?? true),
    'can_view_quiz_answers' => (bool) ($payload['can_view_quiz_answers'] ?? true),
    'can_approve_content' => false,
    'relationship_verification_submitted_at' => null,
    'relationship_verification_reviewed_by' => null,
    'relationship_verification_reviewed_at' => null,
    'relationship_verification_rejection_reason' => null,
    'relationship_verification_rejection_note' => null,
    'relationship_verification_revoked_at' => null,
    'relationship_deactivated_at' => null,
    'relationship_verified_at' => null,
    'is_legacy_relationship' => false,
]
```

Do not reset `current_evidence_round` or delete existing evidence/audit rows, so the next submission becomes a new round. If no row exists, create it with the preceding new-claim array and `current_evidence_round = 0`.

For a successful admin-created or restored claim, add a `claim_created` audit row and register an after-commit `RelationshipVerificationStatusNotification` for both parties using the same failure-safe notification handling as Task 4.

Change `UserRelationshipService::detachParentChild()` to accept `User $admin` and `?string $note` instead of a bare actor ID, update its controller call accordingly, and use this exact dispatch while preserving relationship and evidence history:

```php
if ($relationship->isVerifiedActive()) {
    $verificationService->deactivate($relationship, $admin, $note);
} elseif ($relationship->relationship_verified_status === ParentChildAccount::VERIFICATION_UNDER_REVIEW) {
    $verificationService->reject($relationship, $admin, 'claim_closed', $note, false);
} elseif (in_array($relationship->relationship_verified_status, [
    ParentChildAccount::VERIFICATION_PENDING,
    ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
], true)) {
    $verificationService->closePendingClaim($relationship, $admin, $note);
}
```

For relationships already rejected, revoked, or inactive, return a validation error and leave the historical row unchanged.

- [ ] **Step 4: Add party-authorized lifecycle routes**

Create `DeactivateGuardianRelationshipRequest` with `note => nullable|string|max:500` and authorization requiring the current user to be the relationship’s guardian, dependent, or an administrator.

Create `GuardianRelationshipLifecycleController`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeactivateGuardianRelationshipRequest;
use App\Models\ParentChildAccount;
use App\Services\GuardianRelationshipVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuardianRelationshipLifecycleController extends Controller
{
    public function __construct(private readonly GuardianRelationshipVerificationService $service) {}

    public function deactivate(
        DeactivateGuardianRelationshipRequest $request,
        ParentChildAccount $parentChildAccount,
    ): RedirectResponse {
        $this->service->deactivate(
            $parentChildAccount,
            $request->user(),
            $request->validated('note'),
        );

        return back()->with('success', 'Guardian relationship deactivated.');
    }

    public function reactivate(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse
    {
        abort_unless(in_array((int) $request->user()->id, [
            (int) $parentChildAccount->parent_user_id,
            (int) $parentChildAccount->child_user_id,
        ], true) || $request->user()->hasRole('admin'), 403);

        $this->service->requestReactivation($parentChildAccount, $request->user());

        return back()->with('success', 'Relationship reactivation submitted for administrative review.');
    }
}
```

Register named POST routes `guardian-relationships.deactivate` and `guardian-relationships.reactivate` inside the authenticated/verified route group, outside guardian-only middleware so the dependent can use them.

- [ ] **Step 5: Harden centralized review decisions**

Keep the existing admin relationship routes. Ensure `approveRelationship`, `rejectRelationship`, and `revokeRelationship` delegate only to Task 4 service methods. `ReviewGuardianRelationshipVerificationRequest` must authorize `manage user relationships` or admin role and preserve configured reason-code/note validation.

Add index filters for `verification_pathway`, `relationship_status`, and `relationship_verified_status`. Eager-load relationship documents ordered by round and display order, plus audit actors.

Create `UpdateGuardianRelationshipPermissionsRequest` with three required boolean fields: `can_view_progress`, `can_view_quiz_answers`, and `can_approve_content`. Authorize the same `manage user relationships` permission or admin role used by review actions.

Add a named PATCH route `admin.users.relationships.permissions` and a thin controller action that calls:

```php
updateParentChildPermissions(
    int $parentId,
    int $childId,
    array $permissions,
    User $admin,
    ?Request $request = null,
): ParentChildAccount
```

In that service method, lock the selected row, require the active admin role and `isVerifiedActive()`, update only those three booleans, retain the existing admin activity log, and create this relationship audit entry:

```php
GuardianRelationshipVerificationAudit::query()->create([
    'parent_child_account_id' => $relationship->id,
    'actor_user_id' => $admin->id,
    'action' => 'permissions_updated',
    'previous_status' => $relationship->relationship_verified_status,
    'new_status' => $relationship->relationship_verified_status,
    'submission_round' => $relationship->current_evidence_round,
    'notes' => json_encode([
        'before' => $before,
        'after' => $relationship->only([
            'can_view_progress',
            'can_view_quiz_answers',
            'can_approve_content',
        ]),
    ], JSON_THROW_ON_ERROR),
]);
```

Capture `$before` before `update()` with:

```php
$before = $relationship->only([
    'can_view_progress',
    'can_view_quiz_answers',
    'can_approve_content',
]);
```

This audit contains permission booleans only—never evidence metadata or personal information.

- [ ] **Step 6: Group evidence rounds in the review UI**

In `show-relationship.blade.php`, group documents with:

```php
$evidenceRounds = $relationship->verificationDocuments
    ->groupBy('submission_round')
    ->sortKeysDesc();
```

Render the current round first, mark previous rounds read-only, display category/side/uploader/date, preserve image/PDF preview and zoom controls, and add:

```text
This is an administrative verification of submitted identity and relationship evidence. It is not a legal determination of parenthood, adoption, custody, or guardianship.
```

Never render `path` or `content_sha256`.

- [ ] **Step 7: Run administration tests**

Run:

```powershell
php artisan test tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminUserRelationshipMutationTest.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/GuardianRelationshipLifecycleTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit lifecycle administration**

```powershell
git add app/Http/Requests/DeactivateGuardianRelationshipRequest.php app/Http/Requests/Admin/UpdateGuardianRelationshipPermissionsRequest.php app/Http/Controllers/GuardianRelationshipLifecycleController.php app/Http/Requests/Admin/AttachParentChildRequest.php app/Http/Requests/Admin/ReviewGuardianRelationshipVerificationRequest.php app/Http/Controllers/Admin/ParentChildVerificationController.php app/Http/Controllers/Admin/UserRelationshipAdminController.php app/Services/Admin/UserRelationshipService.php resources/views/admin/parent-verifications/index.blade.php resources/views/admin/parent-verifications/show-relationship.blade.php resources/views/admin/users/relationships/index.blade.php routes/web.php routes/admin.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminUserRelationshipMutationTest.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/GuardianRelationshipLifecycleTest.php
git add -u app/Http/Requests/Admin/ToggleParentChildVerificationRequest.php
git commit -m "feat: centralize guardian relationship review"
```

---

---

### Task 9: Preserve Existing-Learner Invitation Compatibility

**Files:**
- Modify: `app/Services/GuardianRelationshipEvidenceService.php`
- Modify: `app/Services/GuardianRelationshipVerificationService.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Modify: `app/Http/Controllers/ParentInvitationController.php`
- Modify: `app/Http/Requests/Parent/SendParentChildInvitationRequest.php`
- Modify: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`

**Interfaces:**
- Consumes: Task 3 evidence metadata and Task 4 state transitions.
- Produces: `GuardianRelationshipEvidenceService::storeStagedRound(ParentChildAccount, User, int, array, array &$moves): Collection`, `restoreStagedDocuments(array &$moves): void`, and `GuardianRelationshipVerificationService::submitStaged(ParentChildAccount, User, array, ?Closure, ?array &$moves): ParentChildAccount` using the same round semantics as direct uploads.

- [ ] **Step 1: Add failing invitation compatibility tests**

Update invitation tests to assert:

```php
$this->assertDatabaseHas('parent_child_accounts', [
    'parent_user_id' => $guardian->id,
    'child_user_id' => $learner->id,
    'verification_pathway' => 'biological_parent',
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'under_review',
    'current_evidence_round' => 1,
    'can_approve_content' => false,
]);

$this->assertDatabaseHas('guardian_relationship_verification_documents', [
    'parent_child_account_id' => $relationship->id,
    'submission_round' => 1,
    'document_type' => 'civil_registry_record',
    'document_side' => 'not_applicable',
]);
```

Also retain existing tests for no relationship before acceptance, the learner’s independent dashboard/chat access while an invitation is pending, missing staged-file rollback, stale decision locking, rejection without relationship creation, and compensation after transaction failure. Add an explicit assertion that invitation acceptance does not grant relationship-derived guardian chat or monitoring access before administrative approval. Add rejection, cancellation, and expiration cases asserting staged files are removed after the status commit and `relationship_verification_documents` is cleared.

- [ ] **Step 2: Run invitation tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Parent/ParentChildInvitationFlowTest.php
```

Expected: FAIL because biological types currently bypass documents and staged evidence lacks round metadata.

- [ ] **Step 3: Normalize the existing two-file invitation payload**

Keep the Phase 1 invitation UI at one primary and one optional supporting file. Update `SendParentChildInvitationRequest` to use `selectableValues()`, require evidence for every type, use pathway document categories, and reject legacy `parent`.

Normalize the controller/service payload to:

```php
[
    'documents' => array_values(array_filter([
        [
            'document_type' => (string) $request->string('relationship_document_type'),
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => $request->file('relationship_document'),
        ],
        $request->hasFile('relationship_supporting_document') ? [
            'document_type' => 'other_supporting_document',
            'document_side' => 'not_applicable',
            'pairing_key' => null,
            'file' => $request->file('relationship_supporting_document'),
        ] : null,
    ])),
]
```

Each staged JSON item must contain `document_type`, `document_side`, `pairing_key`, `display_order`, `content_sha256`, `disk`, `path`, `original_name`, `mime_type`, and `size_bytes`.

- [ ] **Step 4: Add staged-round storage and compensation**

Implement this exact staged evidence contract in `GuardianRelationshipEvidenceService`:

```php
public function storeStagedRound(
    ParentChildAccount $relationship,
    User $guardian,
    int $round,
    array $documents,
    array &$moves,
): Collection

public function restoreStagedDocuments(array &$moves): void
```

`storeStagedRound()` must validate every item’s `disk === 'local'`, require an existing path under `guardian-relationship-invitations/`, validate the configured document type and `GuardianRelationshipEvidenceRules::metadataErrors()`, recompute SHA-256 from the staged file, require `hash_equals($document['content_sha256'], $recomputedHash)`, reject duplicate hashes, move each file to a UUID filename under `guardian-relationship-verifications/{$relationship->id}/round-{$round}`, create one document row per item, and append `['source' => $source, 'destination' => $destination]` to `$moves` after each successful move.

Implement `restoreStagedDocuments(array &$moves): void` by iterating the moves in reverse: move each existing destination back when the source is absent; delete the destination when the source already exists; log a path-only error if restoration fails; clear the move list afterward. Do not log document contents or user-supplied notes.

Add `GuardianRelationshipVerificationService::submitStaged()` with this signature:

```php
public function submitStaged(
    ParentChildAccount $relationship,
    User $guardian,
    array $documents,
    ?Closure $onSubmitted = null,
    ?array &$movedDocuments = null,
): ParentChildAccount
```

Use the same row lock, guardian ownership check, `pending`/`resubmission_required` source states, round increment, status updates, audit entry, and after-commit notifications as `submit()`. Invoke `$onSubmitted` inside the transaction after evidence rows and relationship state are updated. On any exception, call `$this->evidence->restoreStagedDocuments($movedDocuments)` before rethrowing. Remove the old staged move/create/restore helpers from `GuardianRelationshipVerificationService` after the evidence service owns them.

- [ ] **Step 5: Make acceptance create only a pending reviewed relationship**

On acceptance, set:

```php
[
    'can_view_progress' => true,
    'can_view_quiz_answers' => true,
    'can_approve_content' => false,
    'relationship_type' => $relationshipType,
    'relationship_custom' => $invitation->relationship_custom,
    'verification_pathway' => GuardianRelationshipTypes::pathway($relationshipType),
    'relationship_status' => 'pending',
    'relationship_verified_status' => 'pending',
    'current_evidence_round' => 0,
    'is_legacy_relationship' => false,
    'verification_document_path' => null,
    'relationship_verified_at' => null,
]
```

Remove the new-relationship declaration branch. Every selectable type transfers staged evidence through `submitStaged()`. Preserve the existing invitation update inside the same transaction callback so acceptance cannot finalize without successful evidence transfer.

For rejected, cancelled, or expired invitations, copy the staged JSON array before clearing it on the invitation row, commit the invitation state, then delete those private staged paths in a `DB::afterCommit` callback. Catch deletion failures and log invitation ID plus path only. Never delete staged files before the state change commits, and never delete evidence already transferred to a relationship round.

- [ ] **Step 6: Run the invitation and chat regression tests**

Run:

```powershell
php artisan test tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatHttpFlowTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit invitation compatibility**

```powershell
git add app/Services/GuardianRelationshipEvidenceService.php app/Services/GuardianRelationshipVerificationService.php app/Services/ParentChildInvitationService.php app/Http/Controllers/ParentInvitationController.php app/Http/Requests/Parent/SendParentChildInvitationRequest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatHttpFlowTest.php
git commit -m "fix: preserve secure guardian invitation review"
```

---

---

### Task 10: Normalize Seed Data and Run End-to-End Regression QA

**Files:**
- Modify: `database/seeders/ParentMonitoringSeeder.php`
- Modify: `database/seeders/TestUserSeeder.php`
- Modify: `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`
- Modify: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`
- Modify: `tests/Feature/ParentChildMonitoringTest.php`
- Create during execution: `docs/superpowers/verification/2026-09-07-guardian-dependent-relationship-verification-e2e.md`

**Interfaces:**
- Consumes: All Phase 1 interfaces and flows.
- Produces: Representative verified seed relationships, a passing full regression suite, and a recorded browser/E2E verification report.

- [ ] **Step 1: Add a final multiple-guardian end-to-end feature test**

Create one scenario that:

1. creates one dependent and two approved guardian identities;
2. submits and approves Guardian A;
3. submits and approves Guardian B;
4. confirms both relationships are independently active;
5. gives Guardian A and Guardian B different permissions;
6. revokes Guardian A;
7. confirms Guardian A loses access;
8. confirms Guardian B retains access;
9. confirms the dependent account remains active;
10. confirms both audit timelines remain distinct.

Use these final assertions:

```php
$this->assertSame(2, ParentChildAccount::query()->where('child_user_id', $dependent->id)->count());
$this->assertFalse(ParentChildAccount::accessEligible()->whereKey($guardianARelationship->getKey())->exists());
$this->assertTrue(ParentChildAccount::accessEligible()->whereKey($guardianBRelationship->getKey())->exists());
$this->assertDatabaseHas('users', ['id' => $dependent->id, 'status' => User::STATUS_ACTIVE]);
$this->assertDatabaseHas('guardian_relationship_verification_audits', [
    'parent_child_account_id' => $guardianARelationship->id,
    'action' => 'revoked',
]);
```

- [ ] **Step 2: Update representative seed relationships**

For intentionally usable seeded relationships, set all of:

```php
[
    'relationship_type' => 'parent',
    'verification_pathway' => 'legacy',
    'relationship_status' => 'active',
    'relationship_verified_status' => 'verified',
    'is_legacy_relationship' => true,
    'verification_status' => 'approved',
    'relationship_verified_at' => now(),
    'can_view_progress' => true,
    'can_view_quiz_answers' => true,
    'can_approve_content' => false,
]
```

Do not modify `ModuleLessonQuizSeeder.php` or unrelated lesson/community seed data.

- [ ] **Step 3: Run targeted Phase 1 tests**

Run:

```powershell
php artisan test tests/Unit/GuardianRelationshipTypesTest.php tests/Feature/GuardianRelationshipSchemaTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/ParentChildMonitoringTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminUserRelationshipMutationTest.php tests/Feature/Admin/AdminUserRelationshipManagementPageTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Moderation/SuspensionAppealSubmissionTest.php
```

Expected: all listed tests PASS.

- [ ] **Step 4: Run formatting, build, and the full suite**

Run:

```powershell
vendor\bin\pint --test
pnpm.cmd build
php artisan test
```

Expected: Pint reports no style errors, Vite exits successfully, and the complete PHPUnit suite passes. If the repository’s pre-existing unrelated generated assets are dirty, do not stage them with this feature.

- [ ] **Step 5: Perform browser end-to-end verification**

Start the Laravel application using the project’s normal local development command and verify these flows in the in-app browser:

1. Biological parent: create dependent, submit core evidence, approve, confirm access activates only after approval.
2. Adoptive parent: add several categorized files, preview images/PDFs, replace/remove before submission, and confirm the admin sees the complete round.
3. Non-parent relative: require a circumstances statement and flexible authority/care evidence; request resubmission once, then approve.
4. Multiple guardians: approve two relationships, revoke one, and confirm the other still works.
5. Evidence privacy: verify unrelated guardian, dependent, and instructor accounts receive 403 responses for evidence URLs.
6. Existing learner invitation: confirm no relationship before acceptance, under-review relationship after acceptance, and no relationship-only privileges before approval.
7. Moderation: suspend a guardian and confirm relationship-derived access and chat eligibility stop without altering another guardian.

- [ ] **Step 6: Write the verification report with observed results**

Create `docs/superpowers/verification/2026-09-07-guardian-dependent-relationship-verification-e2e.md`. Record the exact commit tested, database driver, commands and exit codes, each browser scenario’s observed result, any environment limitations, and confirmation that no Health & Support Information or invitation-profile/messaging enhancements were included.

- [ ] **Step 7: Commit seed and verification updates**

```powershell
git add database/seeders/ParentMonitoringSeeder.php database/seeders/TestUserSeeder.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/ParentChildMonitoringTest.php docs/superpowers/verification/2026-09-07-guardian-dependent-relationship-verification-e2e.md
git commit -m "test: verify independent guardian relationships"
```

---

---

## Final Acceptance Checklist

- [ ] Every new relationship type resolves to an evidence pathway and starts pending.
- [ ] No declaration, selection, invitation acceptance, child-account approval, or admin attachment bypasses centralized review.
- [ ] Multiple guardians coexist as independent rows with independent evidence, history, states, and permissions.
- [ ] Adoptive and other applicants can submit 1–10 categorized files with front/back metadata and previews.
- [ ] Non-parent applicants provide flexible core care/custody evidence plus circumstances.
- [ ] Submitted rounds are immutable and resubmissions preserve prior rounds.
- [ ] Evidence is privately stored and authorization-tested.
- [ ] Approval, rejection, resubmission, revocation, deactivation, and reactivation are transactionally guarded and audited.
- [ ] Permission changes affect one relationship row only and are audited without sensitive personal data.
- [ ] Existing active legacy access is preserved and labeled as legacy.
- [ ] Singular guardian fields and helpers are not authoritative.
- [ ] Pending/rejected/revoked/inactive relationships grant no monitoring, content, chat, or moderation privileges.
- [ ] Existing learner invitation, dashboard, chat, notification, moderation, and child-verification regressions pass.
- [ ] User-facing and admin copy consistently says administrative verification, not legal determination.
- [ ] Health/support data and Phase 2 invitation enhancements remain out of scope.
