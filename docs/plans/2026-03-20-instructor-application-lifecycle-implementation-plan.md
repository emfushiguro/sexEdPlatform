# Instructor Application and Lifecycle System - Implementation Plan

**Design Reference:** `2026-03-20-instructor-application-lifecycle-design.md`
**Estimated Timeline:** 12.5 days (100 hours)
**Priority:** HIGH - Foundation for instructor sustainability features

---

## Task Breakdown

### Phase 1: Database Foundation (Day 1)

**Priority:** CRITICAL - All other work depends on this

#### Task 1.1: Create Enum Alteration Migration
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_add_instructor_to_users_role_enum.php`

**Steps:**
1. Generate migration: `php artisan make:migration add_instructor_to_users_role_enum`
2. Write `up()` method with ALTER TABLE statement
3. Write `down()` method (with warning comment about existing instructors)
4. Test on development database
5. Test rollback

**Acceptance Criteria:**
- [ ] Migration runs successfully
- [ ] `users.role` enum includes 'instructor'
- [ ] Rollback works (with manual data cleanup if needed)

---

#### Task 1.2: Create InstructorApplications Table Migration
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_instructor_applications_table.php`

**Steps:**
1. Generate migration: `php artisan make:migration create_instructor_applications_table`
2. Define schema:
   - `id`, `user_id` (FK), `status` (enum)
   - Tier 1 fields: `government_id_path`, `clearance_path`, `bio`
   - Tier 2 fields: `teaching_credential_path`, `sexed_certificate_path`, `professional_license_path` (nullable)
   - Approval fields: `approved_by` (FK), `approved_at`, `rejection_reason`
   - `application_metadata` (JSON)
   - `timestamps`, `softDeletes`
   - Indexes on `user_id`, `status`
3. Run migration
4. Verify table in database

**Acceptance Criteria:**
- [ ] Table created with all fields
- [ ] Foreign keys work
- [ ] Indexes created
- [ ] SoftDeletes trait works

---

