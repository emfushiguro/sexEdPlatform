# 2026-04-16 Admin Creator Profile Transparency

## Scope
Implemented the dedicated admin creator profile domain and integrated learner-facing ownership transparency for admin-owned modules.

## Delivered Changes
- Added admin creator profile persistence model:
  - `admin_creator_profiles` table migration
  - `AdminCreatorProfile` model
  - `User::adminCreatorProfile()` relationship
- Added admin profile update contract:
  - `UpdateAdminCreatorProfileRequest`
  - `AdminCreatorProfileService` for get/create/update orchestration
- Added ownership display normalization:
  - `AdminOwnershipDisplayService` for team-first owner rendering with optional individual attribution
- Extended admin profile flow:
  - Updated `AdminProfileController` with `edit` and `update`
  - Added `admin.profile.edit` and `admin.profile.update` routes
  - Enhanced admin profile `show` and added `edit` view
- Added learner-facing admin creator transparency surface:
  - `Learner\AdminCreatorProfileController@show`
  - `learner.admin-creators.show` route
  - `resources/views/learner/admin-creators/show.blade.php`
- Integrated module ownership UI behavior:
  - Added `resources/views/learner/modules/partials/admin-creator-info-card.blade.php`
  - Updated `learner.modules.show` to render admin/instructor info card by owner type
  - Updated module cards and module index to display normalized owner information and optional individual attribution text
- Added authorization coverage:
  - `AdminCreatorProfilePolicy`
  - Policy registration in `AppServiceProvider`
  - Policy enforcement in admin profile update action

## Test Verification
Focused suite:
- Command: targeted feature test run for admin creator profile + transparency files
- Result: `passed=17 failed=0`

Regression anchors:
- Command: targeted run for:
  - `AdminModuleAuthoringWorkflowTest`
  - `ModuleOverviewLayoutTest`
  - `LearnerInstructorBackgroundPageTest`
- Result: `passed=7 failed=0`

## Notes
- Planned anchors `LearnerModulePageTest` and `InstructorProfileControllerTest` were not present as test classes in this workspace. Closest relevant regression anchors above were used instead.
