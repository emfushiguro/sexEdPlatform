# System-Wide Age Policy and Minor Registration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Teenager (ages 10-17) and Adult (ages 18-100) the only active learner categories, require every Teenager to have an approved and consenting guardian, and let a Teenager activate, sign in, verify identity, and recover a password without owning an email address.

**Architecture:** Put all date-of-birth classification in one `LearnerAgePolicy`, keep the existing category and account-type columns as non-authoritative query caches, and reuse the current Guardian-Dependent and learner-identity review systems. New restricted Teenager accounts use nullable personal email, a proposed-or-null username, versioned guardian consent, and hashed one-time handoffs; existing learners retain their data and credentials while live middleware applies the new policy. Legacy Kids values remain readable only for compatibility and audit, while every active writer, query, report, and ordinary interface exposes Teenager and Adult only.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent/MySQL, Laravel Notifications and Mail, Spatie Permission, Blade, Alpine.js, PHPUnit 11 with the isolated MySQL `cc_db_test` database, and Node's built-in test runner for existing JavaScript tests.

**Approved specification:** `docs/superpowers/specs/2026-11-12-system-wide-age-policy-and-minor-registration-design.md`

## Global Constraints

- Never reset, wipe, truncate, recreate, or destructively reseed the development database. Do not run `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP TABLE`, or an unscoped `DELETE` against development data.
- Confirm `APP_ENV=testing` and `DB_DATABASE=cc_db_test` in `phpunit.xml` before the first PHPUnit command. Do not run `composer setup`; it executes `php artisan migrate --force` against the configured database.
- Use additive, forward-only migrations. Their `down()` methods must not remove learner, guardian, consent, handoff, or audit history.
- Date of birth in `users.birthdate` is authoritative. Never trust submitted age, category, account type, cached classification, or profile DOB for an eligibility decision.
- Classify exactly ages 10-17 as `eligible_teenager` / `teens`, exactly ages 18-100 as `eligible_adult` / `adults`, below 10 as `ineligible_underage`, above 100 as `ineligible_overage`, and missing, future, or invalid DOB as `unclassified`.
- Every 10-17 learner requires the same guardian's approved identity, active verified Guardian-Dependent relationship, current versioned authorization, and completed account activation before protected learner access.
- Keep guardian identity, relationship review, guardian authorization, account activation, and Teenager identity review as separate decisions. No successful upload or provider event may approve another decision.
- A personal email is optional for every 10-17 learner. Never derive a Gmail plus-alias, create an `@child.sexed-platform.local` address, copy the guardian's email into the learner, or mark a non-owned address verified.
- The Teenager chooses the final password. Never send, display, log, or persist a plaintext password. Store only keyed token digests; raw activation or recovery codes exist only long enough to render or notify once.
- Use the neutral consent/privacy copy specified in Tasks 5-6 and keep release behind normal product, privacy, and legal copy review. The platform does not claim to adjudicate legal guardianship.
- Existing accounts, passwords, verified genuine emails, progress, enrollments, attempts, certificates, evidence, relationships, and audit history must survive the transition.
- Existing independently registered minors do not need to repeat password activation. They do need a qualifying guardian relationship and current guardian authorization before protected learner access.
- `kids` and `learner-child` remain readable for legacy data and restricted audit output only. New writes and ordinary UI must accept or expose only Teenager and Adult.
- Never automatically retarget Kids content to Teenager. Kids-only or Kids-worded content is hidden and flagged; mixed content may continue only for its remaining active audiences.
- Live DOB policy controls access. Caches and the scheduled reconciliation are optimizations, and deployment must not automatically run an apply-mode data transition.
- A birthday or authorized DOB correction must re-evaluate category, guardian, identity pathway, contact, and recovery gates without deleting history.
- Do not add SMS, phone authentication, age-estimation AI, automatic document approval, new identity providers, new packages, or unrelated role, permission, navigation, or visual redesigns.
- Preserve unrelated worktree changes, including the currently untracked `public/assets/` directory. Stage only task-owned paths; never use `git add .` or `git add -A`.
- Separate source inspection, focused tests, broader regression, browser evidence, provider evidence, database rehearsal, deployment, and production evidence. An unrun gate is not passed.

---

## Planned File Map

### Authoritative policy and classification

- Create `app/Support/LearnerAgePolicy.php` - the only active learner age and category classifier.
- Create `config/learner_age_policy.php` - consent version, invitation/handoff lifetimes, and disabled-by-default reconciliation schedule.
- Modify `app/Models/User.php` - delegate age/category decisions, store explicit eligibility cache, expose qualifying guardian and activation helpers, and stop defaulting unknown learners to Adult.
- Modify `app/Models/LearnerProfile.php` - read age/category from the owning user's authoritative DOB.
- Create `database/migrations/2026_11_12_000001_add_learner_age_policy_fields.php` - add explicit eligibility and minor activation/privacy state.
- Create `app/Console/Commands/ReclassifyLearnersForAgePolicy.php` - idempotent dry-run/apply cache reconciliation.
- Modify `routes/console.php` - register an apply schedule only when explicitly enabled.

### Minor registration, consent, activation, and recovery

- Create `database/migrations/2026_11_12_000002_create_minor_registration_workflow.php` - make minor contact/username nullable, add consent fields, and create request/handoff tables.
- Create `app/Models/MinorRegistrationRequest.php` - encrypted pre-account handoff data and hashed-token state.
- Create `app/Models/MinorAccountHandoff.php` - activation and password-recovery lifecycle state.
- Modify `app/Models/ParentChildAccount.php` - versioned authorization fields and `minorAccessEligible()` scope.
- Create `app/Services/Auth/MinorAccountHandoffService.php` - issue, approve, validate, consume, expire, and invalidate one-time handoffs.
- Modify `app/Http/Controllers/Auth/ParentRegistrationController.php` - create a restricted Teenager without fabricated email or guardian-chosen password, and link existing Teenagers without duplicating them.
- Modify `app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php` - require current guardian authorization.
- Modify `resources/views/auth/create-child-account.blade.php`, `resources/views/auth/child/step2-location.blade.php`, `resources/views/auth/child/step3-credentials.blade.php`, and `resources/views/auth/child/step5-relationship-verification.blade.php` - Teenager terminology, optional genuine contact, and consent.
- Delete `resources/views/auth/child/step4-validation.blade.php` after its route becomes a compatibility redirect - the later Teenager ID/selfie case replaces the pre-account PSA step for new registrations.
- Modify `app/Services/GuardianRelationshipVerificationService.php` - issue activation only after relationship approval and after commit.
- Create `app/Http/Controllers/Auth/MinorAccountActivationController.php` - one-time activation and guardian reissue.
- Create `app/Http/Requests/Auth/ActivateMinorAccountRequest.php` - username, strong password, and Teenager privacy acceptance.
- Create `app/Notifications/TeenagerActivationReadyNotification.php` and `resources/views/emails/teenager-activation-ready.blade.php` - guardian-only activation delivery.
- Create `resources/views/auth/teenager-activation.blade.php` and `resources/views/guardian/teenager-activation-code.blade.php` - activation entry and one-time reissue display.
- Create `app/Http/Controllers/Auth/MinorRegistrationRequestController.php`, `app/Http/Requests/Auth/StoreMinorRegistrationRequest.php`, and `app/Notifications/GuardianMinorRegistrationInvitation.php` - Teenager-started guardian handoff.
- Create `resources/views/auth/minor-registration-request.blade.php` and `resources/views/emails/guardian-minor-registration-invitation.blade.php` - basic request and mail copy.
- Create `app/Http/Controllers/Auth/MinorPasswordRecoveryController.php` and `app/Http/Requests/Auth/CompleteMinorPasswordResetRequest.php` - guardian-approved username recovery.
- Create `app/Notifications/TeenagerPasswordRecoveryRequestedNotification.php` - guardian recovery approval notice.
- Create `resources/views/auth/minor-password-reset.blade.php` and `resources/views/guardian/minor-password-reset-code.blade.php` - code completion and one-time guardian handoff.
- Modify `app/Http/Controllers/Auth/PasswordResetLinkController.php`, `resources/views/auth/forgot-password.blade.php`, `routes/auth.php`, and the guardian dashboard/routes - accept email or learner username while keeping generic public responses.
- Modify `app/Http/Controllers/GuardianRelationshipLifecycleController.php` and `resources/views/parent/children/show.blade.php` - let the owning approved guardian record current consent for a preserved legacy relationship.

### Access gates and identity transitions

- Create `app/Http/Middleware/EnsureLearnerContactReady.php` - verified own email for Adults/non-learners, or qualifying activated guardian-backed state for Teenagers.
- Modify `bootstrap/app.php` - register `learner.contact-ready`.
- Modify `app/Http/Middleware/EnsureLearnerIdentityVerified.php` - live eligibility, guardian, activation, and current-pathway identity gates.
- Modify `app/Http/Middleware/EnsureProfileCompleted.php` - remove legacy under-13/document assumptions and defer to current policy gates.
- Modify `app/Http/Controllers/Auth/AuthenticatedSessionController.php` and `app/Http/Requests/Auth/LoginRequest.php` - preserve username login while removing fabricated-email auto-verification and blocking unactivated restricted accounts.
- Modify `app/Http/Controllers/Auth/EmailVerificationPromptController.php` and `resources/views/auth/verify-email.blade.php` - let a newly Adult no-email user add and verify their own address.
- Modify `app/Http/Requests/ProfileUpdateRequest.php`, `app/Http/Controllers/ProfileController.php`, and `resources/views/profile/partials/update-profile-information-form.blade.php` - keep personal email optional for a Teenager and required for an Adult.
- Modify `app/Services/Identity/LearnerIdentityRequirement.php`, `app/Services/Identity/LearnerIdentitySubmission.php`, and `app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php` - derive Teenager/Adult pathway from live policy and require approval for the current pathway.
- Create `database/migrations/2026_11_12_000003_preserve_learner_identity_pathway_history.php` - replace the one-row-per-pathway unique index with a history-safe lookup index so repeated DOB pathway changes never delete old evidence.
- Modify learner route groups in `routes/auth.php` and `routes/web.php` - replace inappropriate `verified` checks with `learner.contact-ready` without weakening non-learner email verification.

### Registration entry points and administration

- Modify `app/Http/Requests/RegisterRequest.php`, `app/Http/Requests/AccountInfoRequest.php`, and `app/Http/Controllers/Auth/RegisteredUserController.php` - route every 10-17 learner to guardian assistance and admit only 18-100 to independent account creation.
- Modify `app/Services/Admin/UserManagementService.php`, `app/Http/Requests/Admin/StoreUserRequest.php`, `app/Http/Requests/Admin/UpdateUserRequest.php`, `app/Http/Requests/Admin/IndexUserRequest.php`, `app/Http/Controllers/Admin/UserAdminController.php`, and `resources/views/admin/users/partials/user-wizard-steps.blade.php` - enforce the same DOB policy, keep user/profile DOB synchronized, and reject direct active Teenager creation without guardian workflow.

### Content, reports, interfaces, fixtures, and audit

- Modify `app/Models/Module.php` and the module/learning-path request and service callers - active categories only, with legacy read compatibility.
- Modify seminar, connector, chat, dashboard, export, and report services/configuration listed in Tasks 11-12 - use the central policy and hide legacy Kids targeting.
- Create `app/Console/Commands/AuditLegacyKidsContent.php` - read-only audit of legacy targeting and Kids wording; never retarget content.
- Modify affected Blade views, JavaScript tests, factories, and local/test seeders - expose Teenager and Adult only while keeping explicit legacy fixtures in transition tests.

### Focused verification

- Create `tests/Unit/Support/LearnerAgePolicyTest.php`.
- Create `tests/Feature/AgePolicy/AgePolicySchemaTest.php` and `tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php`.
- Create `tests/Feature/Auth/MinorRegistrationSchemaTest.php`, `GuardianTeenagerRegistrationTest.php`, `TeenagerStartedRegistrationTest.php`, `TeenagerAccountActivationTest.php`, `TeenagerAccessGateTest.php`, and `TeenagerPasswordRecoveryTest.php`.
- Create `tests/Feature/Console/AuditLegacyKidsContentTest.php`.
- Modify existing registration, guardian, identity, profile, admin, module, learning-path, seminar, connector, chat, dashboard, export, seeder, and JavaScript tests named in the relevant tasks.

---

### Task 1: Establish the one authoritative learner age policy

**Files:**
- Create: `app/Support/LearnerAgePolicy.php`
- Create: `config/learner_age_policy.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/LearnerProfile.php`
- Create: `tests/Unit/Support/LearnerAgePolicyTest.php`

**Interfaces:**
- Produces: `LearnerAgePolicy::classify()`, `age()`, `category()`, `requiresGuardian()`, `activeCategories()`, and `normalizeActiveCategory()`.
- Produces: policy results `eligible_teenager`, `eligible_adult`, `ineligible_underage`, `ineligible_overage`, and `unclassified`.
- Preserves: `User::calculateAge()`, `requiresParentalConsent()`, `deriveAgeBracketCache()`, and `LearnerProfile::getAgeBracket()` as compatibility methods that delegate to the policy.
- Consumes: `users.birthdate` as the sole authoritative DOB.

