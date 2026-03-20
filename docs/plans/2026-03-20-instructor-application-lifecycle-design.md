# Instructor Application and Lifecycle System - Design Document

**Date:** 2026-03-20
**Feature:** Instructor Application and Lifecycle System
**Status:** Design Phase - Awaiting Approval

---

## Executive Summary

This design implements a complete instructor application and lifecycle system where learners can apply to become instructors through a vetted approval process. The system addresses the architectural inconsistency where instructors exist in Spatie roles but not in the `users.role` enum, and creates a pathway for instructor sustainability through content creation.

**Core Features:**
- Learners apply to become instructors with two-tier document verification
- Admins review applications and approve/reject with reasons
- Upon approval, user role transitions from 'learner' → 'instructor' (full transition)
- Login system unified at `/login` for both learners and instructors
- All learner data preserved but role access changes

---

## Problem Statement

### Current State Issues

**Architectural Inconsistency:**
- `users.role` enum: `['learner', 'organization', 'clinic', 'counselor', 'admin']` ❌ No 'instructor'
- Instructors exist only in Spatie Permission roles (`hasRole('instructor')`)
- User model has `isAdmin()`, `isCounselor()`, etc. but **no `isInstructor()`**
- Dashboard routing doesn't handle 'instructor' role in match statement (DashboardController line 29-35)

**No Organic Path to Instructor:**
- Instructors can only be created manually by admins manipulating Spatie roles
- No application process, verification, or approval workflow
- No vetting mechanism for instructor quality/credentials

**Multiple Login Portals:**
- 3 separate login pages: `/admin/login`, `/instructor/login`, `/login`
- Creates confusion and maintenance burden
- Thesis advisor wants centralization

### Requirements from Thesis Advisor

1. **Instructor Lifecycle:** Learners → Apply for Instructor → Admin Approval → Role Transition
2. **Document Verification:** Legitimate credentials required (teaching certs, sex ed training, background checks)
3. **Centralized Login:** Remove `/instructor/login`, unify learner + instructor at `/login`
4. **Role Transition:** Full transition (lose learner access, gain instructor access)
5. **Foundation for Monetization:** Prepares for future module marketplace, seminar speakers, instructor subscriptions

---

## Design Goals

1. **Clean Architecture:** Add 'instructor' to enum for consistency with existing role patterns
2. **Reuse Existing Patterns:** Follow Clinic/Counselor approval workflow (proven, tested)
3. **Data Integrity:** Preserve all learner data (enrollments, progress, gamification, certificates)
4. **Security:** Two-tier verification (mandatory safety checks + proof of expertise)
5. **Maintainability:** Follow Laravel conventions (thin controllers, service classes, form requests)
6. **User Experience:** Clear application flow, helpful rejection feedback, smooth transition
7. **Scalability:** Foundation for future instructor monetization features

---

## Recommended Approach: Full Role Transition

### Strategy

Treat instructor as a **true role change**, not a dual-access system:
- `user.role` changes from `'learner'` to `'instructor'` (database enum updated)
- Spatie role `'instructor'` assigned (belt-and-suspenders approach)
- User **loses learner dashboard access** (full transition per user requirement)
- User **gains instructor panel access**
- All learner data **preserved** (enrollments, progress, certificates, gamification) but archived as read-only

### Why This Approach?

**Alignment with Requirements:**
- ✅ Matches user's chosen requirement: "Full transition - become instructor only"
- ✅ Clean architectural separation: users are EITHER learners OR instructors, never both
- ✅ Consistent with existing role patterns (isAdmin(), isCounselor() check `user.role`)
- ✅ Future-proof for role-based pricing and features

**Existing Pattern Reuse:**
- Clinic and Counselor models already have mature approval workflows
- Same fields: `approval_status`, `approved_by`, `approved_at`, `rejection_reason`
- Same scopes: `approved()`, `pending()`, `rejected()`
- SoftDeletes for audit trails

### Alternative Approaches Considered

**Approach 1: Hybrid Role System (Not Chosen)**
- Keep `user.role='learner'`, use only Spatie roles for instructor access
- **Pros:** No enum migration risk, faster implementation
- **Cons:** Architectural inconsistency persists, confusing for future developers

**Approach 3: Hybrid with Enum (Not Chosen)**
- Add 'instructor' to enum BUT allow dual access (dashboard switcher)
- **Pros:** Best user experience, instructors can continue learning
- **Cons:** Doesn't match user's requirement for "full transition to instructor only"

---

## Document Verification Requirements

### Two-Tier Verification System

Balances **safety** (protect learners) with **inclusivity** (recognize diverse expertise paths).

#### Tier 1: Required for ALL Applicants (Safety & Identity)
1. **Government-issued ID** (PDF/JPG) - Identity verification
   - Accepted: Philippine driver's license, passport, national ID, postal ID
   - Must be valid (not expired)

2. **Background Clearance** (PDF/JPG) - Safety requirement
   - NBI clearance OR police clearance
   - Given sensitive topic (sex education) and minor audience
   - Must be issued within last 6 months