#### Task 1.3: Create InstructorProfiles Table Migration
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_instructor_profiles_table.php`

**Steps:**
1. Generate migration: `php artisan make:migration create_instructor_profiles_table`
2. Define schema:
   - `id`, `user_id` (FK unique), `bio`, `specialization`, `credentials` (JSON)
   - `timestamps`
3. Run migration
4. Verify table in database

**Acceptance Criteria:**
- [ ] Table created
- [ ] Unique constraint on `user_id` works
- [ ] JSON field works

---

#### Task 1.4: Create RoleTransitions Table Migration
**File:** `database/migrations/YYYY_MM_DD_HHMMSS_create_role_transitions_table.php`

**Steps:**
1. Generate migration: `php artisan make:migration create_role_transitions_table`
2. Define schema:
   - `id`, `user_id` (FK), `from_role`, `to_role`
   - `approved_by` (FK), `reason`, `preserved_data` (JSON), `transitioned_at`
   - Index on `user_id`
3. Run migration
4. Verify table in database

**Acceptance Criteria:**
- [ ] Table created
- [ ] Foreign keys work
- [ ] JSON field works

---

#### Task 1.5: Run and Verify All Migrations
**Steps:**
1. Fresh migration: `php artisan migrate:fresh --seed`
2. Verify enum: Check `users.role` includes 'instructor'
3. Test Tinker:
   ```php
   User::create(['role' => 'instructor', ...]); // Should work
   ```

**Acceptance Criteria:**
- [ ] All 4 migrations run successfully
- [ ] Enum updated correctly
- [ ] Can create users with role='instructor'
- [ ] Database seeder still works

---

### Phase 2: Models & Relationships (Day 1.5)

#### Task 2.1: Create InstructorApplication Model
**File:** `app/Models/InstructorApplication.php`

**Steps:**
1. Generate model: `php artisan make:model InstructorApplication`
2. Copy pattern from `app/Models/Clinic.php`:
   - Define `$fillable`
   - Add `$casts` for `application_metadata`
   - Import `SoftDeletes` trait
3. Add scopes:
   ```php
   public function scopeApproved($query) { return $query->where('status', 'approved'); }
   public function scopePending($query) { return $query->where('status', 'pending'); }
   public function scopeRejected($query) { return $query->where('status', 'rejected'); }
   ```
4. Add relationships:
   ```php
   public function user() { return $this->belongsTo(User::class); }
   public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
   ```
5. Add helper methods:
   ```php
   public function isApproved(): bool { return $this->status === 'approved'; }
   public function isPending(): bool { return $this->status === 'pending'; }
   ```

**Acceptance Criteria:**
- [ ] Model created with all methods
- [ ] Scopes work: `InstructorApplication::pending()->get()`
- [ ] Relationships work: `$application->user`, `$application->approvedBy`

---

#### Task 2.2: Create InstructorProfile Model
**File:** `app/Models/InstructorProfile.php`

**Steps:**
1. Generate model: `php artisan make:model InstructorProfile`
2. Define `$fillable`: `['user_id', 'bio', 'specialization', 'credentials']`
3. Add `$casts`: `['credentials' => 'array']`
4. Add relationship:
   ```php
   public function user() { return $this->belongsTo(User::class); }
   ```

**Acceptance Criteria:**
- [ ] Model created
- [ ] Credentials cast to array works

---

#### Task 2.3: Create RoleTransition Model
**File:** `app/Models/RoleTransition.php`

**Steps:**
1. Generate model: `php artisan make:model RoleTransition`
2. Define `$fillable`: `['user_id', 'from_role', 'to_role', 'approved_by', 'reason', 'preserved_data', 'transitioned_at']`
3. Add `$casts`: `['preserved_data' => 'array', 'transitioned_at' => 'datetime']`
4. Add relationships:
   ```php
   public function user() { return $this->belongsTo(User::class); }
   public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
   ```

**Acceptance Criteria:**
- [ ] Model created
- [ ] Casts work correctly

---

#### Task 2.4: Update User Model
**File:** `app/Models/User.php`

**Steps:**
1. Add `isInstructor()` method:
   ```php
   public function isInstructor(): bool
   {
       return $this->role === 'instructor';
   }
   ```
2. Add relationships:
   ```php
   public function instructorApplication()
   {
       return $this->hasOne(InstructorApplication::class)->latest();
   }

   public function instructorApplications()
   {
       return $this->hasMany(InstructorApplication::class);
   }

   public function instructorProfile()
   {
       return $this->hasOne(InstructorProfile::class);
   }

   public function roleTransitions()
   {
       return $this->hasMany(RoleTransition::class);
   }
   ```

**Acceptance Criteria:**
- [ ] `isInstructor()` works
- [ ] Relationships work: `$user->instructorApplication`, `$user->instructorProfile`
- [ ] Can access: `$user->roleTransitions`

---

### Phase 3: Service Layer (Day 2-3)

#### Task 3.1: Create InstructorApplicationService
**File:** `app/Services/InstructorApplicationService.php`

**Steps:**
1. Create file in `app/Services/`
2. Add constructor (inject dependencies if needed)
3. Implement methods (outline below)

**Acceptance Criteria:**
- [ ] Service class created
- [ ] All methods implemented
- [ ] Methods use DB transactions where appropriate

---

#### Task 3.2: Implement submitApplication() Method

**Signature:**
```php
public function submitApplication(User $user, array $data): InstructorApplication
```

**Logic:**
1. Store uploaded files to `storage/app/public/instructor-applications/{user_id}/`
2. Create `InstructorApplication` record
3. Store metadata (original filenames, file sizes, submission IP)
4. Send notification to admin
5. Return application

**Acceptance Criteria:**
- [ ] Files uploaded to correct directory
- [ ] Application record created
- [ ] Admin notification sent
- [ ] Returns InstructorApplication instance

---

#### Task 3.3: Implement approve() Method

**Signature:**
```php
public function approve(InstructorApplication $application): void
```

**Logic (in DB transaction):**
1. Preserve learner state snapshot
2. Record role_transition
3. Update `user.role = 'instructor'`
4. Assign Spatie 'instructor' role
5. Create instructor_profile
6. Update application status to 'approved'
7. Send approval notification
8. Cancel active subscription (optional)

**Acceptance Criteria:**
- [ ] Role transition completes
- [ ] Instructor profile created
- [ ] Notification sent
- [ ] Transaction rolls back on error

---

#### Task 3.4: Implement reject() Method

**Signature:**
```php
public function reject(InstructorApplication $application, string $reason): void
```

**Logic:**
1. Update application status to 'rejected'
2. Store rejection_reason
3. Record approved_by and timestamp
4. Send rejection notification

**Acceptance Criteria:**
- [ ] Application status updated
- [ ] Notification sent
- [ ] User remains learner

---

### Phase 4: Validation & Requests (Day 3.5)

#### Task 4.1: Create SubmitInstructorApplicationRequest
**File:** `app/Http/Requests/SubmitInstructorApplicationRequest.php`

**Steps:**
1. Generate: `php artisan make:request SubmitInstructorApplicationRequest`
2. Implement `authorize()`:
   - Check user is learner
   - Check no pending application exists
   - Check user is not parent account
3. Implement `rules()`:
   ```php
   return [
       'government_id' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
       'clearance' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
       'bio' => 'required|string|min:100|max:5000',
       'teaching_credential' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
       'sexed_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
       'professional_license' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
   ];
   ```
4. Implement `withValidator()`:
   - Custom validation: at least one Tier 2 document required
5. Add helpful error messages

**Acceptance Criteria:**
- [ ] Validation rules work
- [ ] Custom Tier 2 validation works
- [ ] Authorization checks pass
- [ ] Error messages are clear

---

#### Task 4.2: Create RejectInstructorApplicationRequest
**File:** `app/Http/Requests/RejectInstructorApplicationRequest.php`

**Steps:**
1. Generate: `php artisan make:request RejectInstructorApplicationRequest`
2. Implement `authorize()`: Only admins can reject
3. Implement `rules()`:
   ```php
   return [
       'rejection_reason' => 'required|string|min:10|max:1000',
   ];
   ```

**Acceptance Criteria:**
- [ ] Validation works
- [ ] Only admins can reject

---

### Phase 5: Controllers (Day 4-5)

#### Task 5.1: Create Learner InstructorApplicationController
**File:** `app/Http/Controllers/Learner/InstructorApplicationController.php`

**Steps:**
1. Create file in `app/Http/Controllers/Learner/`
2. Inject `InstructorApplicationService` in constructor
3. Implement `showForm()`:
   - Check user is learner
   - Check no pending application exists
   - Return view with form
4. Implement `submit()`:
   - Validate with `SubmitInstructorApplicationRequest`
   - Call service->submitApplication()
   - Flash success message
   - Redirect to confirmation page

**Acceptance Criteria:**
- [ ] Form displays correctly
- [ ] Submission works
- [ ] Files upload successfully
- [ ] Redirects to confirmation

---

#### Task 5.2: Create Admin InstructorApplicationController
**File:** `app/Http/Controllers/Admin/InstructorApplicationController.php`

**Steps:**
1. Create file in `app/Http/Controllers/Admin/`
2. Inject `InstructorApplicationService` in constructor
3. Implement `index()`:
   - List applications with tabs (pending, approved, rejected)
   - Eager load user relationship
   - Paginate (20 per page)
4. Implement `show()`:
   - Display application details
   - Load user, approvedBy relationships
   - Calculate learner data snapshot
5. Implement `approve()`:
   - Call service->approve()
   - Flash success
   - Redirect back
6. Implement `reject()`:
   - Validate with `RejectInstructorApplicationRequest`
   - Call service->reject()
   - Flash success
   - Redirect back

**Acceptance Criteria:**
- [ ] Index page works with tabs
- [ ] Show page displays all details
- [ ] Approve action works
- [ ] Reject action works

---

#### Task 5.3: Update DashboardController
**File:** `app/Http/Controllers/DashboardController.php`

**Changes:**

1. **Line 29-35:** Add 'instructor' case
   ```php
   return match($user->role) {
       'admin' => $this->adminDashboard(),
       'instructor' => redirect()->route('instructor.dashboard'),
       'counselor' => $this->counselorDashboard(),
       'clinic' => $this->clinicDashboard(),
       'organization' => $this->organizationDashboard(),
       default => $this->learnerDashboard(),
   };
   ```

2. **adminDashboard() method:** Add pending instructors count
   ```php
   'pendingInstructors' => InstructorApplication::pending()->count(),
   ```

3. **learnerDashboard() method:** Add instructor application CTA logic
   - Check if user can apply (no pending/approved application)
   - Pass flag to view

**Acceptance Criteria:**
- [ ] Instructor role routes correctly
- [ ] Admin dashboard shows pending count
- [ ] Learner dashboard shows CTA when eligible

---

### Phase 6: Routes (Day 5.5)

#### Task 6.1: Add Learner Routes
**File:** `routes/web.php`

**Add:**
```php
Route::middleware(['auth', 'verified', 'profile.completed'])->prefix('learn')->name('learner.')->group(function() {
    // ... existing routes

    // Instructor application (learners only)
    Route::middleware('role:learner')->prefix('instructor')->name('instructor.')->group(function() {
        Route::get('apply', [Learner\InstructorApplicationController::class, 'showForm'])->name('apply');
        Route::post('apply', [Learner\InstructorApplicationController::class, 'submit'])->name('apply.submit');
    });
});
```

**Acceptance Criteria:**
- [ ] Routes registered
- [ ] Middleware applied correctly
- [ ] Routes accessible by learners only

---

#### Task 6.2: Add Admin Routes
**File:** `routes/admin.php`

**Add:**
```php
Route::prefix('instructor-applications')->name('instructor-applications.')->group(function () {
    Route::get('/', [Admin\InstructorApplicationController::class, 'index'])->name('index');
    Route::get('/{application}', [Admin\InstructorApplicationController::class, 'show'])->name('show');
    Route::post('/{application}/approve', [Admin\InstructorApplicationController::class, 'approve'])->name('approve');
    Route::post('/{application}/reject', [Admin\InstructorApplicationController::class, 'reject'])->name('reject');
});
```

**Acceptance Criteria:**
- [ ] Routes registered
- [ ] Route model binding works
- [ ] Admin middleware applied

---

#### Task 6.3: Deprecate Instructor Login Routes
**File:** `routes/auth.php`

**Update line 53-58:**
```php
Route::get('instructor/login', function() {
    return redirect()->route('login')
        ->with('info', 'Please use the main login page. Instructors and learners now share one login.');
})->name('instructor.login');