- [ ] **Step 1: Confirm the isolated PHPUnit database before any test command**

Run:

~~~powershell
Select-String -Path phpunit.xml -Pattern 'APP_ENV.*testing','DB_DATABASE.*cc_db_test'
~~~

Expected: one match for `APP_ENV=testing` and one for `DB_DATABASE=cc_db_test`. Stop if either differs.

- [ ] **Step 2: Write the failing boundary, timezone, leap-day, invalid-DOB, and normalization tests**

Create `tests/Unit/Support/LearnerAgePolicyTest.php` with a data provider that pins the as-of date rather than using the wall clock:

~~~php
<?php

namespace Tests\Unit\Support;

use App\Support\LearnerAgePolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LearnerAgePolicyTest extends TestCase
{
    #[DataProvider('classifications')]
    public function test_it_classifies_exact_policy_boundaries(
        ?string $birthdate,
        string $asOf,
        string $expected,
        ?string $category,
    ): void {
        config(['app.timezone' => 'Asia/Manila']);

        $this->assertSame($expected, LearnerAgePolicy::classify($birthdate, $asOf));
        $this->assertSame($category, LearnerAgePolicy::category($birthdate, $asOf));
    }

    public static function classifications(): array
    {
        return [
            'age 9' => ['2017-11-13', '2026-11-12', LearnerAgePolicy::INELIGIBLE_UNDERAGE, null],
            'age 10' => ['2016-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
            'age 17' => ['2009-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
            'age 18' => ['2008-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_ADULT, LearnerAgePolicy::CATEGORY_ADULTS],
            'age 100' => ['1926-11-12', '2026-11-12', LearnerAgePolicy::ELIGIBLE_ADULT, LearnerAgePolicy::CATEGORY_ADULTS],
            'age 101' => ['1925-11-12', '2026-11-12', LearnerAgePolicy::INELIGIBLE_OVERAGE, null],
            'future DOB' => ['2026-11-13', '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'missing DOB' => [null, '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'invalid DOB' => ['not-a-date', '2026-11-12', LearnerAgePolicy::UNCLASSIFIED, null],
            'leap birthday before anniversary' => ['2016-02-29', '2026-02-28', LearnerAgePolicy::INELIGIBLE_UNDERAGE, null],
            'leap birthday after anniversary' => ['2016-02-29', '2026-03-01', LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::CATEGORY_TEENAGERS],
        ];
    }

    public function test_it_exposes_only_active_categories_and_read_aliases(): void
    {
        $this->assertSame(['teens', 'adults'], LearnerAgePolicy::activeCategories());
        $this->assertSame('teens', LearnerAgePolicy::normalizeActiveCategory('teen'));
        $this->assertSame('adults', LearnerAgePolicy::normalizeActiveCategory('adult'));
        $this->assertNull(LearnerAgePolicy::normalizeActiveCategory('kids'));
    }
}
~~~

Run:

~~~powershell
php artisan test tests/Unit/Support/LearnerAgePolicyTest.php
~~~

Expected: FAIL because `LearnerAgePolicy` does not exist.

- [ ] **Step 3: Add fixed policy configuration**

Create `config/learner_age_policy.php`:

~~~php
<?php

return [
    'guardian_consent_version' => '2026-11-12',
    'minor_registration_request_days' => 14,
    'handoff_minutes' => 30,
    'schedule_reclassification' => false,
];
~~~

Do not add an environment variable or silently enable apply-mode reconciliation.

- [ ] **Step 4: Implement the stateless policy**

Create `app/Support/LearnerAgePolicy.php`:

~~~php
<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

final class LearnerAgePolicy
{
    public const ELIGIBLE_TEENAGER = 'eligible_teenager';
    public const ELIGIBLE_ADULT = 'eligible_adult';
    public const INELIGIBLE_UNDERAGE = 'ineligible_underage';
    public const INELIGIBLE_OVERAGE = 'ineligible_overage';
    public const UNCLASSIFIED = 'unclassified';
    public const CATEGORY_TEENAGERS = 'teens';
    public const CATEGORY_ADULTS = 'adults';

    public static function classify(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): string
    {
        $age = self::age($birthdate, $asOf);

        return match (true) {
            $age === null => self::UNCLASSIFIED,
            $age < 10 => self::INELIGIBLE_UNDERAGE,
            $age <= 17 => self::ELIGIBLE_TEENAGER,
            $age <= 100 => self::ELIGIBLE_ADULT,
            default => self::INELIGIBLE_OVERAGE,
        };
    }

    public static function age(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): ?int
    {
        $timezone = config('app.timezone');

        try {
            $dob = $birthdate instanceof DateTimeInterface
                ? CarbonImmutable::instance($birthdate)->setTimezone($timezone)->startOfDay()
                : CarbonImmutable::parse($birthdate, $timezone)->startOfDay();
            $today = $asOf instanceof DateTimeInterface
                ? CarbonImmutable::instance($asOf)->setTimezone($timezone)->startOfDay()
                : ($asOf === null
                    ? CarbonImmutable::now($timezone)->startOfDay()
                    : CarbonImmutable::parse($asOf, $timezone)->startOfDay());
        } catch (Throwable) {
            return null;
        }

        if ($birthdate === null || $dob->isAfter($today)) {
            return null;
        }

        return (int) $dob->diffInYears($today);
    }

    public static function category(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): ?string
    {
        return match (self::classify($birthdate, $asOf)) {
            self::ELIGIBLE_TEENAGER => self::CATEGORY_TEENAGERS,
            self::ELIGIBLE_ADULT => self::CATEGORY_ADULTS,
            default => null,
        };
    }

    public static function requiresGuardian(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): bool
    {
        return self::classify($birthdate, $asOf) === self::ELIGIBLE_TEENAGER;
    }

    public static function activeCategories(): array
    {
        return [self::CATEGORY_TEENAGERS, self::CATEGORY_ADULTS];
    }

    public static function normalizeActiveCategory(string $value): ?string
    {
        return match (strtolower(trim($value))) {
            'teen', 'teens' => self::CATEGORY_TEENAGERS,
            'adult', 'adults' => self::CATEGORY_ADULTS,
            default => null,
        };
    }
}
~~~

- [ ] **Step 5: Delegate existing model helpers without changing unrelated roles**

In `User`, make learner-facing compatibility methods call the policy:

~~~php
public function calculateAge(): ?int
{
    return LearnerAgePolicy::age($this->birthdate);
}

public function requiresGuardian(): bool
{
    return LearnerAgePolicy::requiresGuardian($this->birthdate);
}

public function requiresParentalConsent(): bool
{
    return $this->requiresGuardian();
}

public function deriveAgeBracketCache(): ?string
{
    return $this->hasRole('learner')
        ? LearnerAgePolicy::category($this->birthdate)
        : null;
}
~~~

In `LearnerProfile`, read the owning user's DOB first and retain profile DOB only as legacy display fallback:

~~~php
public function getAge(): ?int
{
    return LearnerAgePolicy::age($this->user?->birthdate ?? $this->birthdate);
}

public function getAgeBracket(): ?string
{
    return LearnerAgePolicy::category($this->user?->birthdate ?? $this->birthdate);
}

public function isKid(): bool
{
    return false;
}

public function isTeen(): bool
{
    return $this->getAgeBracket() === LearnerAgePolicy::CATEGORY_TEENAGERS;
}

public function isAdult(): bool
{
    return $this->getAgeBracket() === LearnerAgePolicy::CATEGORY_ADULTS;
}
~~~

Keep `isKid()` only for source compatibility during this plan; no active caller may use it after Task 12.

- [ ] **Step 6: Run focused tests and formatting**

Run:

~~~powershell
php artisan test tests/Unit/Support/LearnerAgePolicyTest.php
vendor/bin/pint --test app/Support/LearnerAgePolicy.php app/Models/User.php app/Models/LearnerProfile.php config/learner_age_policy.php tests/Unit/Support/LearnerAgePolicyTest.php
~~~

Expected: all policy tests PASS and Pint reports no formatting changes required.

- [ ] **Step 7: Commit the policy boundary**

~~~powershell
git add app/Support/LearnerAgePolicy.php app/Models/User.php app/Models/LearnerProfile.php config/learner_age_policy.php tests/Unit/Support/LearnerAgePolicyTest.php
git commit -m "feat: centralize learner age policy"
~~~

---

### Task 2: Persist explicit eligibility without making caches authoritative

**Files:**
- Create: `database/migrations/2026_11_12_000001_add_learner_age_policy_fields.php`
- Modify: `app/Models/User.php`
- Modify: `app/Services/Admin/UserManagementService.php`
- Create: `tests/Feature/AgePolicy/AgePolicySchemaTest.php`

**Interfaces:**
- Produces: nullable indexed `users.age_eligibility_cached`.
- Produces: `users.minor_account_activation_required`, `minor_account_activated_at`, `minor_privacy_notice_version`, and `minor_privacy_accepted_at`.
- Changes: `User::refreshClassificationCache()` writes explicit eligibility and never converts an ineligible/unclassified learner to Adult.
- Preserves: existing `account_type` for ineligible/unclassified legacy rows.

- [ ] **Step 1: Write the failing schema and cache-behavior tests**

Create `tests/Feature/AgePolicy/AgePolicySchemaTest.php` covering:

~~~php
public function test_age_policy_columns_exist(): void
{
    $this->assertTrue(Schema::hasColumns('users', [
        'age_eligibility_cached',
        'minor_account_activation_required',
        'minor_account_activated_at',
        'minor_privacy_notice_version',
        'minor_privacy_accepted_at',
    ]));
}

public function test_refresh_writes_eligible_teenager_without_defaulting_invalid_dob_to_adult(): void
{
    $teenager = User::factory()->create(['birthdate' => now()->subYears(10)->toDateString()]);
    $teenager->assignRole('learner');
    $teenager->refreshClassificationCache();

    $this->assertSame('eligible_teenager', $teenager->fresh()->age_eligibility_cached);
    $this->assertSame('teens', $teenager->fresh()->age_bracket_cached);
    $this->assertSame(User::ACCOUNT_TYPE_LEARNER_TEEN, $teenager->fresh()->account_type);

    $unknown = User::factory()->create([
        'birthdate' => null,
        'account_type' => User::ACCOUNT_TYPE_LEARNER_CHILD,
    ]);
    $unknown->assignRole('learner');
    $unknown->refreshClassificationCache();

    $this->assertSame('unclassified', $unknown->fresh()->age_eligibility_cached);
    $this->assertNull($unknown->fresh()->age_bracket_cached);
    $this->assertSame(User::ACCOUNT_TYPE_LEARNER_CHILD, $unknown->fresh()->account_type);
}
~~~

Run:

~~~powershell
php artisan test tests/Feature/AgePolicy/AgePolicySchemaTest.php
~~~

Expected: FAIL because the columns do not exist.

- [ ] **Step 2: Add the forward-only migration**

The migration's `up()` must add:

~~~php
Schema::table('users', function (Blueprint $table): void {
    $table->string('age_eligibility_cached', 32)->nullable()->index()->after('age_bracket_cached');
    $table->boolean('minor_account_activation_required')->default(false)->after('age_eligibility_cached');
    $table->timestamp('minor_account_activated_at')->nullable()->after('minor_account_activation_required');
    $table->string('minor_privacy_notice_version', 32)->nullable()->after('minor_account_activated_at');
    $table->timestamp('minor_privacy_accepted_at')->nullable()->after('minor_privacy_notice_version');
});
~~~

Use an empty `down(): void` so rollback cannot erase policy or privacy state. Do not backfill any row in the migration.

- [ ] **Step 3: Cast and refresh the fields safely**

Add the new fillable/cast fields, then replace the current unconditional account-type derivation with:

~~~php
public function refreshClassificationCache(): void
{
    if (! $this->hasRole('learner')) {
        $this->forceFill([
            'age_eligibility_cached' => null,
            'age_bracket_cached' => null,
        ])->saveQuietly();

        return;
    }

    $eligibility = LearnerAgePolicy::classify($this->birthdate);
    $accountType = match ($eligibility) {
        LearnerAgePolicy::ELIGIBLE_TEENAGER => self::ACCOUNT_TYPE_LEARNER_TEEN,
        LearnerAgePolicy::ELIGIBLE_ADULT => self::ACCOUNT_TYPE_LEARNER_ADULT,
        default => $this->account_type,
    };

    $this->forceFill([
        'age_eligibility_cached' => $eligibility,
        'age_bracket_cached' => LearnerAgePolicy::category($this->birthdate),
        'account_type' => $accountType,
    ])->saveQuietly();
}
~~~

Ensure `UserManagementService::createUser()` and `updateUser()` still call `refreshClassificationCache()` after role and DOB persistence. Do not add model-save recursion.

- [ ] **Step 4: Run migration tests, focused Admin tests, and formatting**

Run only against `cc_db_test`:

~~~powershell
php artisan test tests/Feature/AgePolicy/AgePolicySchemaTest.php tests/Feature/Admin/AdminUserManagementFeatureTest.php
vendor/bin/pint --test database/migrations/2026_11_12_000001_add_learner_age_policy_fields.php app/Models/User.php app/Services/Admin/UserManagementService.php tests/Feature/AgePolicy/AgePolicySchemaTest.php
~~~

Expected: schema/cache tests PASS; existing Admin management tests remain PASS.

- [ ] **Step 5: Commit explicit eligibility storage**

~~~powershell
git add database/migrations/2026_11_12_000001_add_learner_age_policy_fields.php app/Models/User.php app/Services/Admin/UserManagementService.php tests/Feature/AgePolicy/AgePolicySchemaTest.php
git commit -m "feat: persist learner age eligibility"
~~~

---

### Task 3: Add dry-run-first learner reclassification

**Files:**
- Create: `app/Console/Commands/ReclassifyLearnersForAgePolicy.php`
- Modify: `routes/console.php`
- Create: `tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php`

**Interfaces:**
- Produces: `learners:reclassify-age-policy {--apply} {--chunk=100}`.
- Default behavior: read-only report with five eligibility counts, guardian gaps, and user/profile DOB mismatches.
- Apply behavior: bounded, transactional, row-locked cache refresh only; no deletion, suspension, password change, relationship mutation, or content rewrite.
- Produces: disabled-by-default daily schedule controlled by `learner_age_policy.schedule_reclassification`.

- [ ] **Step 1: Write failing dry-run, apply, idempotence, and schedule tests**

Create tests that snapshot the relevant columns before dry-run and assert byte-for-byte equality afterward:

~~~php
public function test_dry_run_reports_without_writing(): void
{
    $learner = $this->createLearner(['birthdate' => '2014-01-01', 'age_bracket_cached' => 'kids']);
    $before = $learner->only(['account_type', 'age_bracket_cached', 'age_eligibility_cached', 'updated_at']);

    $this->artisan('learners:reclassify-age-policy', ['--chunk' => 1])
        ->expectsOutputToContain('DRY RUN')
        ->expectsOutputToContain('eligible_teenager')
        ->assertSuccessful();

    $this->assertSame($before, $learner->fresh()->only(array_keys($before)));
}

public function test_apply_is_batched_and_idempotent(): void
{
    $learner = $this->createLearner(['birthdate' => '2014-01-01', 'age_bracket_cached' => 'kids']);

    $this->artisan('learners:reclassify-age-policy', ['--apply' => true, '--chunk' => 1])->assertSuccessful();
    $first = $learner->fresh()->only(['account_type', 'age_bracket_cached', 'age_eligibility_cached']);
    $this->artisan('learners:reclassify-age-policy', ['--apply' => true, '--chunk' => 1])->assertSuccessful();

    $this->assertSame($first, $learner->fresh()->only(array_keys($first)));
}
~~~

Also cover ages 9, 10, 17, 18, 100, 101, missing DOB, soft-deleted learner rows, independently registered minors, and a differing `learner_profiles.birthdate`. Assert no related progress or relationship count changes.

Run:

~~~powershell
php artisan test tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php
~~~

Expected: FAIL because the command is absent.

- [ ] **Step 2: Implement an explicit dry-run/apply command**

Use the existing `MoveLegacyIdentityDocumentsToPrivateStorage` command style. The command must:

~~~php
protected $signature = 'learners:reclassify-age-policy
    {--apply : Persist classification caches}
    {--chunk=100 : Number of learner rows per batch}';
~~~

Its `handle()` must validate `--chunk` as an integer from 1 through 1000, query learner-role users with soft-deleted rows included, and process stable ID-ordered chunks. For every row:

1. classify `users.birthdate` with `LearnerAgePolicy`;
2. count the result, current Kids/cache mismatch, missing qualifying guardian for Teenagers, and user/profile DOB mismatch;
3. in dry-run mode, make no write;
4. in apply mode, run `DB::transaction()`, reload that exact user with `lockForUpdate()`, and call `refreshClassificationCache()`;
5. catch each row failure, report its user ID and exception class without exposing personal data, and return non-zero if any row fails.

The summary table must reconcile `total = teenager + adult + underage + overage + unclassified`. Do not set account status, activation, consent, identity, email, or relationship fields.

- [ ] **Step 3: Add an opt-in schedule, not an automatic rollout**

In `routes/console.php`:

~~~php
if (config('learner_age_policy.schedule_reclassification')) {
    Schedule::command('learners:reclassify-age-policy --apply')
        ->dailyAt('00:15')
        ->withoutOverlapping();
}
~~~

The test must set the config to `true` before resolving the schedule and assert the command appears once; with the default `false`, it must not appear.

- [ ] **Step 4: Run focused tests and one read-only local dry run**

Run:

~~~powershell
php artisan test tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php
php artisan learners:reclassify-age-policy --chunk=100
vendor/bin/pint --test app/Console/Commands/ReclassifyLearnersForAgePolicy.php routes/console.php tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php
~~~

Expected: tests PASS; the command labels itself `DRY RUN`, reports reconciled counts, and does not change `git status` or database timestamps. Do not run `--apply` against the development database.

- [ ] **Step 5: Commit the safe transition command**

~~~powershell
git add app/Console/Commands/ReclassifyLearnersForAgePolicy.php routes/console.php tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php
git commit -m "feat: add safe learner reclassification"
~~~

---

### Task 4: Add the minimal minor-workflow persistence model

**Files:**
- Create: `database/migrations/2026_11_12_000002_create_minor_registration_workflow.php`
- Create: `app/Models/MinorRegistrationRequest.php`
- Create: `app/Models/MinorAccountHandoff.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/ParentChildAccount.php`
- Create: `tests/Feature/Auth/MinorRegistrationSchemaTest.php`

**Interfaces:**
- Changes: `users.email` and `learner_profiles.username` become nullable; unique non-null values remain enforced by their existing indexes.
- Produces: versioned guardian authorization on `parent_child_accounts`.
- Produces: encrypted `minor_registration_requests` with searchable guardian-email fingerprint and keyed token digest.
- Produces: `minor_account_handoffs` for `activation` and `password_reset` only.
- Produces: `ParentChildAccount::minorAccessEligible()` as the shared qualifying-guardian query.

- [ ] **Step 1: Write failing schema, encryption, relationship-scope, and model tests**

Create `tests/Feature/Auth/MinorRegistrationSchemaTest.php` and assert:

- all new columns and tables exist;
- two users may have null email and two learner profiles may have null username;
- duplicate non-null email and username still fail their unique constraints;
- `guardian_email` and `minor_payload` plaintext do not appear in raw database values;
- a current-consent relationship passes `minorAccessEligible()` only when the relationship is active/verified, the guardian is active and identity-approved, the child is active, the actor equals `parent_user_id`, and the consent version equals config;
- a revoked, inactive, unverified, wrong-actor, stale-consent, or soft-deleted relationship fails;
- handoff purpose rejects values other than `activation` and `password_reset` at the model/service boundary.

Run:

~~~powershell
php artisan test tests/Feature/Auth/MinorRegistrationSchemaTest.php
~~~

Expected: FAIL because the migration and models do not exist.

- [ ] **Step 2: Add one forward-only compatibility migration**

The migration must use `change()` only for nullability and must not rewrite existing values:

~~~php
Schema::table('users', function (Blueprint $table): void {
    $table->string('email')->nullable()->change();
});

Schema::table('learner_profiles', function (Blueprint $table): void {
    $table->string('username')->nullable()->change();
});

Schema::table('parent_child_accounts', function (Blueprint $table): void {
    $table->timestamp('minor_registration_authorized_at')->nullable();
    $table->string('minor_registration_consent_version', 32)->nullable();
    $table->foreignId('minor_registration_authorized_by_user_id')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();
});
~~~

Create the request table:

~~~php
Schema::create('minor_registration_requests', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('existing_child_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->text('guardian_email');
    $table->char('guardian_email_fingerprint', 64)->index();
    $table->text('minor_payload')->nullable();
    $table->char('token_hash', 64)->unique();
    $table->string('status', 24)->default('pending')->index();
    $table->timestamp('expires_at')->index();
    $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('accepted_at')->nullable();
    $table->timestamps();
});
~~~

Create the handoff table:

~~~php
Schema::create('minor_account_handoffs', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('child_user_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('guardian_user_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('parent_child_account_id')->constrained('parent_child_accounts')->restrictOnDelete();
    $table->string('purpose', 24)->index();
    $table->char('token_hash', 64)->nullable()->unique();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('expires_at')->index();
    $table->timestamp('used_at')->nullable();
    $table->timestamps();
});
~~~

Use an empty `down(): void`. Do not add plaintext tokens, passwords, guardian emails, or identity evidence to either table.

- [ ] **Step 3: Implement the two small lifecycle models**

`MinorRegistrationRequest` must define `STATUS_PENDING`, `STATUS_COMPLETED`, `STATUS_EXPIRED`, encrypted casts for `guardian_email` and `minor_payload`, datetime casts, and relations to `existingChild` and `acceptedBy`. Its fillable list must contain only migration columns.

`MinorAccountHandoff` must define `PURPOSE_ACTIVATION` and `PURPOSE_PASSWORD_RESET`, datetime casts, and `child`, `guardian`, and `relationship` relations. Add these user relations:

~~~php
public function minorRegistrationRequests(): HasMany
{
    return $this->hasMany(MinorRegistrationRequest::class, 'existing_child_user_id');
}

public function minorAccountHandoffs(): HasMany
{
    return $this->hasMany(MinorAccountHandoff::class, 'child_user_id');
}
~~~

- [ ] **Step 4: Add versioned consent to the existing relationship model**

Add the three fields to `$fillable`, cast the timestamp, and implement:

~~~php
public function scopeMinorAccessEligible(Builder $query): Builder
{
    return $query
        ->accessEligible()
        ->whereNotNull('minor_registration_authorized_at')
        ->where('minor_registration_consent_version', config('learner_age_policy.guardian_consent_version'))
        ->whereColumn('minor_registration_authorized_by_user_id', 'parent_user_id');
}
~~~

Add a direct relation on `User` rather than reusing the current `parent()` method, which terminates with `first()`:

~~~php
public function guardianRelationships(): HasMany
{
    return $this->hasMany(ParentChildAccount::class, 'child_user_id');
}

public function qualifyingMinorGuardianRelationship(): ?ParentChildAccount
{
    if (! LearnerAgePolicy::requiresGuardian($this->birthdate)) {
        return null;
    }

    return $this->guardianRelationships()
        ->minorAccessEligible()
        ->orderByDesc('minor_registration_authorized_at')
        ->orderByDesc('id')
        ->first();
}

public function hasCompletedMinorActivation(): bool
{
    return ! $this->minor_account_activation_required
        || $this->minor_account_activated_at !== null;
}
~~~

The default `minor_account_activation_required = false` is the explicit compatibility rule for existing accounts that already own credentials. Only newly restricted accounts set it to `true`.

Add a multiple-guardian test proving the most recently authorized qualifying relationship is selected deterministically for recovery; a revoked or stale newer relationship falls back only to another fully qualifying relationship.

- [ ] **Step 5: Run focused tests and formatting**

Run:

~~~powershell
php artisan test tests/Feature/Auth/MinorRegistrationSchemaTest.php tests/Feature/GuardianRelationshipSchemaTest.php
vendor/bin/pint --test database/migrations/2026_11_12_000002_create_minor_registration_workflow.php app/Models/MinorRegistrationRequest.php app/Models/MinorAccountHandoff.php app/Models/User.php app/Models/ParentChildAccount.php tests/Feature/Auth/MinorRegistrationSchemaTest.php
~~~

Expected: all schema, encryption, and relationship-scope assertions PASS.

- [ ] **Step 6: Commit persistence separately from behavior**

~~~powershell
git add database/migrations/2026_11_12_000002_create_minor_registration_workflow.php app/Models/MinorRegistrationRequest.php app/Models/MinorAccountHandoff.php app/Models/User.php app/Models/ParentChildAccount.php tests/Feature/Auth/MinorRegistrationSchemaTest.php
git commit -m "feat: add minor registration persistence"
~~~

---

### Task 5: Convert guardian-first creation into a restricted Teenager account

**Files:**
- Modify: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Modify: `app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php`
- Modify: `routes/auth.php`
- Modify: `resources/views/auth/create-child-account.blade.php`
- Modify: `resources/views/auth/child/step2-location.blade.php`
- Modify: `resources/views/auth/child/step3-credentials.blade.php`
- Delete: `resources/views/auth/child/step4-validation.blade.php`
- Modify: `resources/views/auth/child/step5-relationship-verification.blade.php`
- Modify: `resources/views/auth/child/done.blade.php`
- Create: `tests/Feature/Auth/GuardianTeenagerRegistrationTest.php`
- Modify: `tests/Feature/Auth/ParentChildVerificationResubmissionTest.php`
- Modify: `tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php`

**Interfaces:**
- Consumes: an authenticated, verified, fully approved guardian.
- Produces: an active-status but login-restricted learner user, nullable genuine personal email, nullable proposed username, learner role/profile, pending Guardian-Dependent relationship, current guardian consent, and relationship evidence in one transaction.
- Removes from new flow: guardian-chosen password, Gmail plus-alias, internal placeholder email, `markEmailAsVerified()`, and pre-account PSA certificate approval.
- Preserves: legacy rows that already have `verification_document_path`; their existing moderation path remains readable.

- [ ] **Step 1: Write the failing guardian-first feature tests**

Cover these cases in `GuardianTeenagerRegistrationTest`:

1. exact ages 10 and 17 are accepted; 9, 18, 100, future, and missing DOB are rejected from this Teenager flow;
2. an optional unique username and genuine email may be proposed, and both may be omitted;
3. the guardian never submits the learner's password;
4. a no-email account stores `users.email = null` and `email_verified_at = null`;
5. a supplied personal email remains unverified;
6. no stored email contains `+` derived from the guardian or `@child.sexed-platform.local`;
7. the restricted account has `minor_account_activation_required = true`, no activation timestamp, and an unusable random password;
8. the relationship is pending, has current consent version/actor/time, and its evidence remains private;
9. no Teenager identity case is approved or created by relationship submission;
10. a failure while finalizing relationship evidence removes only newly written files and creates no partial user/relationship.

Run:

~~~powershell
php artisan test tests/Feature/Auth/GuardianTeenagerRegistrationTest.php
~~~

Expected: FAIL against the current under-13/password/placeholder-email flow.

- [ ] **Step 2: Enforce live Teenager classification at the first step**

Replace the locally calculated/stored age with the policy result. Keep DOB in the session but never trust a session age:

~~~php
$classification = LearnerAgePolicy::classify($validated['birthdate']);

if ($classification !== LearnerAgePolicy::ELIGIBLE_TEENAGER) {
    return back()
        ->withErrors(['birthdate' => 'Guardian-assisted learner registration is for Teenagers ages 10 through 17.'])
        ->withInput();
}

session(['child_step1' => $validated]);
~~~

Change user-facing copy from Child/Kids to Teenager while retaining existing route names for compatibility.

- [ ] **Step 3: Make credentials a proposal, not a password handoff**

Remove the Gmail suggestion and password fields. Validate only:

~~~php
$validated = $request->validate([
    'username' => ['nullable', 'string', 'min:3', 'max:30', 'unique:learner_profiles,username', 'regex:/^[a-z0-9_-]+$/'],
    'email' => ['nullable', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
]);

session(['child_step3' => [
    'username' => $validated['username'] ?? null,
    'email' => $validated['email'] ?? null,
]]);
~~~

Post this step directly to the relationship-verification page. Keep the old `/validation` GET as a session-aware redirect to the relationship step and reject its POST with HTTP 410 plus a safe restart message. Then delete the now-unused validation view.

- [ ] **Step 4: Capture guardian authorization on the relationship form**

Add an unchecked, required checkbox with this exact neutral product copy: `I confirm that I am authorized to act as this Teenager's guardian, authorize creation of this account and the required identity review, and have read the Privacy Notice.` Add this request rule:

~~~php
'guardian_authorization' => ['accepted'],
~~~

The server must write `config('learner_age_policy.guardian_consent_version')`, `now()`, and the authenticated guardian's ID. Do not trust a hidden consent-version field. Product/privacy/legal copy review remains a release gate; a wording change increments the configured version.

- [ ] **Step 5: Create the restricted account and relationship atomically**

Inside the existing evidence-compensating transaction, reclassify the DOB, lock any consumed `MinorRegistrationRequest`, and fail if the result is no longer Teenager. For a new account, use:

~~~php
$child = User::query()->create([
    'name' => trim($step1['first_name'].' '.$step1['last_name']),
    'first_name' => $step1['first_name'],
    'middle_initial' => $step1['middle_initial'] ?? null,
    'last_name' => $step1['last_name'],
    'suffix' => $step1['suffix'] ?? null,
    'email' => $step3['email'] ?? null,
    'birthdate' => $step1['birthdate'],
    'password' => Hash::make(Str::random(64)),
    'minor_account_activation_required' => true,
]);
~~~

Assign learner role, create the existing learner profile with nullable proposed username and the current location/gender data, call `refreshClassificationCache()`, and create the relationship with:

~~~php
'verification_document_path' => null,
'minor_registration_authorized_at' => now(),
'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
'minor_registration_authorized_by_user_id' => $parent->id,
~~~

Do not call `markEmailAsVerified()`. Do not create a learner identity case yet. Submit only the relationship evidence through `GuardianRelationshipVerificationService::submit()`.

For an existing independently registered Teenager selected by an accepted handoff, lock and reuse that user/profile, preserve email/password/username and all related data, set no activation requirement, and create only the relationship plus consent/evidence. Reject a duplicate active or pending relationship rather than creating another.

- [ ] **Step 6: Update completion copy and compatibility tests**

The completion page must say:

- the account cannot be used until the relationship is approved;
- the guardian will receive an activation handoff only for a newly restricted account;
- the Teenager will then choose the password and submit their own ID plus selfie;
- relationship approval is not Teenager identity approval.

Retain legacy `approveChild` tests for pre-existing rows with a PSA document, and add an assertion that new rows with null `verification_document_path` do not appear in that legacy queue.

- [ ] **Step 7: Run focused guardian and evidence tests**

Run:

~~~powershell
php artisan test tests/Feature/Auth/GuardianTeenagerRegistrationTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
vendor/bin/pint --test app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php tests/Feature/Auth/GuardianTeenagerRegistrationTest.php
~~~

Expected: guardian-first tests PASS; legacy evidence review remains PASS; no generated or marked-verified learner email appears.

- [ ] **Step 8: Commit the restricted-account flow**

~~~powershell
git add app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Requests/Auth/StoreChildRelationshipVerificationRequest.php routes/auth.php resources/views/auth/create-child-account.blade.php resources/views/auth/child/step2-location.blade.php resources/views/auth/child/step3-credentials.blade.php resources/views/auth/child/step4-validation.blade.php resources/views/auth/child/step5-relationship-verification.blade.php resources/views/auth/child/done.blade.php tests/Feature/Auth/GuardianTeenagerRegistrationTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
git commit -m "feat: create guardian-assisted teen accounts"
~~~

---

### Task 6: Activate the account only after relationship approval

**Files:**
- Create: `app/Services/Auth/MinorAccountHandoffService.php`
- Modify: `app/Services/GuardianRelationshipVerificationService.php`
- Create: `app/Http/Controllers/Auth/MinorAccountActivationController.php`
- Create: `app/Http/Requests/Auth/ActivateMinorAccountRequest.php`
- Create: `app/Notifications/TeenagerActivationReadyNotification.php`
- Create: `resources/views/emails/teenager-activation-ready.blade.php`
- Create: `resources/views/auth/teenager-activation.blade.php`
- Create: `resources/views/guardian/teenager-activation-code.blade.php`
- Modify: `routes/auth.php`
- Modify: `resources/views/parent/children/show.blade.php`
- Create: `tests/Feature/Auth/TeenagerAccountActivationTest.php`

**Interfaces:**
- Produces: a 16-character, case-insensitive, single-use activation code with only a keyed SHA-256 digest persisted.
- Produces: `MinorAccountHandoffService::issueActivation()`, `findUsable()`, and `consumeActivation()`.
- Consumes: one current-consent, active verified relationship whose guardian is identity-approved.
- Changes: activation sets final username/password, privacy acceptance, and activation timestamp; it does not approve identity or mark email verified.

- [ ] **Step 1: Write failing issuance and activation tests**

Cover:

- pending/rejected/revoked relationships issue nothing;
- approval issues one activation only for a new restricted Teenager and not for an existing credentialed Teenager;
- raw code is absent from the database, logs, audit records, queued payloads, and redirect URLs other than the intended activation URL;
- code expiry, reuse, wrong purpose, changed DOB, stale consent, revoked relationship, and wrong child all fail generically;
- reissue expires older unused activation rows;
- the Teenager can retain or change the proposed username, must use a strong confirmed password, and must accept the current privacy notice;
- successful activation stores a hashed password, current username, privacy version/time, `minor_account_activated_at`, and `minor_account_activation_required = false` in one transaction;
- activation does not set `email_verified_at` and does not approve the learner identity case;
- after activation, the browser is authenticated as the Teenager and redirected to the existing Teenager identity submission route.

Run:

~~~powershell
php artisan test tests/Feature/Auth/TeenagerAccountActivationTest.php
~~~

Expected: FAIL because activation lifecycle is absent.

- [ ] **Step 2: Implement one keyed token lifecycle service**

The service must generate and normalize codes in memory:

~~~php
private function generateCode(): string
{
    return strtoupper(Str::random(16));
}

private function digest(string $code): string
{
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code));

    return hash_hmac('sha256', $normalized, (string) config('app.key'));
}
~~~

`issueActivation(ParentChildAccount $relationship): array` must transactionally:

1. lock the relationship and child;
2. re-check live Teenager policy, `minorAccessEligible()`, and activation-required state;
3. expire older unused activation handoffs for that child;
4. create one row with the digest, current relationship/guardian, approval time, and `now()->addMinutes(config('learner_age_policy.handoff_minutes'))`;
5. return `[$handoff, $rawCode]` without storing the raw value anywhere else.

`findUsable($code, $purpose)` must query by digest and require approved, unused, unexpired state. `consumeActivation()` must repeat all relationship and age checks under row locks before changing credentials and marking the handoff used.

- [ ] **Step 3: Issue only after the approval transaction commits**

Inject `MinorAccountHandoffService` into `GuardianRelationshipVerificationService`. After `approve()` returns the updated relationship, register an `afterCommit` callback only when the child is a live Teenager and `minor_account_activation_required` is true:

~~~php
DB::afterCommit(function () use ($approved): void {
    try {
        [$handoff, $code] = $this->minorHandoffs->issueActivation($approved);
        $approved->parent->notify(new TeenagerActivationReadyNotification($approved->child, $handoff, $code));
    } catch (Throwable $exception) {
        report($exception);
    }
});
~~~

Notification failure must not roll back relationship approval. It must not log the code. The guardian dashboard reissue action provides the retry path and rechecks ownership plus `minorAccessEligible()`.

- [ ] **Step 4: Implement activation request and controller**

`ActivateMinorAccountRequest` validates:

~~~php
return [
    'code' => ['required', 'string', 'max:32'],
    'username' => [
        'required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9_-]+$/',
        Rule::unique('learner_profiles', 'username')->ignore($profileId),
    ],
    'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
    'privacy_notice' => ['accepted'],
];
~~~

The controller's `store()` delegates the locked mutation to the service, then regenerates the session, logs in the Teenager, sends normal own-email verification only when a genuine email exists, and redirects to the existing learner identity route. Add `throttle:5,1` to show/store/reissue routes.

- [ ] **Step 5: Build accessible activation and guardian handoff views**

Use the existing auth layout and form components. The activation form must have visible labels, error summaries, autocomplete values (`username`, `new-password`), keyboard focus, and no guardian password field. Its unchecked privacy checkbox copy is: `I have read the Teenager Privacy Notice and understand how my information is used.` The guardian screen/email may show the one-time link and selectable code once, but never the learner password or evidence.

- [ ] **Step 6: Run focused relationship, notification, and activation tests**

~~~powershell
php artisan test tests/Feature/Auth/TeenagerAccountActivationTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Notifications
vendor/bin/pint --test app/Services/Auth/MinorAccountHandoffService.php app/Services/GuardianRelationshipVerificationService.php app/Http/Controllers/Auth/MinorAccountActivationController.php app/Http/Requests/Auth/ActivateMinorAccountRequest.php app/Notifications/TeenagerActivationReadyNotification.php tests/Feature/Auth/TeenagerAccountActivationTest.php
~~~

Expected: relationship approval remains independently auditable; activation tests PASS; notification failures leave a reissuable approved relationship.

- [ ] **Step 7: Commit activation as its own security boundary**

~~~powershell
git add app/Services/Auth/MinorAccountHandoffService.php app/Services/GuardianRelationshipVerificationService.php app/Http/Controllers/Auth/MinorAccountActivationController.php app/Http/Requests/Auth/ActivateMinorAccountRequest.php app/Notifications/TeenagerActivationReadyNotification.php resources/views/emails/teenager-activation-ready.blade.php resources/views/auth/teenager-activation.blade.php resources/views/guardian/teenager-activation-code.blade.php resources/views/parent/children/show.blade.php routes/auth.php tests/Feature/Auth/TeenagerAccountActivationTest.php
git commit -m "feat: add minor account activation"
~~~

---

### Task 7: Add the Teenager-started guardian handoff

**Files:**
- Create: `app/Http/Controllers/Auth/MinorRegistrationRequestController.php`
- Create: `app/Http/Requests/Auth/StoreMinorRegistrationRequest.php`
- Create: `app/Notifications/GuardianMinorRegistrationInvitation.php`
- Create: `resources/views/auth/minor-registration-request.blade.php`
- Create: `resources/views/emails/guardian-minor-registration-invitation.blade.php`
- Modify: `app/Http/Controllers/Auth/ParentRegistrationController.php`
- Modify: `app/Http/Controllers/GuardianRelationshipLifecycleController.php`
- Modify: `resources/views/parent/children/show.blade.php`
- Modify: `routes/auth.php`
- Create: `tests/Feature/Auth/TeenagerStartedRegistrationTest.php`

**Interfaces:**
- Guest input: Teenager name, DOB, and guardian email; no learner account, evidence, username, or password is created at request time.
- Authenticated existing-Teenager input: guardian email only, linked by `existing_child_user_id`.
- Produces: one encrypted, expiring request and a guardian-email notification with a single-use token.
- Consumes: a signed-in guardian whose verified email fingerprint matches the invitation; final completion reuses Task 5.

- [ ] **Step 1: Write failing request, privacy, expiry, and acceptance tests**

Cover:

- ages 10-17 may request; other/missing/future DOB values fail;
- public response is identical whether or not the guardian email belongs to an account;
- rate limiting applies by normalized guardian fingerprint and IP;
- only encrypted name/DOB payload and encrypted guardian email are stored; no identity or relationship evidence is collected;
- raw token is absent from storage/logs and expires after 14 days;
- a guardian must authenticate, verify the same genuine email, finish guardian onboarding, and have approved guardian identity before continuing;
- a forwarded token used by a different email fails generically;
- a guest request pre-fills Task 5 without creating a user until final guardian submission;
- an authenticated existing Teenager links the existing user and preserves password/email/username/progress;
- a completed, expired, or reused request cannot create a second user or relationship.

Run:

~~~powershell
php artisan test tests/Feature/Auth/TeenagerStartedRegistrationTest.php
~~~

Expected: FAIL because the request flow does not exist.

- [ ] **Step 2: Validate minimum request data at the trust boundary**

`StoreMinorRegistrationRequest` must normalize guardian email and validate the guest payload:

~~~php
return [
    'first_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:255'],
    'last_name' => [$this->user() ? 'nullable' : 'required', 'string', 'max:255'],
    'birthdate' => [$this->user() ? 'nullable' : 'required', 'date', 'before_or_equal:today'],
    'guardian_email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255'],
];
~~~

After validation, use the authenticated learner's authoritative DOB when present; otherwise require `LearnerAgePolicy::ELIGIBLE_TEENAGER`. Never accept submitted child ID, category, age, account type, consent version, or guardian user ID.

- [ ] **Step 3: Store encrypted data and send a generic invitation**

Create a 16-character raw token using the handoff service's shared generate/digest methods, calculate the normalized-email fingerprint with a keyed SHA-256 digest for equality only, and persist:

~~~php
MinorRegistrationRequest::query()->create([
    'existing_child_user_id' => $existingTeenager?->id,
    'guardian_email' => $validated['guardian_email'],
    'guardian_email_fingerprint' => hash_hmac('sha256', strtolower(trim($validated['guardian_email'])), (string) config('app.key')),
    'minor_payload' => $existingTeenager ? null : Arr::only($validated, ['first_name', 'last_name', 'birthdate']),
    'token_hash' => $this->minorHandoffs->digestForStorage($rawToken),
    'status' => MinorRegistrationRequest::STATUS_PENDING,
    'expires_at' => now()->addDays(config('learner_age_policy.minor_registration_request_days')),
]);
~~~

Send through `Notification::route('mail', $guardianEmail)` after commit. Always redirect the requester with the same generic message. If delivery fails, report without personal data and let the requester safely submit a new request.

- [ ] **Step 4: Authenticate and bind the accepting guardian**

The invitation URL may carry the raw token but no PII. On open:

1. validate token digest, pending status, and expiry;
2. store only the request ID and raw token in the encrypted session;
3. send unauthenticated visitors through existing guardian login/registration using the intended URL;
4. require verified email, completed guardian onboarding, and approved guardian identity;
5. compare the authenticated guardian's normalized email fingerprint in constant time;
6. prefill `pending_child_registration` and enter Task 5.

Do not mark the request completed on link open. In Task 5's transaction, lock it, repeat every check, create or link the Teenager, then set `status = completed`, `accepted_by_user_id`, and `accepted_at` atomically.

- [ ] **Step 5: Give an existing Teenager a restricted guardian-setup route**

Add an authenticated version of the request form to the global learner-gate allowlist. It must not expose learning, community, chat, seminars, subscriptions, or marketplace content. It accepts guardian email only and always uses the signed-in user's authoritative DOB and user ID.

- [ ] **Step 6: Let an existing relationship record current guardian authorization**

Add one owner-authorized POST action to `GuardianRelationshipLifecycleController`. It must lock the relationship, require the signed-in user to be `parent_user_id`, require an active approved guardian account, require the child to classify as a current Teenager, reject rejected/revoked/deleted relationships, and write the server-side consent version, actor, and timestamp. It must not approve the relationship or learner identity. A verified active relationship becomes access-eligible immediately; a pending relationship still waits for Admin approval.

- [ ] **Step 7: Run focused tests and formatting**

~~~powershell
php artisan test tests/Feature/Auth/TeenagerStartedRegistrationTest.php tests/Feature/Auth/GuardianTeenagerRegistrationTest.php
vendor/bin/pint --test app/Http/Controllers/Auth/MinorRegistrationRequestController.php app/Http/Requests/Auth/StoreMinorRegistrationRequest.php app/Notifications/GuardianMinorRegistrationInvitation.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Controllers/GuardianRelationshipLifecycleController.php tests/Feature/Auth/TeenagerStartedRegistrationTest.php
~~~

Expected: both guardian-first and Teenager-started paths converge on one relationship/account creation transaction and all tests PASS.

- [ ] **Step 8: Commit the handoff flow**

~~~powershell
git add app/Http/Controllers/Auth/MinorRegistrationRequestController.php app/Http/Requests/Auth/StoreMinorRegistrationRequest.php app/Notifications/GuardianMinorRegistrationInvitation.php resources/views/auth/minor-registration-request.blade.php resources/views/emails/guardian-minor-registration-invitation.blade.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Controllers/GuardianRelationshipLifecycleController.php resources/views/parent/children/show.blade.php routes/auth.php tests/Feature/Auth/TeenagerStartedRegistrationTest.php
git commit -m "feat: add teen guardian handoff"
~~~

---

### Task 8: Enforce live Teenager readiness without pretending guardian email is learner email

**Files:**
- Create: `app/Http/Middleware/EnsureLearnerContactReady.php`
- Create: `app/Http/Controllers/Auth/LearnerEligibilityStatusController.php`
- Create: `resources/views/auth/learner-eligibility-status.blade.php`
- Modify: `bootstrap/app.php`
- Modify: `app/Http/Middleware/EnsureLearnerIdentityVerified.php`
- Modify: `app/Http/Middleware/EnsureProfileCompleted.php`
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Modify: `app/Http/Requests/Auth/LoginRequest.php`
- Modify: `routes/auth.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Auth/TeenagerAccessGateTest.php`
- Modify: `tests/Feature/Identity/LearnerIdentityAccessTest.php`
- Create: `tests/Feature/Learner/ProfileCompletionMiddlewareTest.php`

**Interfaces:**
- Produces: middleware alias `learner.contact-ready`.
- Teenager contact-ready means live age 10-17, completed activation, and one current qualifying relationship; it never changes `email_verified_at`.
- Adult/non-learner contact-ready means the user's own genuine email is verified, preserving existing behavior.
- Global learner gate fails closed for ineligible/unclassified learners and exposes only completion, privacy/data-rights, password/recovery, support, and logout routes.

- [ ] **Step 1: Write the failing middleware and direct-route tests**

Create a matrix covering:

| Learner state | Identity route | Protected learning route | Completion/support/logout |
|---|---|---|---|
| Teenager, no email, qualifying guardian, activated | allowed | identity-dependent | allowed |
| Teenager, unactivated restricted account | denied | denied | activation/status allowed |
| Teenager, missing/stale/revoked guardian consent | denied | denied | guardian setup/status allowed |
| Teenager, own unverified optional email, otherwise ready | allowed | identity-dependent | allowed |
| Adult, no/unverified email | denied | denied | add/verify email allowed |
| Adult, verified own email | allowed | identity-dependent | allowed |
| age 9, age 101, missing/future DOB | denied | denied | status/support/privacy/logout allowed |

Also assert HTML redirects, JSON 403 responses, and direct URL behavior. Assert no request path changes `email_verified_at`.

Run:

~~~powershell
php artisan test tests/Feature/Auth/TeenagerAccessGateTest.php
~~~

Expected: FAIL because current middleware requires verified email for every learner.

- [ ] **Step 2: Add the narrow contact-readiness middleware**

Implement:

~~~php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (! $user) {
        return redirect()->route('login');
    }

    if (! $user->hasRole('learner')) {
        return $user->hasVerifiedEmail()
            ? $next($request)
            : redirect()->route('verification.notice');
    }

    return match (LearnerAgePolicy::classify($user->birthdate)) {
        LearnerAgePolicy::ELIGIBLE_TEENAGER => $user->hasCompletedMinorActivation()
            && $user->qualifyingMinorGuardianRelationship() !== null
                ? $next($request)
                : redirect()->route('learner.eligibility.status'),
        LearnerAgePolicy::ELIGIBLE_ADULT => $user->hasVerifiedEmail()
            ? $next($request)
            : redirect()->route('verification.notice'),
        default => redirect()->route('learner.eligibility.status'),
    };
}
~~~