3. **Professional Background (CV/Bio)** (Text, 100-5000 chars)
   - Resume or biographical statement
   - Demonstrates relevant experience in education, health, or related fields

#### Tier 2: At Least ONE Required (Proof of Expertise)
Applicant must provide **at least one** of the following:

1. **Teaching Credentials** (PDF/JPG)
   - Professional Teaching Certificate (LET)
   - Teaching certificate from recognized institution
   - Relevant degree (Education, Health Sciences, Psychology, Nursing, Social Work)

2. **Sex Education Training Certificate** (PDF/JPG)
   - Specialized training from recognized organizations
   - Examples: UNFPA, Likhaan, PNGOC, DOH, DepEd sex ed programs
   - Certificate of completion from accredited training providers

3. **Professional License** (PDF/JPG)
   - Nurse (PRC license)
   - Registered Counselor
   - Social Worker
   - Other relevant health/counseling profession

### Why This Two-Tier System?

**Inclusive:** Doesn't lock out qualified practitioners without formal teaching degrees (e.g., NGO facilitators with extensive training, nurses specializing in adolescent health)

**Safe:** NBI clearance is standard in Philippines for working with minors

**Credible:** Requires proof of expertise through formal or specialized education

**Real-World Aligned:** Matches patterns from:
- Udemy (ID verification)
- TeachAway Philippines (NBI clearance + teaching cert OR relevant degree)
- Khan Academy (background checks + teaching demonstration)

---

## Database Schema

### 1. Alter `users.role` Enum (CRITICAL)

**Migration:** `YYYY_MM_DD_HHMMSS_add_instructor_to_users_role_enum.php`

```php
public function up(): void
{
    // For MySQL 8.0+ / PostgreSQL
    DB::statement("
        ALTER TABLE users
        MODIFY COLUMN role
        ENUM('learner', 'organization', 'clinic', 'counselor', 'instructor', 'admin')
        DEFAULT 'learner'
    ");
}

public function down(): void
{
    // WARNING: This will fail if any users have role='instructor'
    DB::statement("
        ALTER TABLE users
        MODIFY COLUMN role
        ENUM('learner', 'organization', 'clinic', 'counselor', 'admin')
        DEFAULT 'learner'
    ");
}
```

**Risk Mitigation:**
- Test on staging database first
- Check for existing Spatie 'instructor' role users: `User::role('instructor')->count()`
- Backup users table before running production migration
- Consider maintenance window for large databases

---

### 2. Create `instructor_applications` Table

Mirrors Clinic and Counselor approval workflow patterns.

```php
Schema::create('instructor_applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

    // Tier 1 Documents (All Required)
    $table->string('government_id_path');
    $table->string('clearance_path');
    $table->text('bio');

    // Tier 2 Documents (At Least ONE Required)
    $table->string('teaching_credential_path')->nullable();
    $table->string('sexed_certificate_path')->nullable();
    $table->string('professional_license_path')->nullable();

    // Approval Workflow
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();

    // Metadata for Auditing
    $table->json('application_metadata')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['user_id', 'status']);
});
```

**Fields Explained:**
- `status`: Tracks application state (pending → approved/rejected)
- Document paths: Store file locations in `storage/app/public/instructor-applications/`
- `approved_by`: Foreign key to admin who approved (audit trail)
- `rejection_reason`: Required when rejecting, helps applicant improve
- `application_metadata`: JSON field for original filenames, file sizes, submission IP, etc.
- SoftDeletes: Preserves rejected applications for compliance

---

### 3. Create `instructor_profiles` Table

Stores instructor-specific profile data separate from learner profiles.

```php
Schema::create('instructor_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->text('bio');
    $table->string('specialization')->nullable(); // e.g., "Adolescent Health"
    $table->json('credentials')->nullable(); // Structured credential data
    $table->timestamps();

    $table->unique('user_id');
});
```

**Purpose:**
- Stores bio and specialization for instructor's public profile
- `credentials` JSON stores paths to verified documents from application
- Can be extended later with instructor ratings, reviews, module sales analytics

---

### 4. Create `role_transitions` Table

Audit trail for compliance and data integrity.

```php
Schema::create('role_transitions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('from_role');
    $table->string('to_role');
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->text('reason')->nullable();
    $table->json('preserved_data')->nullable(); // Snapshot of learner state
    $table->timestamp('transitioned_at');

    $table->index('user_id');
});
```

**Purpose:**
- Records when/why users changed roles
- Preserves snapshot of learner data at time of transition:
  - Enrolled modules count
  - Certificates earned
  - Gamification level and score
  - Subscription status
  - Last activity timestamp
- Compliance requirement for auditing role changes

---

## Application Flow

### User Journey