Route::post('instructor/login', function() {
    return redirect()->route('login');
});
```

**Acceptance Criteria:**
- [ ] `/instructor/login` redirects to `/login`
- [ ] Info message displays
- [ ] POST route also redirects

---

### Phase 7: Views - Learner Side (Day 6)

#### Task 7.1: Create Application Form View
**File:** `resources/views/learner/instructor-application/form.blade.php`

**Requirements:**
- Extend `layouts.learner-app`
- Form with all Tier 1 + Tier 2 fields
- File upload inputs (with max size warning)
- Bio textarea with character counter
- Checkbox confirmation
- Validation error display
- Submit button

**Acceptance Criteria:**
- [ ] Form renders correctly
- [ ] File uploads work
- [ ] Validation errors display
- [ ] Character counter works for bio

---

#### Task 7.2: Create Confirmation View
**File:** `resources/views/learner/instructor-application/submitted.blade.php`

**Requirements:**
- Success message
- "What happens next" section
- Link back to dashboard

**Acceptance Criteria:**
- [ ] View displays after submission
- [ ] Links work correctly

---

#### Task 7.3: Update Learner Dashboard Views
**Files:**
- `resources/views/dashboards/learner.blade.php`
- `resources/views/dashboards/kids.blade.php`
- `resources/views/dashboards/teens.blade.php`
- `resources/views/dashboards/adults.blade.php`

**Changes:**
- Add "Become an Instructor" CTA card (conditional: if eligible)
- Add "Application Pending" status card (if pending application exists)

**Acceptance Criteria:**
- [ ] CTA shows when user is eligible
- [ ] Pending status shows when application is pending
- [ ] Works across all age-specific dashboards

---

### Phase 8: Views - Admin Side (Day 7-8)

#### Task 8.1: Create Applications Index View
**File:** `resources/views/admin/instructor-applications/index.blade.php`

**Requirements:**
- Extend `layouts.admin`
- Stat cards (pending, approved, rejected counts)
- Tabbed interface (Pending | Approved | Rejected)
- Table with columns: Name, Email, Date, Status, Actions
- Pagination
- Link to show page

**Acceptance Criteria:**
- [ ] Tabs work (filter by status)
- [ ] Table displays correctly
- [ ] Pagination works
- [ ] Links to show page work

---

#### Task 8.2: Create Application Show View
**File:** `resources/views/admin/instructor-applications/show.blade.php`

**Requirements:**
- Applicant information section
- Bio/CV section
- Tier 1 documents section (with view/download links)
- Tier 2 documents section (with view/download links)
- Learner data snapshot section
- Actions section (Approve/Reject buttons)
- Document preview modals (PDF/image viewer)

**Acceptance Criteria:**
- [ ] All sections display correctly
- [ ] Document preview works
- [ ] Download links work
- [ ] Approve/Reject buttons trigger modals

---

#### Task 8.3: Create Approve Modal
**File:** `resources/views/admin/instructor-applications/_approve-modal.blade.php`

**Requirements:**
- Alpine.js modal component
- Confirmation message
- List of actions that will happen
- Cancel and Confirm buttons
- Form submission to approve route

**Acceptance Criteria:**
- [ ] Modal opens on button click
- [ ] Form submits correctly
- [ ] Cancel closes modal

---

#### Task 8.4: Create Reject Modal
**File:** `resources/views/admin/instructor-applications/_reject-modal.blade.php`

**Requirements:**
- Alpine.js modal component
- Rejection reason textarea (required, min 10 chars)
- Character counter
- Common reasons list (for reference)
- Cancel and Submit buttons
- Form submission to reject route

**Acceptance Criteria:**
- [ ] Modal opens on button click
- [ ] Validation works (client + server)
- [ ] Form submits correctly
- [ ] Character counter works

---

#### Task 8.5: Update Admin Dashboard View
**File:** `resources/views/dashboards/admin.blade.php`

**Changes:**
- Add pending instructor applications stat card
- Add link to instructor applications page

**Acceptance Criteria:**
- [ ] Stat card displays
- [ ] Count is accurate
- [ ] Link works

---

### Phase 9: Notifications (Day 8.5)

#### Task 9.1: Create InstructorApplicationSubmitted Notification
**File:** `app/Notifications/InstructorApplicationSubmitted.php`

**Steps:**
1. Generate: `php artisan make:notification InstructorApplicationSubmitted`
2. Implement `via()`: `['database', 'mail']` (optional email)
3. Implement `toArray()`:
   ```php
   return [
       'type' => 'instructor_application_submitted',
       'title' => 'New Instructor Application',
       'message' => "A learner has submitted an instructor application.",
       'url' => route('admin.instructor-applications.show', $this->application),
   ];
   ```
4. Implement `toMail()` (optional)

**Acceptance Criteria:**
- [ ] Notification sends to admins
- [ ] Database notification created
- [ ] Email sends (if enabled)
- [ ] Link works correctly

---

#### Task 9.2: Create InstructorApplicationApproved Notification
**File:** `app/Notifications/InstructorApplicationApproved.php`

**Steps:**
1. Generate notification
2. Implement `via()`: `['database', 'mail']`
3. Implement `toArray()`:
   ```php
   return [
       'type' => 'instructor_application_approved',
       'title' => 'Your instructor application was approved! 🎉',
       'message' => 'You now have access to the instructor panel.',
       'url' => route('instructor.dashboard'),
   ];
   ```
4. Implement `toMail()`:
   - Subject: "Your Instructor Application Was Approved! 🎉"
   - Body: Welcome message, next steps, link to instructor dashboard

**Acceptance Criteria:**
- [ ] Notification sends to applicant
- [ ] Database notification created
- [ ] Email formatted correctly
- [ ] Links work

---

#### Task 9.3: Create InstructorApplicationRejected Notification
**File:** `app/Notifications/InstructorApplicationRejected.php`

**Steps:**
1. Generate notification
2. Implement `via()`: `['database', 'mail']`
3. Implement `toArray()`:
   ```php
   return [
       'type' => 'instructor_application_rejected',
       'title' => 'Update on Your Instructor Application',
       'message' => "Your application was not approved. You can reapply after addressing the feedback.",
       'url' => route('learner.dashboard'),
   ];
   ```
4. Implement `toMail()`:
   - Subject: "Update on Your Instructor Application"
   - Body: Include rejection reason, encourage reapplication

**Acceptance Criteria:**
- [ ] Notification sends to applicant
- [ ] Rejection reason included
- [ ] Email tone is constructive

---

### Phase 10: Authentication Updates (Day 9)

#### Task 10.1: Update AuthenticatedSessionController
**File:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

**Changes (around line 48-51):**
```php
// Add enum check for belt-and-suspenders approach
if ($user->role === 'instructor' || $user->hasRole('instructor')) {
    return redirect()->intended(route('instructor.dashboard'))
        ->with('success', "Welcome back, {$userName}!");
}
```

**Acceptance Criteria:**
- [ ] Instructors redirect to instructor dashboard
- [ ] Learners redirect to learner dashboard
- [ ] Enum check works

---

#### Task 10.2: Update Instructor Layout Logout Route
**File:** `resources/views/layouts/instructor-app.blade.php`

**Changes:**
- Find logout form/link
- Update from `route('instructor.logout')` to `route('logout')`

**Acceptance Criteria:**
- [ ] Logout link works for instructors
- [ ] Redirects to login page after logout

---

#### Task 10.3: Test Unified Login Flow
**Manual Testing:**
1. Create test learner account
2. Apply for instructor
3. Approve application as admin
4. Log out
5. Log in at `/login` (not `/instructor/login`)
6. Verify redirect to instructor dashboard

**Acceptance Criteria:**
- [ ] Instructors can log in at `/login`
- [ ] Redirect to correct dashboard
- [ ] `/instructor/login` redirects to `/login`

---

### Phase 11: Testing (Day 10-11)

#### Task 11.1: Write Feature Test - Application Submission
**File:** `tests/Feature/InstructorApplicationSubmissionTest.php`

**Test Cases:**
1. `test_learner_can_view_application_form()`
2. `test_learner_can_submit_valid_application()`
3. `test_application_requires_tier1_documents()`
4. `test_application_requires_at_least_one_tier2_document()`
5. `test_files_are_uploaded_to_correct_directory()`
6. `test_learner_cannot_submit_multiple_pending_applications()`
7. `test_parent_accounts_cannot_apply()`
8. `test_admin_receives_notification_on_submission()`

**Acceptance Criteria:**
- [ ] All test cases pass
- [ ] Code coverage > 80%

---

#### Task 11.2: Write Feature Test - Admin Approval
**File:** `tests/Feature/InstructorApplicationApprovalTest.php`

**Test Cases:**
1. `test_admin_can_view_applications_list()`
2. `test_admin_can_view_application_details()`
3. `test_admin_can_approve_application()`
4. `test_approval_changes_user_role_to_instructor()`
5. `test_approval_creates_instructor_profile()`
6. `test_approval_records_role_transition()`
7. `test_approval_sends_notification_to_applicant()`
8. `test_approval_cancels_active_subscription()`
9. `test_admin_can_reject_application()`
10. `test_rejection_requires_reason()`
11. `test_rejection_sends_notification_to_applicant()`

**Acceptance Criteria:**
- [ ] All test cases pass
- [ ] Database transactions work correctly

---

#### Task 11.3: Write Unit Test - InstructorApplicationService
**File:** `tests/Unit/InstructorApplicationServiceTest.php`

**Test Cases:**
1. `test_submit_application_stores_files()`
2. `test_approve_method_changes_role()`
3. `test_approve_method_creates_profile()`
4. `test_approve_method_records_transition()`
5. `test_reject_method_updates_status()`

**Acceptance Criteria:**
- [ ] All test cases pass
- [ ] Mocks used appropriately

---

#### Task 11.4: Write Integration Test - Complete Lifecycle
**File:** `tests/Feature/InstructorLifecycleIntegrationTest.php`

**Test Case:**
`test_complete_instructor_lifecycle_from_learner_to_instructor()`

**Flow:**
1. Create learner account
2. Enroll in modules
3. Earn certificates
4. Have active subscription
5. Submit instructor application
6. Admin approves
7. Verify role change
8. Verify data preservation
9. Verify instructor dashboard access
10. Verify learner dashboard 403
11. Verify subscription canceled

**Acceptance Criteria:**
- [ ] End-to-end test passes
- [ ] All state changes verified

---

#### Task 11.5: Manual Testing Checklist
**Perform these tests in browser:**

**Learner Flow:**
- [ ] View application form
- [ ] Submit valid application
- [ ] See confirmation page
- [ ] See "pending" status on dashboard
- [ ] Cannot submit another application while pending

**Admin Flow:**
- [ ] View applications list
- [ ] See pending applications count on dashboard
- [ ] View application details
- [ ] Preview documents (PDF, images)
- [ ] Approve application
- [ ] See success message
- [ ] Verify application moved to "Approved" tab
- [ ] Reject application with reason
- [ ] See success message

**Post-Approval:**
- [ ] Approved user logs out
- [ ] Logs in at `/login`
- [ ] Redirects to instructor dashboard
- [ ] Cannot access learner dashboard
- [ ] Can create modules
- [ ] Old enrollments preserved in database

**Edge Cases:**
- [ ] Test with active subscription (verify cancellation)
- [ ] Test with parent account (verify blocked)
- [ ] Test file size validation (> 5MB fails)
- [ ] Test unsupported file types (fails)
- [ ] Test bio length (< 100 chars fails, > 5000 chars fails)

---

### Phase 12: Documentation & Deployment (Day 12)

#### Task 12.1: Update README
**File:** `README.md`

**Add section:**
- Instructor application feature
- Link to design doc
- Mention unified login

**Acceptance Criteria:**
- [ ] README updated
- [ ] Links work

---

#### Task 12.2: Create Admin User Guide
**File:** `docs/ADMIN_INSTRUCTOR_APPLICATION_GUIDE.md`

**Content:**
- How to review applications
- What to look for in documents
- When to approve vs reject
- Sample rejection reasons

**Acceptance Criteria:**
- [ ] Guide created
- [ ] Clear instructions
- [ ] Examples provided

---

#### Task 12.3: Staging Deployment
**Steps:**
1. Push code to staging branch
2. Run migrations: `php artisan migrate --force`
3. Verify enum update
4. Run seeders if needed
5. Test application flow
6. Test admin approval flow

**Acceptance Criteria:**
- [ ] Migrations successful
- [ ] No errors in logs
- [ ] Feature works end-to-end

---

#### Task 12.4: Production Deployment
**Steps:**
1. **Pre-deployment:**
   - Backup database: `mysqldump -u root -p sexedplatform > backup.sql`
   - Check for existing Spatie instructors: `User::role('instructor')->count()`
   - Schedule maintenance window

2. **Deployment:**
   - Pull latest code
   - Run migrations: `php artisan migrate --force`
   - Clear caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear`
   - Update existing instructors (if any): `User::role('instructor')->update(['role' => 'instructor']);`