Return JSON 403 with a stable `completion_required` code when `expectsJson()` is true. Register only `learner.contact-ready` in `bootstrap/app.php`; do not replace Laravel's general `verified` middleware.

- [ ] **Step 3: Put live age/guardian/activation checks before identity/profile checks**

In `EnsureLearnerIdentityVerified`, after its explicit safe-route allowlist and before email/identity logic:

1. classify live DOB;
2. redirect ineligible/unclassified learners to `learner.eligibility.status`;
3. for Teenagers, require completed activation and a fresh `minorAccessEligible()` relationship;
4. for Adults, require own verified email;
5. require approval for the current live identity pathway, not any historical case.

The allowlist must name only existing/new routes for guardian setup, activation, learner identity submission/status/evidence, add/verify own email, privacy/data-rights, password and recovery, support, eligibility status, and logout. Do not allow dashboard, learning, community, chat, seminars, subscriptions, connector marketplace, or generic wildcard prefixes.

In `EnsureProfileCompleted`, remove under-13 and legacy PSA-document branches. It should assume the global gate already established eligibility/guardian/activation/current identity, then check profile completion only.

- [ ] **Step 4: Remove the fabricated-email login side effect**

Delete the block in `AuthenticatedSessionController` that calls `markEmailAsVerified()` for a dependent based on relationship evidence. In `LoginRequest`, keep existing email-or-username lookup and add an explicit credential rejection when:

~~~php
$user->hasRole('learner')
    && $user->minor_account_activation_required
    && $user->minor_account_activated_at === null
~~~

Use the same generic authentication failure as an incorrect password; do not reveal restricted account existence.

- [ ] **Step 5: Apply the custom middleware only where Laravel `verified` blocks no-email Teenagers**

In `routes/auth.php`, change the learner identity group from `verified` to `learner.contact-ready`. In `routes/web.php`, replace `verified` only on learner-facing groups that a fully ready no-email Teenager must use. The custom middleware preserves verified-email behavior for Adults and non-learners, so mixed-role groups do not become weaker.

Do not remove `verified` from guardian onboarding, Admin, Instructor, connector, or unrelated routes.

- [ ] **Step 6: Build one plain status page**

`LearnerEligibilityStatusController` derives the state server-side and passes one of these codes to a single Blade view:

- `guardian_required` - start/resume guardian handoff;
- `relationship_pending` - wait for review;
- `activation_required` - guardian must reissue/open activation;
- `underage`, `overage`, or `unclassified` - protected participation unavailable, with support/privacy actions;
- `adult_email_required` - add and verify the user's own email.

Do not show raw DOB, guardian email, evidence, internal status values, or admin-only review details.

- [ ] **Step 7: Run focused authentication and middleware tests**