```
[Learner Dashboard]
   ↓
   User clicks "Apply to Become Instructor"
   ↓
[Application Form] /learn/instructor/apply
   - Upload Government ID
   - Upload NBI/Police Clearance
   - Enter Bio/CV (min 100 chars)
   - Upload at least ONE Tier 2 credential
   - Submit
   ↓
[Application Submitted Confirmation]
   - "Your application is under review"
   - Status: PENDING
   ↓
[Admin Reviews] /admin/instructor-applications
   - View application details
   - Preview uploaded documents
   - Decision: APPROVE or REJECT
   ↓
   ├─ APPROVED ─→ [Role Transition Process]
   │               - user.role = 'instructor'
   │               - Spatie role assigned
   │               - instructor_profile created
   │               - role_transition recorded
   │               - Notification sent
   │               ↓
   │              [Instructor Dashboard] /instructor
   │               - Can create modules/lessons/quizzes
   │               - Cannot access learner dashboard
   │
   └─ REJECTED ─→ [Rejection Notification]
                   - Email with reason
                   - User remains learner
                   - Can reapply after addressing issues
```

---

## Role Transition Mechanism

The approval process is handled by `InstructorApplicationService::approve()` in a database transaction:

### Step-by-Step Process

```php
DB::transaction(function() use ($application) {
    $user = $application->user;

    // 1. Preserve learner state snapshot
    $snapshot = [
        'enrolled_modules_count' => $user->moduleEnrollments()->count(),
        'certificates_earned' => $user->certificates()->count(),
        'gamification_level' => $user->gamification->level ?? 0,
        'gamification_score' => $user->gamification->score ?? 0,
        'subscription_status' => $user->subscription?->status,
        'last_activity' => $user->userProgress()->latest()->first()?->updated_at,
    ];

    // 2. Record role transition
    RoleTransition::create([
        'user_id' => $user->id,
        'from_role' => 'learner',
        'to_role' => 'instructor',
        'approved_by' => auth()->id(),
        'reason' => 'Instructor application approved',
        'preserved_data' => $snapshot,
        'transitioned_at' => now(),
    ]);

    // 3. Update user role in database
    $user->update(['role' => 'instructor']);

    // 4. Assign Spatie role (belt-and-suspenders)
    $user->assignRole('instructor');

    // 5. Create instructor profile
    InstructorProfile::create([
        'user_id' => $user->id,
        'bio' => $application->bio,
        'credentials' => [
            'government_id' => $application->government_id_path,
            'clearance' => $application->clearance_path,
            'teaching_credential' => $application->teaching_credential_path,
            'sexed_certificate' => $application->sexed_certificate_path,
            'professional_license' => $application->professional_license_path,
        ],
    ]);

    // 6. Update application status
    $application->update([
        'status' => 'approved',
        'approved_by' => auth()->id(),
        'approved_at' => now(),
    ]);

    // 7. Send notification
    $user->notify(new InstructorApplicationApproved($application));

    // 8. Cancel active subscription (optional)
    if ($user->subscription && $user->subscription->status === 'active') {
        app(SubscriptionService::class)->cancel(
            $user->subscription,
            'Role changed to instructor'
        );
    }
});
```

### What Happens to Learner Data?

**Preserved but Archived:**
- ✅ **Module enrollments:** Kept in database, can be viewed but not continued
- ✅ **Progress records:** Preserved (UserProgress, LessonProgress, LessonTopicProgress)
- ✅ **Certificates earned:** Still accessible in database
- ✅ **Gamification stats:** Level, score, streak data preserved
- ✅ **Subscription history:** Payment records and invoices maintained

**Changed Access:**
- ❌ **Learner dashboard:** No longer accessible (403 Forbidden or redirect to instructor dashboard)
- ❌ **New enrollments:** Instructors cannot enroll in new modules as learners
- ❌ **Active subscription:** Canceled (instructors may have different pricing model in future)
- ✅ **Instructor dashboard:** Full access to content creation tools

---

## Authentication & Login System Updates

### Current State (3 Login Portals)
- `/admin/login` → AdminAuthController
- `/instructor/login` → InstructorAuthController
- `/login` → AuthenticatedSessionController (learners, organizations, clinics, counselors)

### New State (2 Login Portals)
- `/admin/login` → AdminAuthController (unchanged)
- `/login` → AuthenticatedSessionController (learners, instructors, organizations, clinics, counselors)

### Changes Required

**1. Deprecate `/instructor/login`** (routes/auth.php)
```php
// Redirect old instructor login to unified login
Route::get('instructor/login', function() {
    return redirect()->route('login')
        ->with('info', 'Please use the main login page.');
})->name('instructor.login');

Route::post('instructor/login', function() {
    return redirect()->route('login');
});
```

**2. Update AuthenticatedSessionController** (already works!)
```php
// Line 48-51 (already handles instructors)
if ($user->hasRole('instructor')) {
    return redirect()->intended(route('instructor.dashboard'))
        ->with('success', "Welcome back, {$userName}!");
}

// Add enum check for belt-and-suspenders
if ($user->role === 'instructor' || $user->hasRole('instructor')) {
    return redirect()->intended(route('instructor.dashboard'))
        ->with('success', "Welcome back, {$userName}!");
}
```