3. **Post-deployment:**
   - Verify enum: Check `users.role` in database
   - Test login flow
   - Test application submission
   - Check logs for errors

**Acceptance Criteria:**
- [ ] Production deployment successful
- [ ] No downtime
- [ ] Feature works in production
- [ ] Existing instructors migrated

---

#### Task 12.5: Create Rollback Plan
**File:** `docs/INSTRUCTOR_APPLICATION_ROLLBACK_PLAN.md`

**Content:**
1. How to rollback migrations
2. How to restore database backup
3. How to revert code
4. What to do with existing applications

**Acceptance Criteria:**
- [ ] Rollback plan documented
- [ ] Tested on staging

---

## Summary

**Total Tasks:** 65 tasks across 12 phases
**Estimated Time:** 12.5 days (100 hours)
**Critical Path:** Phase 1 (Database) → Phase 2 (Models) → Phase 3 (Service) → All other phases can partially overlap

**Dependencies:**
- All phases depend on Phase 1 (Database Foundation)
- Phase 5 (Controllers) depends on Phase 2, 3, 4
- Phase 7-8 (Views) depends on Phase 5 (Controllers)
- Phase 11 (Testing) depends on Phase 1-10

**Risk Areas:**
- Enum migration (test thoroughly before production)
- File uploads (ensure storage symlink exists)
- Role transition (test with real learner data)
- Subscription cancellation (handle edge cases)

**Next Step:** Begin implementation with Phase 1 (Database Foundation).