~~~powershell
php artisan test tests/Feature/Auth/TeenagerAccessGateTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/Learner/ProfileCompletionMiddlewareTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/EmailVerificationTest.php
vendor/bin/pint --test app/Http/Middleware/EnsureLearnerContactReady.php app/Http/Controllers/Auth/LearnerEligibilityStatusController.php app/Http/Middleware/EnsureLearnerIdentityVerified.php app/Http/Middleware/EnsureProfileCompleted.php app/Http/Controllers/Auth/AuthenticatedSessionController.php app/Http/Requests/Auth/LoginRequest.php tests/Feature/Auth/TeenagerAccessGateTest.php
~~~

Expected: no-email ready Teenagers reach identity/protected routes as appropriate; Adults still require their own verified email; restricted states fail closed.

- [ ] **Step 8: Commit the access boundary**

~~~powershell
git add app/Http/Middleware/EnsureLearnerContactReady.php app/Http/Controllers/Auth/LearnerEligibilityStatusController.php resources/views/auth/learner-eligibility-status.blade.php bootstrap/app.php app/Http/Middleware/EnsureLearnerIdentityVerified.php app/Http/Middleware/EnsureProfileCompleted.php app/Http/Controllers/Auth/AuthenticatedSessionController.php app/Http/Requests/Auth/LoginRequest.php routes/auth.php routes/web.php tests/Feature/Auth/TeenagerAccessGateTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/Learner/ProfileCompletionMiddlewareTest.php
git commit -m "feat: gate guardian-backed teen access"
~~~

---

### Task 9: Add guardian-approved password recovery for a no-email Teenager