**3. Update DashboardController::index()** (line 29-35)
```php
return match($user->role) {
    'admin' => $this->adminDashboard(),
    'instructor' => redirect()->route('instructor.dashboard'), // NEW
    'counselor' => $this->counselorDashboard(),
    'clinic' => $this->clinicDashboard(),
    'organization' => $this->organizationDashboard(),
    default => $this->learnerDashboard(),
};
```

---

## File Storage

### Directory Structure
```
storage/app/public/instructor-applications/
  {user_id}/
    {timestamp}_government_id.{ext}
    {timestamp}_clearance.{ext}
    {timestamp}_teaching_credential.{ext}
    {timestamp}_sexed_certificate.{ext}
    {timestamp}_professional_license.{ext}
```

### File Upload Configuration
- **Accepted formats:** PDF, JPG, JPEG, PNG
- **Max file size:** 5MB per file
- **Storage disk:** `public` (use `php artisan storage:link`)
- **Naming convention:** `{timestamp}_{type}.{ext}` prevents overwrites

### Security Considerations
- Files stored in `storage/app/public/` (accessible via symlink)
- Admin-only access to view documents (route middleware: `role:admin`)
- File paths stored in database (never expose directly in URLs)
- Consider encrypting sensitive documents (future enhancement)

---

## Admin Approval Interface

### Index Page: `/admin/instructor-applications`

**Features:**
- Tabbed interface: Pending | Approved | Rejected
- Default tab: Pending
- Table columns:
  - Applicant name
  - Email
  - Application date
  - Status badge
  - Actions (View Details)
- Pagination: 20 per page
- Search/filter (future enhancement)

**Stat Cards:**
```
┌─────────────────────────┐  ┌─────────────────────────┐  ┌─────────────────────────┐
│   Pending Applications  │  │   Approved This Month   │  │     Total Instructors    │
│          {count}        │  │         {count}         │  │         {count}          │
└─────────────────────────┘  └─────────────────────────┘  └─────────────────────────┘
```

---

### Show Page: `/admin/instructor-applications/{id}`

**Layout:**
```
┌─────────────────────────────────────────────────────────────────────┐
│  APPLICANT INFORMATION                                               │
│  Name: {user.name}                                                   │
│  Email: {user.email}                                                 │
│  Date of Birth: {learnerProfile.date_of_birth}                      │
│  Location: {learnerProfile.city}, {learnerProfile.province}         │
│  Applied on: {application.created_at}                                │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  BIO / CV                                                            │
│  {application.bio}                                                   │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  TIER 1 DOCUMENTS (Required)                                         │
│  ✓ Government ID       [View PDF/Image] [Download]                  │
│  ✓ NBI/Police Clearance [View PDF/Image] [Download]                 │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  TIER 2 DOCUMENTS (At least one required)                            │
│  {if teaching_credential exists}                                      │
│    ✓ Teaching Credential [View PDF/Image] [Download]                │
│  {if sexed_certificate exists}                                       │
│    ✓ Sex Ed Training Certificate [View PDF/Image] [Download]       │
│  {if professional_license exists}                                    │
│    ✓ Professional License [View PDF/Image] [Download]               │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  LEARNER DATA SNAPSHOT                                               │
│  As a learner, this user has:                                        │
│  • {enrolled_modules_count} enrolled modules                         │
│  • {certificates_earned} certificates earned                         │
│  • Level {gamification.level} ({gamification.score} XP)             │
│  • Subscription: {subscription.status}                               │
│                                                                       │
│  ⚠ Role change will transition them to instructor (lose learner     │
│  access). All data will be preserved but cannot continue learning.  │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  ACTIONS                                                             │
│  {if status = pending}                                               │
│    [Approve Application] [Reject with Reason]                       │
│  {if status = approved}                                              │
│    Approved by {approvedBy.name} on {approved_at}                   │
│  {if status = rejected}                                              │
│    Rejected by {approvedBy.name} on {approved_at}                   │
│    Reason: {rejection_reason}                                        │
└─────────────────────────────────────────────────────────────────────┘
```

**Document Preview:**
- PDFs: Embed using `<iframe>` or PDF.js viewer
- Images: Lightbox modal with zoom capability
- Download: Direct storage URL (admin auth check)

---

### Approve Modal
```
┌─────────────────────────────────────────────────────────────────────┐
│  Approve Instructor Application                                      │
│                                                                       │
│  Are you sure you want to approve {user.name}'s application?        │
│                                                                       │
│  This action will:                                                   │
│  • Change user role from 'learner' to 'instructor'                  │
│  • Grant access to instructor panel                                  │
│  • Remove learner dashboard access                                   │
│  • Preserve all learner data (read-only)                            │
│  • Send approval notification email                                  │
│                                                                       │
│  [Cancel]  [Confirm Approval]                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

### Reject Modal
```
┌─────────────────────────────────────────────────────────────────────┐
│  Reject Instructor Application                                       │
│                                                                       │
│  Reason for rejection: (required, min 10 characters)                │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │ [Textarea: Enter detailed reason why application is rejected] │ │
│  │                                                                │ │
│  │ This helps the applicant understand what to improve.          │ │
│  └───────────────────────────────────────────────────────────────┘ │
│                                                                       │
│  Common reasons:                                                     │
│  • Incomplete or unclear documentation                               │
│  • Background clearance expired                                      │
│  • Insufficient teaching/health experience                           │
│  • Document quality issues (illegible)                               │
│                                                                       │
│  [Cancel]  [Submit Rejection]                                       │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Learner Application Interface

### Entry Point: Learner Dashboard

**Conditions to Show CTA:**
- User role = 'learner'
- No approved application exists
- No pending application exists

**CTA Card Design:**
```
┌─────────────────────────────────────────────────────────────────────┐
│  👨‍🏫 Become an Instructor                                            │
│                                                                       │
│  Share your expertise! Apply to become a verified instructor and     │
│  create your own sexual health education modules.                    │
│                                                                       │
│  Requirements:                                                       │
│  • Government-issued ID                                              │
│  • NBI or Police Clearance                                           │
│  • Professional CV/Bio                                               │
│  • Teaching credentials OR sex ed training OR professional license   │
│                                                                       │
│  [Apply Now] →                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

**Pending Application Status:**
```
┌─────────────────────────────────────────────────────────────────────┐
│  ⏳ Instructor Application Pending                                   │
│                                                                       │
│  Your application is under review. We'll notify you once an admin    │
│  has reviewed your submission.                                       │
│                                                                       │
│  Submitted on: {application.created_at}                              │
└─────────────────────────────────────────────────────────────────────┘
```

---

### Application Form: `/learn/instructor/apply`

**Page Structure:**
```
┌─────────────────────────────────────────────────────────────────────┐
│  Apply to Become an Instructor                                       │
│                                                                       │
│  Help us verify your qualifications to teach sexual health education.│
│  All documents will be reviewed by our admin team.                   │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  TIER 1: Required Documents (All 3 needed)                           │
│                                                                       │
│  Government-issued ID *                                              │
│  [Choose File] (Max 5MB, PDF/JPG/PNG)                              │
│  Accepted: Philippine driver's license, passport, national ID, etc.  │
│                                                                       │
│  NBI or Police Clearance *                                           │
│  [Choose File] (Max 5MB, PDF/JPG/PNG)                              │
│  Must be issued within last 6 months.                                │
│                                                                       │
│  Professional Background (CV/Bio) *                                  │
│  ┌───────────────────────────────────────────────────────────────┐ │
│  │ [Textarea: 100-5000 characters]                                │ │
│  │ Describe your relevant experience in education, health,        │ │
│  │ counseling, or related fields.                                 │ │
│  └───────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  TIER 2: Proof of Expertise (At least ONE required)                  │
│                                                                       │
│  □ Teaching Credential                                               │
│    [Choose File] (Max 5MB, PDF/JPG/PNG)                            │
│    LET, teaching certificate, or relevant degree (Education, Health, │
│    Psychology, Nursing, Social Work)                                 │
│                                                                       │
│  □ Sex Education Training Certificate                                │
│    [Choose File] (Max 5MB, PDF/JPG/PNG)                            │
│    Training from UNFPA, Likhaan, PNGOC, DOH, DepEd, or accredited  │
│    organizations.                                                    │
│                                                                       │
│  □ Professional License                                              │
│    [Choose File] (Max 5MB, PDF/JPG/PNG)                            │
│    PRC license: Nurse, Registered Counselor, Social Worker, etc.    │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  ☑ I confirm that all documents submitted are authentic and belong   │
│     to me. I understand that false information may result in         │
│     permanent account suspension.                                    │
│                                                                       │
│  [Cancel]  [Submit Application]                                     │
└─────────────────────────────────────────────────────────────────────┘
```

**Validation Rules:**
- All Tier 1 fields required
- At least one Tier 2 file required (client-side + server-side validation)
- Bio: 100-5000 characters
- Files: PDF, JPG, JPEG, PNG only, max 5MB each
- User must be learner role
- User must not have pending application

---

### Confirmation Page

After submission:
```
┌─────────────────────────────────────────────────────────────────────┐
│  ✅ Application Submitted Successfully!                              │
│                                                                       │
│  Thank you for applying to become an instructor. Our admin team      │
│  will review your application and documents within 3-5 business days.│
│                                                                       │
│  You'll receive an email notification with the decision. You can     │
│  check your application status on your dashboard.                    │
│                                                                       │
│  [Return to Dashboard]                                               │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Notifications

### 1. InstructorApplicationSubmitted (to Admin)

**Channels:** Database, Email (optional)

**Content:**
```
Title: New Instructor Application from {user.name}
Message: A learner has submitted an instructor application. Review it in the admin panel.
URL: /admin/instructor-applications/{application.id}
```

---

### 2. InstructorApplicationApproved (to Applicant)

**Channels:** Database, Email

**Email Subject:** Your Instructor Application Was Approved! 🎉