**Files:**
- Create: `app/Http/Controllers/Auth/MinorPasswordRecoveryController.php`
- Create: `app/Http/Requests/Auth/CompleteMinorPasswordResetRequest.php`
- Create: `app/Notifications/TeenagerPasswordRecoveryRequestedNotification.php`
- Create: `resources/views/auth/minor-password-reset.blade.php`
- Create: `resources/views/guardian/minor-password-reset-code.blade.php`
- Modify: `app/Services/Auth/MinorAccountHandoffService.php`
- Modify: `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- Modify: `resources/views/auth/forgot-password.blade.php`
- Modify: `routes/auth.php`
- Modify: `resources/views/parent/children/show.blade.php`
- Create: `tests/Feature/Auth/TeenagerPasswordRecoveryTest.php`
- Modify: `tests/Feature/Auth/PasswordResetTest.php`

**Interfaces:**
- Public input becomes `login`, accepting genuine email or learner username.
- Existing verified-email users continue through Laravel's password broker unchanged.
- A no-email Teenager creates a pending recovery row and notifies the qualifying guardian; public output remains generic.
- Guardian approval reveals one raw code once; the guardian never sees or sets the new password.

- [ ] **Step 1: Write failing recovery, enumeration, and revocation tests**

Cover:

- the same public response/status appears for unknown email, unknown username, Adult-without-email, Teenager-without-guardian, and eligible Teenager;
- email input still uses Laravel `Password::sendResetLink()` and existing throttling;
- username lookup is case-normalized through `learner_profiles.username`;
- only a live 10-17 learner with completed activation and qualifying current relationship creates one pending recovery request;
- the guardian notification contains no password, identity evidence, private messages, or raw documents;
- a different guardian cannot approve;
- approval fails after relationship revocation, consent staleness, DOB becoming Adult/ineligible, account suspension, or request expiry;
- approval produces a raw code once while the database stores only its digest;
- code reuse, wrong username, wrong purpose, expiry, and concurrent double-submit fail;
- successful reset changes only the Teenager's password, marks the row used, and invalidates existing sessions through the project's normal password-reset behavior;
- an Adult who turned 18 never falls back to the former guardian and receives support-assisted recovery guidance instead.

Run:

~~~powershell
php artisan test tests/Feature/Auth/TeenagerPasswordRecoveryTest.php
~~~

Expected: FAIL because forgot-password accepts email only.

- [ ] **Step 2: Preserve the broker path and add a generic username branch**

Change the form field to `login`. In `PasswordResetLinkController::store()`:

~~~php
$login = strtolower(trim((string) $request->string('login')));

if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    Password::sendResetLink(['email' => $login]);
} else {
    $teenager = User::query()
        ->whereHas('learnerProfile', fn (Builder $query) => $query->whereRaw('LOWER(username) = ?', [$login]))
        ->first();

    $this->minorHandoffs->requestPasswordReset($teenager);
}

return back()->with('status', trans(Password::RESET_LINK_SENT));
~~~

`requestPasswordReset()` must silently return when any requirement fails. It must rate-limit by IP and normalized login, expire older pending recovery rows, create a token-null pending row, and notify only the qualifying relationship's verified guardian after commit.

- [ ] **Step 3: Add authenticated guardian approval**

The guardian dashboard lists only that guardian's pending, unexpired recovery requests. POST approval must lock the handoff, relationship, guardian, and child; re-check live Teenager policy plus `minorAccessEligible()`; generate a new 16-character code; store only its digest and approval time; and render `guardian/minor-password-reset-code.blade.php` once.

If the page is refreshed, do not reveal the raw code again. The guardian may ask the Teenager to start a new recovery request.

- [ ] **Step 4: Complete reset without authenticating the guardian as the Teenager**

The public completion form accepts code, learner username, strong confirmed password, and no email. `CompleteMinorPasswordResetRequest` uses the same password rule as activation. The service must transactionally:

1. find the approved, unused, unexpired password-reset digest;
2. lock handoff, child, and relationship;
3. compare the submitted username to that child in constant time after normalization;
4. repeat live policy, activation, guardian, consent, and account-status checks;
5. update the hashed password and remember-token/session invalidation fields used by the existing reset flow;
6. mark `used_at` and return a generic success redirect to login.

- [ ] **Step 5: Run recovery and existing broker tests**

~~~powershell
php artisan test tests/Feature/Auth/TeenagerPasswordRecoveryTest.php tests/Feature/Auth/PasswordResetTest.php
vendor/bin/pint --test app/Http/Controllers/Auth/MinorPasswordRecoveryController.php app/Http/Requests/Auth/CompleteMinorPasswordResetRequest.php app/Notifications/TeenagerPasswordRecoveryRequestedNotification.php app/Services/Auth/MinorAccountHandoffService.php app/Http/Controllers/Auth/PasswordResetLinkController.php tests/Feature/Auth/TeenagerPasswordRecoveryTest.php
~~~

Expected: new username flow PASS; all standard email reset tests remain PASS; public responses do not enumerate accounts.

- [ ] **Step 6: Commit recovery separately**

~~~powershell
git add app/Http/Controllers/Auth/MinorPasswordRecoveryController.php app/Http/Requests/Auth/CompleteMinorPasswordResetRequest.php app/Notifications/TeenagerPasswordRecoveryRequestedNotification.php resources/views/auth/minor-password-reset.blade.php resources/views/guardian/minor-password-reset-code.blade.php resources/views/parent/children/show.blade.php app/Services/Auth/MinorAccountHandoffService.php app/Http/Controllers/Auth/PasswordResetLinkController.php resources/views/auth/forgot-password.blade.php routes/auth.php tests/Feature/Auth/TeenagerPasswordRecoveryTest.php tests/Feature/Auth/PasswordResetTest.php
git commit -m "feat: add guardian-approved teen recovery"
~~~

---

### Task 10: Enforce the policy at every learner entry point and identity transition

**Files:**
- Create: `database/migrations/2026_11_12_000003_preserve_learner_identity_pathway_history.php`
- Modify: `app/Http/Requests/RegisterRequest.php`
- Modify: `app/Http/Requests/AccountInfoRequest.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Modify: `resources/views/auth/register.blade.php`
- Modify: `app/Services/Admin/UserManagementService.php`
- Modify: `app/Services/Identity/LearnerIdentityRequirement.php`
- Modify: `app/Services/Identity/LearnerIdentitySubmission.php`
- Modify: `app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php`
- Modify: `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- Modify: `resources/views/auth/verify-email.blade.php`
- Modify: `app/Http/Requests/ProfileUpdateRequest.php`
- Modify: `app/Http/Controllers/ProfileController.php`
- Modify: `resources/views/profile/partials/update-profile-information-form.blade.php`
- Modify: `app/Http/Requests/Admin/StoreUserRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateUserRequest.php`
- Modify: `app/Http/Controllers/Admin/UserAdminController.php`
- Modify: `resources/views/admin/users/partials/user-wizard-steps.blade.php`
- Create: `tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php`
- Create: `tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php`
- Modify: `tests/Feature/Auth/RegistrationTest.php`
- Modify: `tests/Feature/Admin/AdminUserManagementFeatureTest.php`
- Modify: `tests/Feature/Identity/LearnerIdentityRequirementTest.php`
- Modify: `tests/Feature/Identity/LearnerIdentitySubmissionTest.php`
- Modify: `tests/Feature/Identity/LearnerIdentityAccessTest.php`
- Modify: `tests/Feature/Identity/LearnerIdentityReviewTest.php`
- Modify: `tests/Feature/ProfileTest.php`

**Interfaces:**
- Public registration: 10-17 enters guardian handoff; 18-100 enters independent email/password registration; all other classifications fail.
- Admin creation: direct active Teenager creation is rejected with a link/instruction to use guardian assistance; Admins cannot impersonate guardian consent.
- DOB mutation: authorized updates synchronize user/profile DOB, refresh caches, and let live gates require the new guardian/identity/contact state immediately.
- Identity: only approval for the current live Teenager or Adult pathway satisfies access.

- [ ] **Step 1: Write failing trust-boundary and transition tests**

Cover all public, guardian, Admin, and current integration entry points:

- exact age boundaries 9/10/17/18/100/101;
- crafted `age`, `kids`, `category`, `age_bracket_cached`, and `account_type` values cannot override DOB;
- direct POST to `/register/account` with Teenager DOB cannot create an independent account;
- Adult registration still requires and verifies a genuine unique email;
- Admin cannot create an active Teenager without guardian workflow, and non-learner roles are not assigned learner categories;
- authorized DOB correction writes the same canonical DOB to user/profile in one transaction and immediately changes gates;
- age 17 Teenager approval does not satisfy the Adult pathway after the eighteenth birthday;
- an Adult approval does not satisfy a corrected 10-17 DOB;
- historical identity cases/evidence remain present and private;
- a newly Adult no-email user may add and verify their own email, but the former guardian cannot recover the Adult account.

Run:

~~~powershell
php artisan test tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php
~~~

Expected: FAIL at current under-13 and cached-pathway assumptions.

- [ ] **Step 2: Reclassify on both pages of public registration**

`RegisterRequest` validates syntax/date, then adds a post-validation error for underage, overage, or unclassified DOB. `RegisteredUserController::store()` reclassifies and:

~~~php
return match (LearnerAgePolicy::classify($validated['birthdate'])) {
    LearnerAgePolicy::ELIGIBLE_TEENAGER => redirect()->route('minor-registration.create')
        ->with('pending_minor_registration', Arr::only($validated, ['first_name', 'last_name', 'birthdate'])),
    LearnerAgePolicy::ELIGIBLE_ADULT => redirect()->route('register.account'),
    default => back()->withErrors(['birthdate' => 'Learner registration is available for ages 10 through 100.']),
};
~~~

`storeAccount()` must re-read the session DOB and require `ELIGIBLE_ADULT` before accepting required email/password. This closes direct crafted POSTs. Update copy to say Teenager 10-17 needs a guardian and Adult 18-100 registers independently.

- [ ] **Step 3: Apply the same policy to Admin creation and updates**

In `UserManagementService`, ignore submitted learner category/account type and derive them only after role/DOB persistence. For a newly created learner whose live result is Teenager, throw a validation error directing the Admin to the guardian-assisted registration flow; do not let the Admin record guardian consent.

For an authorized DOB update on an existing learner:

1. validate the new DOB with `LearnerAgePolicy`;
2. update `users.birthdate` and the legacy profile display copy in the same transaction;
3. refresh caches;
4. leave identity and relationship history intact;
5. rely on live middleware to restrict changed requirements immediately.

Do not categorize Parent, Admin, Instructor, Connector, or applicant-only accounts merely because they have a DOB.

- [ ] **Step 4: Require the identity case for the current live pathway**

First add a forward-only migration that drops `learner_idv_user_pathway_uq` and adds a non-unique index named `learner_idv_user_pathway_history_idx` over `user_id`, `pathway`, and `superseded_at`. Its `down()` is empty; it must never delete duplicate historical cases to recreate the old constraint.

Then change `LearnerIdentityRequirement::pathwayFor()` to:

~~~php
return match (LearnerAgePolicy::classify($user->birthdate)) {
    LearnerAgePolicy::ELIGIBLE_TEENAGER => 'teen',
    LearnerAgePolicy::ELIGIBLE_ADULT => 'adult',
    default => throw new DomainException('Learner is not currently eligible for identity verification.'),
};
~~~

Update `current()` so an eligible learner with no case receives a fresh pending case instead of the current legacy exemption. On a pathway change, lock the user and current case, mark only the current row `superseded_at`, preserve its evidence/audits unchanged, and create a new pending row for the target pathway. Remove the current evidence-deletion and old-case-reactivation branches. Repeated Teenager/Adult transitions create additional historical rows safely under the user lock. Update submission request/service and middleware queries so an approved case counts only when `superseded_at` is null and its pathway equals `pathwayFor($user)`. Teenager continues to require accepted school, institution, or government ID plus selfie; Adult continues to require government ID plus selfie. Provider completion remains pending until Admin approval.

- [ ] **Step 5: Let a no-email learner who turns 18 establish an own mailbox**

When `EmailVerificationPromptController` sees an eligible Adult with null email, render an email field instead of attempting to send to null. Add a rate-limited authenticated POST that validates a genuine unique email, updates `email`, clears `email_verified_at`, and calls the existing verification notification. Require current password confirmation if the project already uses it for email changes; do not invent a guardian fallback.

Update `ProfileUpdateRequest` so email is nullable only while live policy classifies the learner as Teenager and remains required for Adults and non-learners. Clearing or changing a Teenager email requires the existing current-password confirmation, clears `email_verified_at`, and never substitutes the guardian address. Keep `ProfileController` notification behavior conditional on a non-null changed email.

- [ ] **Step 6: Run entry-point, Admin, identity, profile, and email regression tests**

~~~powershell
php artisan test tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Admin/AdminUserManagementFeatureTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/ProfileTest.php tests/Feature/Identity
vendor/bin/pint --test database/migrations/2026_11_12_000003_preserve_learner_identity_pathway_history.php app/Http/Requests/RegisterRequest.php app/Http/Requests/AccountInfoRequest.php app/Http/Controllers/Auth/RegisteredUserController.php app/Services/Admin/UserManagementService.php app/Http/Requests/Admin/StoreUserRequest.php app/Http/Requests/Admin/UpdateUserRequest.php app/Http/Controllers/Admin/UserAdminController.php app/Services/Identity/LearnerIdentityRequirement.php app/Services/Identity/LearnerIdentitySubmission.php app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php app/Http/Controllers/Auth/EmailVerificationPromptController.php app/Http/Requests/ProfileUpdateRequest.php app/Http/Controllers/ProfileController.php tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php
~~~

Expected: every learner entry point agrees on boundaries; each age transition opens the correct new requirements without deleting historical state.

- [ ] **Step 7: Commit policy enforcement and transitions**

~~~powershell
git add database/migrations/2026_11_12_000003_preserve_learner_identity_pathway_history.php app/Http/Requests/RegisterRequest.php app/Http/Requests/AccountInfoRequest.php app/Http/Controllers/Auth/RegisteredUserController.php resources/views/auth/register.blade.php app/Services/Admin/UserManagementService.php app/Http/Requests/Admin/StoreUserRequest.php app/Http/Requests/Admin/UpdateUserRequest.php app/Http/Controllers/Admin/UserAdminController.php resources/views/admin/users/partials/user-wizard-steps.blade.php app/Services/Identity/LearnerIdentityRequirement.php app/Services/Identity/LearnerIdentitySubmission.php app/Http/Requests/Auth/SubmitLearnerIdentityRequest.php app/Http/Controllers/Auth/EmailVerificationPromptController.php resources/views/auth/verify-email.blade.php app/Http/Requests/ProfileUpdateRequest.php app/Http/Controllers/ProfileController.php resources/views/profile/partials/update-profile-information-form.blade.php tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Admin/AdminUserManagementFeatureTest.php tests/Feature/Identity/LearnerIdentityRequirementTest.php tests/Feature/Identity/LearnerIdentitySubmissionTest.php tests/Feature/Identity/LearnerIdentityAccessTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php tests/Feature/ProfileTest.php
git commit -m "feat: enforce learner age transitions"
~~~

---

### Task 11: Retire Kids from active content targeting without retargeting legacy content

**Files:**
- Modify: `app/Models/Module.php`
- Modify: `app/Http/Controllers/Admin/AdminModuleController.php`
- Modify: `app/Http/Requests/Admin/SaveLearningPathRequest.php`
- Modify: `app/Http/Requests/Instructor/StoreModuleRequest.php`
- Modify: `app/Services/Content/ContentAuthoringService.php`
- Modify: `app/Services/Connectors/ConnectorModuleAccessService.php`
- Modify: `app/Services/Seminars/SeminarPublicationValidator.php`
- Modify: `app/Services/Seminars/SeminarRegistrationService.php`
- Modify: `app/Services/Seminars/SeminarExportService.php`
- Modify: `app/Services/Chat/ChatSuggestionCatalog.php`
- Modify: `config/seminars.php`
- Modify: `config/chat_suggestions.php`
- Create: `app/Console/Commands/AuditLegacyKidsContent.php`
- Create: `tests/Feature/Console/AuditLegacyKidsContentTest.php`
- Modify: `tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php`
- Modify: `tests/Feature/Learner/ModuleAgeBracketUpdateVisibilityTest.php`
- Modify: `tests/Feature/Instructor/InstructorModuleConfigValidationTest.php`
- Modify: `tests/Feature/Admin/AdminLearningPathManagementTest.php`
- Modify: `tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php`
- Modify: `tests/Feature/Seminars/SeminarRegistrationTest.php`
- Modify: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`
- Modify: `tests/Unit/Chat/ChatSuggestionCatalogTest.php`

**Interfaces:**
- Active category keys: `teens`, `adults`; public labels: `Teenager`, `Adult`.
- Compatibility reads: singular `teen` and `adult` normalize to active plural keys; raw `kids` remains visible only to audit code.
- Active writes: reject `kids`, `child`, `learner-child`, unknown values, and empty audience arrays.
- Delivery: Kids-only or Kids-worded legacy content is hidden; mixed targeting continues only for its remaining active categories without changing stored raw data.
- Produces: read-only `content:audit-legacy-kids`; there is deliberately no `--apply` option.

- [ ] **Step 1: Write failing active-write, legacy-read, delivery, and audit tests**

For modules, learning paths, seminars/events, connector assignment, and chat suggestions, cover:

- new writes accept only canonical `teens`/`adults` and normalize singular compatibility input only where the existing stored record is read;
- exact ages 10 and 17 receive Teenager content; exact ages 18 and 100 receive Adult content;
- age 9, 101, and unclassified learners receive no active content;
- existing Kids-only content is absent from discovery, assignment, publication, registration, and learner dashboards;
- existing `['kids', 'teens']` content remains stored unchanged but can be delivered to Teenagers through its active `teens` audience;
- a title/description containing the whole word `Kid` or `Kids` is hidden pending owner correction even when another active category exists;
- words such as `kidney` do not trigger the wording rule;
- the audit command reports identifiers and reasons but changes no row/timestamp;
- no service infers that legacy Kids means Teenager.

Run:

~~~powershell
php artisan test tests/Feature/Console/AuditLegacyKidsContentTest.php tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php tests/Feature/Seminars/SeminarRegistrationTest.php tests/Unit/Chat/ChatSuggestionCatalogTest.php
~~~

Expected: FAIL because Kids remains active and age ranges begin below 10/at 13.

- [ ] **Step 2: Make `Module` expose active normalization and separate raw legacy inspection**

Replace duplicated ranges and labels with:

~~~php
public static function learnerCategoryAgeRanges(): array
{
    return [
        LearnerAgePolicy::CATEGORY_TEENAGERS => [10, 17],
        LearnerAgePolicy::CATEGORY_ADULTS => [18, 100],
    ];
}

public static function normalizeLearnerCategory(string $category): ?string
{
    return LearnerAgePolicy::normalizeActiveCategory($category);
}

public static function normalizeLearnerCategories(array $categories): array
{
    return collect($categories)
        ->map(fn (mixed $category): ?string => is_string($category)
            ? self::normalizeLearnerCategory($category)
            : null)
        ->filter()
        ->unique()
        ->values()
        ->all();
}
~~~

Keep a clearly named `rawLearnerCategoriesForLegacyAudit()` that decodes the stored JSON without normalizing or saving it. Add query/model helpers for:

- `hasLegacyKidsOnlyTargeting()` - raw category includes Kids and active normalization is empty;
- `hasLegacyKidsWording()` - case-insensitive whole-word `kid`/`kids` in title or description;
- `isDeliverableToCategory($category)` - false for either hidden condition, otherwise true only when normalized active categories contain the category.

Do not call `save()` from an accessor or compatibility reader.

- [ ] **Step 3: Reject Kids at every authoring trust boundary**

Use `Rule::in(LearnerAgePolicy::activeCategories())` in Admin/Instructor module and learning-path requests. Require at least one active category after normalization. Remove Kids from controller-provided option arrays. `ContentAuthoringService` must reject an empty/legacy-only normalized audience rather than defaulting to Teenager.

Crafted `kids`, `child`, or conflicting min/max age requests must return validation errors and leave the record unchanged.

- [ ] **Step 4: Normalize seminar/event compatibility reads and canonicalize new writes**

Change `config/seminars.php` active audience keys to `teens` and `adults`, with ranges 10-17 and 18-100. In `SeminarPublicationValidator`, accept only canonical active values for new or edited records. In `SeminarRegistrationService::learnerAgeCategoryFor()`, return the policy category directly.

When reading an existing singular `teen`/`adult`, normalize in memory. Existing `kids` is legacy-hidden. `SeminarExportService` uses public labels `Teenager`/`Adult`, and restricted audit output may retain `Legacy Kids`.

- [ ] **Step 5: Move connectors and chat to the same live policy**

Replace connector `age < 13` guardian logic with `LearnerAgePolicy::requiresGuardian()`, and use current active content helpers for module assignment. `ChatSuggestionCatalog::resolveAudience()` must return `teens`, `adults`, or null from the policy. Remove the Kids active suggestion set from `config/chat_suggestions.php`; do not remap its entries into Teenager.

- [ ] **Step 6: Implement the read-only legacy content audit**

`content:audit-legacy-kids` must inspect modules, learning paths, seminars/events, and other persisted audience fields already used by these services. Report only:

- model/table name;
- record ID;
- `kids_only`, `mixed_with_kids`, or `kids_wording` reason;
- remaining normalized active audiences.

It must not include learner PII, evidence paths, descriptions, or raw private content. It must have no apply flag and make no writes. Exit zero after a successful scan even when findings exist; use non-zero only for scan failures.

- [ ] **Step 7: Run all focused content-domain tests and a read-only audit**

~~~powershell
php artisan test tests/Feature/Console/AuditLegacyKidsContentTest.php tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php tests/Feature/Learner/ModuleAgeBracketUpdateVisibilityTest.php tests/Feature/Instructor/InstructorModuleConfigValidationTest.php tests/Feature/Admin/AdminLearningPathManagementTest.php tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Unit/Chat/ChatSuggestionCatalogTest.php
php artisan content:audit-legacy-kids
vendor/bin/pint --test app/Models/Module.php app/Http/Controllers/Admin/AdminModuleController.php app/Http/Requests/Admin/SaveLearningPathRequest.php app/Http/Requests/Instructor/StoreModuleRequest.php app/Services/Content/ContentAuthoringService.php app/Services/Connectors/ConnectorModuleAccessService.php app/Services/Seminars/SeminarPublicationValidator.php app/Services/Seminars/SeminarRegistrationService.php app/Services/Seminars/SeminarExportService.php app/Services/Chat/ChatSuggestionCatalog.php app/Console/Commands/AuditLegacyKidsContent.php tests/Feature/Console/AuditLegacyKidsContentTest.php
~~~

Expected: all focused tests PASS; audit reports findings without changing timestamps or data.

- [ ] **Step 8: Commit content-policy behavior**

~~~powershell
git add app/Models/Module.php app/Http/Controllers/Admin/AdminModuleController.php app/Http/Requests/Admin/SaveLearningPathRequest.php app/Http/Requests/Instructor/StoreModuleRequest.php app/Services/Content/ContentAuthoringService.php app/Services/Connectors/ConnectorModuleAccessService.php app/Services/Seminars/SeminarPublicationValidator.php app/Services/Seminars/SeminarRegistrationService.php app/Services/Seminars/SeminarExportService.php app/Services/Chat/ChatSuggestionCatalog.php config/seminars.php config/chat_suggestions.php app/Console/Commands/AuditLegacyKidsContent.php tests/Feature/Console/AuditLegacyKidsContentTest.php tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php tests/Feature/Learner/ModuleAgeBracketUpdateVisibilityTest.php tests/Feature/Instructor/InstructorModuleConfigValidationTest.php tests/Feature/Admin/AdminLearningPathManagementTest.php tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Unit/Chat/ChatSuggestionCatalogTest.php
git commit -m "feat: retire kids content targeting"
~~~

---

### Task 12: Remove Kids from ordinary UI, reports, exports, factories, and seed data

**Files:**
- Modify: `app/Services/AdminDashboardService.php`
- Modify: `app/Services/Admin/UserManagementService.php`
- Modify: `app/Http/Requests/Admin/IndexUserRequest.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `app/Http/Controllers/Admin/UserAdminController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `resources/views/admin/learning-paths/form.blade.php`
- Modify: `resources/views/admin/modules/index.blade.php`
- Modify: `resources/views/admin/modules/partials/form.blade.php`
- Modify: `resources/views/admin/users/show.blade.php`
- Modify: `resources/views/admin/users/partials/filter-toolbar.blade.php`
- Modify: `resources/views/admin/users/partials/users-table.blade.php`
- Modify: `resources/views/learner/dashboard.blade.php`
- Modify: `resources/views/landing/index.blade.php`
- Modify: `resources/views/instructor/guidelines.blade.php`
- Modify: `resources/views/instructor/enrollments/index.blade.php`
- Modify: `resources/views/instructor/modules/create.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/modules/partials/module-modal.blade.php`
- Modify: `resources/views/connectors/members/index.blade.php`
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Modify: `database/factories/LearnerProfileFactory.php`
- Modify: `database/seeders/CommunityFeedDemoSeeder.php`
- Modify: `database/seeders/LocalLearningPathDemoSeeder.php`
- Modify: `database/seeders/TestModulesSeeder.php`
- Modify: `database/seeders/TestUserSeeder.php`
- Modify: `tests/JavaScript/learning-path-builder.test.mjs`
- Modify: `tests/Feature/Admin/AdminDashboardMetricsTest.php`
- Modify: `tests/Feature/Admin/AdminUsersUiAlignmentTest.php`
- Modify: `tests/Feature/Admin/AdminUserWizardFlowTest.php`
- Modify: `tests/Feature/Instructor/InstructorGuidelinesPageTest.php`
- Modify: `tests/Feature/Instructor/InstructorModulesIndexUiTest.php`
- Modify: `tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php`
- Modify: `tests/Feature/Seeders/TestUserSeederTest.php`
- Modify: `tests/Feature/Seeders/CommunityFeedDemoSeederTest.php`
- Create: `tests/Feature/Seeders/AgePolicySeederTest.php`