**Email Body:**
```
Congratulations, {user.name}!

Your application to become an instructor on Conscious Connections has been approved.

You now have access to the instructor panel where you can:
• Create educational modules and lessons
• Design quizzes and assessments
• Manage learner enrollments
• Track your teaching impact

Access your instructor dashboard:
{url}

Note: Your role has been upgraded to instructor. You will no longer have access to the learner dashboard, but all your learning progress and certificates have been preserved.

Welcome to the instructor community!

— Conscious Connections Team
```

---

### 3. InstructorApplicationRejected (to Applicant)

**Channels:** Database, Email

**Email Subject:** Update on Your Instructor Application

**Email Body:**
```
Hi {user.name},

Thank you for your interest in becoming an instructor on Conscious Connections.

After careful review, we've decided not to approve your application at this time.

Reason:
{rejection_reason}

You're welcome to reapply after addressing the feedback above. We encourage applicants to improve their credentials and resubmit.

If you have questions, please contact support@consciousconnections.ph

— Conscious Connections Team
```

---

## Edge Cases & Data Handling

### 1. User Has Active Subscription

**Scenario:** Learner with premium subscription becomes instructor

**Solution:**
- Cancel subscription upon approval
- Update `subscriptions.status = 'cancelled'`
- Add cancel reason: "Role changed to instructor"
- Optional: Offer prorated refund
- Optional: Create instructor subscription tier (future enhancement)

**Implementation:**
```php
if ($user->subscription && $user->subscription->status === 'active') {
    app(SubscriptionService::class)->cancel(
        $user->subscription,
        'Role changed to instructor'
    );
}
```

---

### 2. User Has Enrolled Modules with Incomplete Progress

**Scenario:** Learner with 5 enrolled modules (2 completed, 3 in progress) becomes instructor

**Solution:**
- **Preserve all enrollments:** No deletion
- **Preserve all progress:** UserProgress, LessonProgress, LessonTopicProgress tables unchanged
- **Block future progress updates:** Middleware checks prevent instructors from continuing learner activities
- **Read-only access:** Instructor can view old enrollments but not continue them (future enhancement: instructor profile page shows "Previously enrolled as learner")

**No Code Changes Needed:**
- Enrollment records stay in `module_enrollments` table
- Progress records stay in `user_progress`, `lesson_progress`, `lesson_topic_progress` tables
- Instructor role middleware blocks access to learner routes (`/learn/*`)

---

### 3. User Has Parent-Child Relationship

**Scenario:** Parent account (monitoring child) applies to become instructor

**Considerations:**
- Should parents be allowed to become instructors while monitoring children?
- Security concern: Parent could create inappropriate content

**Recommended Solution:**
- **Block application:** Add validation check
- Display error: "Parent accounts cannot apply to become instructors. Please create a separate account."

**Implementation:**
```php
// In SubmitInstructorApplicationRequest
public function authorize(): bool
{
    // Check if user is a parent
    if (auth()->user()->childAccounts()->exists()) {
        return false;
    }
    return true;
}
```

**Alternative:** Allow but require separate approval step

---

### 4. User Submits Multiple Applications

**Scenario:** User submits application, gets rejected, tries to submit again immediately

**Solution:**
- **One pending application at a time:** Validate on submission
- **Allow reapplication after rejection:** No cooldown (feedback should guide improvement)
- **Track application history:** All applications preserved via SoftDeletes

**Implementation:**
```php
// In SubmitInstructorApplicationRequest
public function authorize(): bool
{
    // Check for pending application
    $pendingExists = auth()->user()->instructorApplications()
        ->where('status', 'pending')
        ->exists();

    if ($pendingExists) {
        session()->flash('error', 'You already have a pending application.');
        return false;
    }

    return true;
}
```

---

### 5. Application Documents Deleted from Storage

**Scenario:** Admin deletes files or storage corruption

**Prevention:**
- Use SoftDeletes on InstructorApplication model
- Never hard-delete approved applications
- Implement backup strategy for `storage/app/public/instructor-applications/`

**Recovery:**
- Approved applications: Keep documents indefinitely (compliance requirement)
- Rejected applications: Archive after 1 year (scheduled job)

**Future Enhancement:** Store documents in S3 or cloud storage with versioning

---

## Implementation Phases

### Phase 1: Database Foundation (1 day)

**Priority: CRITICAL** - Everything depends on this

**Tasks:**
1. Create migration: `add_instructor_to_users_role_enum.php`
2. Create migration: `create_instructor_applications_table.php`
3. Create migration: `create_instructor_profiles_table.php`
4. Create migration: `create_role_transitions_table.php`
5. Run migrations on development environment
6. Test rollback

**Files:**
- `database/migrations/YYYY_MM_DD_HHMMSS_add_instructor_to_users_role_enum.php`
- 3 more migration files

**Acceptance Criteria:**
- [ ] Migrations run successfully
- [ ] `users.role` enum includes 'instructor'
- [ ] All new tables created with correct schema
- [ ] Foreign key constraints work

---

### Phase 2: Models & Relationships (0.5 day)

**Tasks:**
1. Create `InstructorApplication` model (copy Clinic pattern)
2. Create `InstructorProfile` model
3. Create `RoleTransition` model
4. Update `User` model:
   - Add `isInstructor()` method
   - Add `instructorApplication()` relationship
   - Add `instructorProfile()` relationship
   - Add `roleTransitions()` relationship

**Files:**
- `app/Models/InstructorApplication.php`
- `app/Models/InstructorProfile.php`
- `app/Models/RoleTransition.php`
- `app/Models/User.php` (update)

**Acceptance Criteria:**
- [ ] Models created with fillable fields, casts, relationships
- [ ] Scopes work: `InstructorApplication::pending()->get()`
- [ ] User relationships work: `$user->instructorApplication`

---

### Phase 3: Service Layer (1 day)

**Tasks:**
1. Create `InstructorApplicationService`
2. Implement `submitApplication()` method (file uploads, store application)
3. Implement `approve()` method (transaction, role change, notifications)
4. Implement `reject()` method (update status, notifications)

**Files:**
- `app/Services/InstructorApplicationService.php`

**Acceptance Criteria:**
- [ ] `submitApplication()` uploads files, creates application record
- [ ] `approve()` changes role, assigns Spatie role, creates profile, sends notification
- [ ] `reject()` updates status, sends notification
- [ ] All operations are transactional (rollback on error)

---

### Phase 4: Validation & Requests (0.5 day)

**Tasks:**
1. Create `SubmitInstructorApplicationRequest`
   - Validate Tier 1 fields (required)
   - Validate Tier 2 fields (at least one)
   - File validation (size, type)
   - Bio validation (length)
2. Create `RejectInstructorApplicationRequest`
   - Validate rejection_reason (required, min length)

**Files:**
- `app/Http/Requests/SubmitInstructorApplicationRequest.php`
- `app/Http/Requests/RejectInstructorApplicationRequest.php`

**Acceptance Criteria:**
- [ ] Form validation works (client-side + server-side)
- [ ] Custom validation: at least one Tier 2 document
- [ ] Error messages are clear and helpful

---

### Phase 5: Controllers (1 day)

**Tasks:**
1. Create `Learner\InstructorApplicationController`
   - `showForm()` - Display application form
   - `submit()` - Handle submission, call service
2. Create `Admin\InstructorApplicationController`
   - `index()` - List applications with tabs
   - `show()` - View application details
   - `approve()` - Approve application
   - `reject()` - Reject application
3. Update `DashboardController`
   - Add 'instructor' case to match statement (line 29)
   - Update `learnerDashboard()` - Show "Apply" CTA if eligible
   - Update `adminDashboard()` - Show pending applications count

**Files:**
- `app/Http/Controllers/Learner/InstructorApplicationController.php`
- `app/Http/Controllers/Admin/InstructorApplicationController.php`
- `app/Http/Controllers/DashboardController.php` (update)

**Acceptance Criteria:**
- [ ] Learners can access `/learn/instructor/apply`
- [ ] Application submission works
- [ ] Admins can view applications at `/admin/instructor-applications`
- [ ] Approve/reject buttons work

---

### Phase 6: Routes (0.5 day)

**Tasks:**
1. Add learner routes to `routes/web.php`
2. Add admin routes to `routes/admin.php`
3. Update `routes/auth.php`:
   - Deprecate instructor login (redirect to `/login`)
   - Deprecate instructor logout (redirect to `/logout`)

**Files:**
- `routes/web.php`
- `routes/admin.php`
- `routes/auth.php`

**Acceptance Criteria:**
- [ ] Routes registered correctly
- [ ] Middleware applied (role checks, auth checks)
- [ ] `/instructor/login` redirects to `/login`

---

### Phase 7: Views - Learner Side (1 day)

**Tasks:**
1. Create application form view
2. Create confirmation view
3. Update learner dashboard (add CTA card)

**Files:**
- `resources/views/learner/instructor-application/form.blade.php`
- `resources/views/learner/instructor-application/submitted.blade.php`
- `resources/views/dashboards/learner.blade.php` (update)
- Also update: `dashboards/kids.blade.php`, `dashboards/teens.blade.php`, `dashboards/adults.blade.php`

**Acceptance Criteria:**
- [ ] Form renders with all fields
- [ ] File upload works
- [ ] Validation errors display correctly
- [ ] CTA card shows on learner dashboard

---

### Phase 8: Views - Admin Side (1.5 days)

**Tasks:**
1. Create applications index view (with tabs)
2. Create application show view (details + documents)
3. Create approve modal
4. Create reject modal
5. Update admin dashboard (add stat card + link)

**Files:**
- `resources/views/admin/instructor-applications/index.blade.php`
- `resources/views/admin/instructor-applications/show.blade.php`
- `resources/views/admin/instructor-applications/_approve-modal.blade.php`
- `resources/views/admin/instructor-applications/_reject-modal.blade.php`
- `resources/views/dashboards/admin.blade.php` (update)