**Interfaces:**
- Ordinary active UI and reports expose only `Teenager`, `Adult`, and separate `Ineligible`/`Unclassified` review counts where operationally needed.
- No disabled Kids option, badge, card, filter, navigation item, landing copy, authoring choice, or ordinary export remains.
- Restricted legacy audit output remains allowed and is clearly labeled historical.
- Factories/seeders produce only eligible Teenager/Adult DOBs unless a test explicitly names a legacy/ineligible scenario.

- [ ] **Step 1: Update failing UI/report contract tests first**

Add assertions that:

- Admin active statistics reconcile Teenager + Adult + Ineligible + Unclassified to the learner total;
- active filters accept only `teens` and `adults`, while invalid `kids` requests are rejected or ignored safely;
- ordinary user rows and detail pages never translate `learner-child` into an active Kids badge;
- learner/guardian/instructor/connector pages contain Teenager/Adult copy and no Kids option;
- active exports have Teenager/Adult columns or labels only;
- factory/seeder output stays within ages 10-100 and produces no active Kids category;
- the JavaScript learning-path builder submits only `teens`/`adults` and rejects empty audiences.

Run the existing focused tests for these surfaces and confirm they fail before implementation.

- [ ] **Step 2: Rebuild active report buckets from explicit eligibility**

In `AdminDashboardService` and `UserManagementService`, remove 5-12/13-17 ranges and use explicit cache values only for query performance, with the live reconciliation command responsible for freshness. Active cards are:

~~~php
[
    'teenagers' => (clone $learners)->where('age_eligibility_cached', LearnerAgePolicy::ELIGIBLE_TEENAGER)->count(),
    'adults' => (clone $learners)->where('age_eligibility_cached', LearnerAgePolicy::ELIGIBLE_ADULT)->count(),
    'ineligible' => (clone $learners)->whereIn('age_eligibility_cached', [
        LearnerAgePolicy::INELIGIBLE_UNDERAGE,
        LearnerAgePolicy::INELIGIBLE_OVERAGE,
    ])->count(),
    'unclassified' => (clone $learners)->where('age_eligibility_cached', LearnerAgePolicy::UNCLASSIFIED)->count(),
]
~~~

Do not fold null/unclassified into Adult and do not omit them from reconciliation. `IndexUserRequest` permits `teens`, `adults`, `ineligible`, and `unclassified`; ordinary filters never expose raw `kids`.

- [ ] **Step 3: Replace options and copy without redesigning pages**

On every listed Blade surface:

- option value `teens`, label `Teenager`;
- option value `adults`, label `Adult`;
- remove Kids option/card/badge entirely;
- replace under-13/13+ instructions with guardian-required ages 10-17 and independent ages 18-100;
- keep existing components, layout, spacing, responsive behavior, focus styles, and validation rendering.

For a legacy `learner-child` row shown in ordinary Admin user history, display `Legacy learner account - review required`, not `Kids`. Raw values may appear only in the restricted audit command/output.

- [ ] **Step 4: Make fixtures active-policy-safe**

Change default learner factory DOB generation to 10-100 and derive category through `LearnerAgePolicy`. Update local/test seeders to create Teenager/Adult data only, except named fixtures used by reclassification/content-audit tests. Never run these seeders against development as part of this task.

Do not change existing production data or delete legacy seeder classes merely to make a scan quiet.

- [ ] **Step 5: Update JavaScript and server-rendered authoring tests**

Change the builder's category constants and expected payloads to `['teens', 'adults']`. Add a test that a legacy loaded `kids` value is omitted from the active submitted payload and that the user must choose at least one active audience before save.

- [ ] **Step 6: Run the UI/report/fixture suite and review every literal result**

~~~powershell
php artisan test tests/Feature/Admin/AdminDashboardMetricsTest.php tests/Feature/Admin/AdminUserManagementFeatureTest.php tests/Feature/Admin/AdminUsersUiAlignmentTest.php tests/Feature/Admin/AdminUserWizardFlowTest.php tests/Feature/Admin/AdminLearningPathManagementTest.php tests/Feature/Instructor/InstructorGuidelinesPageTest.php tests/Feature/Instructor/InstructorModulesIndexUiTest.php tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php tests/Feature/Seeders/TestUserSeederTest.php tests/Feature/Seeders/CommunityFeedDemoSeederTest.php tests/Feature/Seeders/AgePolicySeederTest.php
node --test tests/JavaScript/learning-path-builder.test.mjs
rg -n -i "\bkids?\b|learner-child|@child\.sexed-platform\.local|gmail\.com" app resources config routes database/factories database/seeders
~~~

Expected: tests PASS. Every remaining literal match must be one of: explicit legacy compatibility, the read-only audit command, a migration/schema constant, or text that says Kids is retired/legacy in a restricted context. No active UI, active config, placeholder-email creation, Gmail-alias generation, or new-write validator may remain.

- [ ] **Step 7: Run formatting and commit interface cleanup**

~~~powershell
vendor/bin/pint --test app database/factories database/seeders tests/Feature
git add app/Services/AdminDashboardService.php app/Services/Admin/UserManagementService.php app/Http/Requests/Admin/IndexUserRequest.php app/Http/Controllers/DashboardController.php app/Http/Controllers/Admin/UserAdminController.php app/Http/Controllers/Instructor/ModuleController.php resources/views/admin/learning-paths/form.blade.php resources/views/admin/modules/index.blade.php resources/views/admin/modules/partials/form.blade.php resources/views/admin/users/show.blade.php resources/views/admin/users/partials/filter-toolbar.blade.php resources/views/admin/users/partials/users-table.blade.php resources/views/learner/dashboard.blade.php resources/views/landing/index.blade.php resources/views/instructor/guidelines.blade.php resources/views/instructor/enrollments/index.blade.php resources/views/instructor/modules/create.blade.php resources/views/instructor/modules/edit.blade.php resources/views/instructor/modules/index.blade.php resources/views/instructor/modules/partials/module-modal.blade.php resources/views/connectors/members/index.blade.php resources/views/connectors/seminars/show.blade.php database/factories/LearnerProfileFactory.php database/seeders/CommunityFeedDemoSeeder.php database/seeders/LocalLearningPathDemoSeeder.php database/seeders/TestModulesSeeder.php database/seeders/TestUserSeeder.php tests/JavaScript/learning-path-builder.test.mjs tests/Feature/Admin/AdminDashboardMetricsTest.php tests/Feature/Admin/AdminUsersUiAlignmentTest.php tests/Feature/Admin/AdminUserWizardFlowTest.php tests/Feature/Instructor/InstructorGuidelinesPageTest.php tests/Feature/Instructor/InstructorModulesIndexUiTest.php tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php tests/Feature/Seeders/TestUserSeederTest.php tests/Feature/Seeders/CommunityFeedDemoSeederTest.php tests/Feature/Seeders/AgePolicySeederTest.php
git commit -m "refactor: remove kids from active interfaces"
~~~

---

### Task 13: Verify the complete workflow and prepare a no-surprises rollout handoff

**Files:**
- Verify: all task-owned source, migration, route, view, notification, config, test, and documentation paths.
- Modify only if a verification failure proves a task-owned defect; do not broaden scope to unrelated failures.

**Interfaces:**
- Produces: separate evidence for source/static checks, focused PHPUnit, broader PHPUnit, JavaScript, browser, mail/provider, database dry-run, deployment, and production.
- Does not produce: a production data apply, destructive migration, seed run, or deployment claim.

- [ ] **Step 1: Self-review the implementation against every approved design section**

Build a checked matrix in the implementation report with one row for each:

1. authoritative age boundaries;
2. cache compatibility and no Adult default;
3. public/guardian/Admin entry points;
4. guardian identity, relationship, consent, activation, and Teenager identity separation;
5. no-email activation/login/recovery;
6. eighteenth birthday and DOB corrections;
7. active content/UI/report retirement of Kids;
8. legacy account/content preservation;
9. privacy, token, evidence, concurrency, and failure handling;
10. rollout gates and explicit non-goals.

For each row, cite the implementing file/tests or mark it not implemented. Do not infer completion from the plan.

- [ ] **Step 2: Check route and migration shape without touching development data**

~~~powershell
php artisan route:list --except-vendor
php artisan migrate:status
php artisan learners:reclassify-age-policy --chunk=100
php artisan content:audit-legacy-kids
~~~

Expected: routes resolve with no duplicate names; migration status is reported only; both commands are read-only and clearly label/report their behavior. Do not run `php artisan migrate`, `--apply`, or any seeder against development during this verification step.

- [ ] **Step 3: Run the focused security and lifecycle suite**

~~~powershell
php artisan test tests/Unit/Support/LearnerAgePolicyTest.php tests/Feature/AgePolicy tests/Feature/Console/ReclassifyLearnersForAgePolicyTest.php tests/Feature/Console/AuditLegacyKidsContentTest.php tests/Feature/Auth/GuardianTeenagerRegistrationTest.php tests/Feature/Auth/TeenagerStartedRegistrationTest.php tests/Feature/Auth/TeenagerAccountActivationTest.php tests/Feature/Auth/TeenagerAccessGateTest.php tests/Feature/Auth/TeenagerPasswordRecoveryTest.php tests/Feature/Auth/LearnerAgeRegistrationPolicyTest.php tests/Feature/Identity/LearnerAgeIdentityTransitionTest.php
~~~

Expected: all focused tests PASS with no real mail/provider calls and no development database mutation.

- [ ] **Step 4: Run affected regression groups**

~~~powershell
php artisan test tests/Feature/Auth tests/Feature/Identity tests/Feature/Admin tests/Feature/GuardianRelationshipCorrectionsTest.php tests/Feature/GuardianRelationshipCorrectionDraftHttpTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Learner tests/Feature/LearningPath tests/Feature/Instructor tests/Feature/Seminars tests/Feature/Connectors tests/Unit/Chat
node --test tests/JavaScript/learning-path-builder.test.mjs
~~~

Expected: affected suites PASS. If an existing unrelated migration/test-infrastructure failure occurs, capture the exact command and error separately; do not call the feature verified through that gate.

- [ ] **Step 5: Run full static and repository checks**

~~~powershell
vendor/bin/pint --test app bootstrap config database routes tests
git diff --check
rg -n "\b(T[B]D|T[O]DO|F[I]XME|X[X]X)\b" docs/superpowers/plans/2026-11-12-system-wide-age-policy-and-minor-registration.md
rg -n -i "\bkids?\b|learner-child|@child\.sexed-platform\.local|gmail\.com" app resources config routes database/factories database/seeders
git status --short
~~~

Expected: Pint and diff checks PASS; the plan placeholder scan returns no matches; every remaining legacy literal is reviewed and justified; unrelated `public/assets/` remains untouched.

- [ ] **Step 6: Perform role-based local browser checks with the installed browser automation**

Use the `agent-browser` skill against the local app and capture evidence for:

1. guardian-first no-email Teenager request through pending relationship;
2. relationship approval, guardian activation handoff, Teenager password/privacy setup;
3. Teenager ID plus selfie submission remains pending until Admin approval;
4. fully approved no-email Teenager can sign in by username and reach learner features;
5. Teenager-started invitation with guardian login/resume;
6. guardian-approved password recovery and one-time code reuse denial;
7. Adult independent registration and own-email verification;
8. a simulated eighteenth-birthday/DOB-transition account requiring own email and Adult identity;
9. Admin/Instructor content forms and ordinary reports showing Teenager/Adult only;
10. direct protected URLs denied in every incomplete state.

Check narrow/mobile and desktop layouts, keyboard traversal, visible labels/errors, focus placement, and touch targets. Do not use real identity documents or real minor PII.

- [ ] **Step 7: Verify mail and provider boundaries separately**

With the configured local/sandbox mailer, verify guardian invitation, activation, recovery, and own-email verification rendering. Confirm messages contain no passwords, evidence, private learner messages, or sensitive identifiers. Record Persona/provider checks as unverified unless a sandbox callback is actually exercised; provider completion must still leave Admin approval pending.

- [ ] **Step 8: Rehearse data transition only on an isolated backup or staging clone**

After an authorized backup is confirmed:

1. run both dry-run commands and save reconciled counts;
2. review every underage, overage, unclassified, DOB-mismatch, missing-guardian, Kids-only, mixed-Kids, and Kids-wording finding;
3. obtain explicit approval for account reclassification;
4. run `learners:reclassify-age-policy --apply` only on the isolated clone;
5. rerun dry-run and assert zero cache mismatches, unchanged relationship/progress/enrollment/attempt/certificate counts, and idempotent second apply;
6. do not apply content retargeting because no such command exists.

Deployment alone must not perform Step 4. Production apply requires its own backup, anomaly review, approval, monitoring, and evidence.

- [ ] **Step 9: Run the complete PHPUnit suite and report honestly**

~~~powershell
php artisan test
~~~

Expected: PASS is the release target. If blocked by pre-existing infrastructure debt, report the exact first failure, which focused suites passed, and which risks remain; do not label the full gate passed.

- [ ] **Step 10: Commit only verification-driven task fixes, then produce the handoff**

Do not create an empty verification commit. If verification required a task-owned fix, reuse that task's explicit `git add` list with only the files actually changed and commit with `test: verify learner age policy rollout`; never stage an entire dirty directory. The final handoff must list commits, migrations, new commands, test/browser/mail evidence, unrun gates, staging/production prerequisites, and the fact that no production data transition was performed.