**Acceptance Criteria:**
- [ ] Index page shows applications with tabs
- [ ] Show page displays all application details
- [ ] Document preview works (PDF/image)
- [ ] Approve/reject modals work
- [ ] Admin dashboard links to applications

---

### Phase 9: Notifications (0.5 day)

**Tasks:**
1. Create notification: `InstructorApplicationSubmitted`
2. Create notification: `InstructorApplicationApproved`
3. Create notification: `InstructorApplicationRejected`

**Files:**
- `app/Notifications/InstructorApplicationSubmitted.php`
- `app/Notifications/InstructorApplicationApproved.php`
- `app/Notifications/InstructorApplicationRejected.php`

**Acceptance Criteria:**
- [ ] Admin receives notification when application submitted
- [ ] Applicant receives notification on approval
- [ ] Applicant receives notification on rejection
- [ ] Email notifications send correctly
- [ ] Database notifications appear in notification dropdown

---

### Phase 10: Authentication Updates (0.5 day)

**Tasks:**
1. Update `AuthenticatedSessionController` (add enum check)
2. Test unified login flow
3. Update instructor layout (change logout route)

**Files:**
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `resources/views/layouts/instructor-app.blade.php`

**Acceptance Criteria:**
- [ ] Instructors can log in at `/login`
- [ ] Instructors redirect to instructor dashboard
- [ ] Learners redirect to learner dashboard
- [ ] `/instructor/login` redirects to `/login`

---

### Phase 11: Testing (2 days)

**Tasks:**
1. Write feature test: `InstructorApplicationTest`
2. Write unit test: `InstructorApplicationServiceTest`
3. Write unit test: `RoleTransitionTest`
4. Manual testing (full flow)

**Files:**
- `tests/Feature/InstructorApplicationTest.php`
- `tests/Unit/InstructorApplicationServiceTest.php`
- `tests/Unit/RoleTransitionTest.php`

**Test Coverage:**
- [ ] Learner can submit application
- [ ] Validation works (Tier 1 + Tier 2)
- [ ] Admin can approve application
- [ ] Role transition works correctly
- [ ] Admin can reject application
- [ ] Notifications sent correctly
- [ ] Edge cases handled (subscription cancel, multiple applications, etc.)

---

### Phase 12: Documentation & Deployment (0.5 day)

**Tasks:**
1. Update README (mention instructor application feature)
2. Create admin guide (how to review applications)
3. Run migrations on staging
4. Test on staging
5. Deploy to production

**Acceptance Criteria:**
- [ ] Documentation updated
- [ ] Staging environment tested
- [ ] Production migration successful
- [ ] Feature works in production

---

## Total Estimated Timeline

**Development:** 10 days (80 hours)
**Testing:** 2 days (16 hours)
**Documentation & Deployment:** 0.5 days (4 hours)

**Total:** ~12.5 days (100 hours)

---

## Success Metrics

### Quantitative
- Number of applications submitted per month
- Approval rate (target: 70-80%)
- Average review time (target: < 3 business days)
- Number of active instructors
- Instructor retention rate (still creating content after 3 months)

### Qualitative
- **Learner feedback:** Is the application process clear?
- **Admin feedback:** Is the review process efficient?
- **Instructor feedback:** Was the transition smooth?

---

## Future Enhancements (Out of Scope)

These features are **not included** in this design but can be built later:

1. **Instructor Onboarding Wizard:** Multi-step wizard after approval (set bio, specialization, create first module)
2. **Instructor Verification Levels:** Basic → Verified → Expert tiers (badges)
3. **Document Expiry Tracking:** NBI clearance expires every 6-12 months (auto-renew notifications)
4. **Auto-rejection:** Pending applications auto-reject after 30 days of admin inactivity
5. **Reapplication Cooldown:** 7-day wait after rejection to prevent spam
6. **Instructor Analytics Dashboard:** Track applications, approval rate, popular documents
7. **Instructor Portfolio:** Public profile showing credentials, modules created, ratings
8. **Module Marketplace Integration:** Instructors sell modules to connectors (separate design needed per user's request)
9. **Connectors System Redesign:** Unified Organization model with type classification (separate design needed per user's request)
10. **Seminar Speaker Integration:** Connectors hire instructors for seminars (future enhancement)
11. **Instructor Subscription Plans:** Premium features for instructors (future enhancement per user's request)

---

## Conclusion

This design provides a complete, production-ready instructor application and lifecycle system that:

✅ **Solves the architectural inconsistency** by adding 'instructor' to the `users.role` enum
✅ **Creates an organic path** for learners to become instructors through vetted approval
✅ **Reuses existing patterns** (Clinic/Counselor workflows) for faster, safer implementation
✅ **Preserves data integrity** through careful role transition and audit trails
✅ **Unifies authentication** by centralizing learner + instructor login
✅ **Prepares for monetization** by establishing a credible instructor community

**Next Step:** Transition to implementation planning (invoke `writing-plans` skill to create detailed task breakdown).
