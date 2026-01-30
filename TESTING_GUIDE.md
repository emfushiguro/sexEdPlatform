# Testing Guide: Age Bracket System

## Quick Start Testing

### 1. Create Test Users with Different Ages

Run these artisan commands in your terminal to create test users:

```bash
# Create a test Kid (8 years old)
php artisan tinker
$user = User::create(['first_name' => 'Kid', 'last_name' => 'Test', 'email' => 'kid@test.com', 'password' => bcrypt('password'), 'role' => 'learner']);
$user->learnerProfile()->create(['username' => 'kidtester', 'birthdate' => now()->subYears(8), 'city_code' => '042108', 'barangay_code' => '042108001']);
exit

# Create a test Teen (15 years old)
php artisan tinker
$user = User::create(['first_name' => 'Teen', 'last_name' => 'Test', 'email' => 'teen@test.com', 'password' => bcrypt('password'), 'role' => 'learner']);
$user->learnerProfile()->create(['username' => 'teentester', 'birthdate' => now()->subYears(15), 'city_code' => '042108', 'barangay_code' => '042108001']);
exit

# Create a test Adult (25 years old)
php artisan tinker
$user = User::create(['first_name' => 'Adult', 'last_name' => 'Test', 'email' => 'adult@test.com', 'password' => bcrypt('password'), 'role' => 'learner']);
$user->learnerProfile()->create(['username' => 'adulttester', 'birthdate' => now()->subYears(25), 'city_code' => '042108', 'barangay_code' => '042108001']);
exit
```

**Test Credentials**:
- Kid: `kid@test.com` / `password`
- Teen: `teen@test.com` / `password`
- Adult: `adult@test.com` / `password`

### 2. Create Test Modules with Age Restrictions

Login as admin and create these modules:

**Module 1: Growing Up Healthy (Kids Only)**
- Title: Growing Up Healthy
- Min Age: 5
- Max Age: 12
- Difficulty: Beginner
- Click "Kids (5-12 years)" preset button
- Check "Publish immediately"
- Save

**Module 2: Understanding Changes (Teens Only)**
- Title: Understanding Changes
- Min Age: 13
- Max Age: 17
- Difficulty: Intermediate
- Click "Teens (13-17 years)" preset button
- Check "Publish immediately"
- Save

**Module 3: Adult Health & Wellness (Adults Only)**
- Title: Adult Health & Wellness
- Min Age: 18
- Max Age: 100
- Difficulty: Advanced
- Click "Adults (18+ years)" preset button
- Check "Publish immediately"
- Save

**Module 4: Everyone's Module (All Ages)**
- Title: Basic Health Awareness
- Min Age: 5
- Max Age: 100
- Difficulty: Beginner
- Don't click any preset (or manually set 5-100)
- Check "Publish immediately"
- Save

### 3. Test Dashboard Display

**Login as Kid** (`kid@test.com` / `password`):
- ✅ Should see colorful, playful dashboard (yellow/pink/blue)
- ✅ Should see large emojis (📚 ⭐ 🎮)
- ✅ Should see "My Lessons", "My Level", "My Stars"
- ✅ Profile card should show username and age (8 years old)
- ✅ Click "Edit Profile" - should go to profile edit page

**Login as Teen** (`teen@test.com` / `password`):
- ✅ Should see modern purple/indigo dashboard
- ✅ Should see "My Modules", "My Level", "My XP"
- ✅ Should see XP progress bars
- ✅ Profile card should show username and age (15 years old)
- ✅ Click "Edit Profile" - should go to profile edit page

**Login as Adult** (`adult@test.com` / `password`):
- ✅ Should see professional gray/blue dashboard
- ✅ Should see "Enrolled Modules", "Level", "Points"
- ✅ Should see SVG icons (not emojis)
- ✅ Profile card should show username and age (25 years old)
- ✅ Click "Edit Profile" - should go to profile edit page

### 4. Test Module Access Control

**Login as Kid** (`kid@test.com`):
1. Go to "Explore Modules" (or `/learn/modules`)
2. ✅ Should see:
   - "Growing Up Healthy" (Kids module)
   - "Basic Health Awareness" (All ages module)
3. ❌ Should NOT see:
   - "Understanding Changes" (Teens only)
   - "Adult Health & Wellness" (Adults only)
4. Click on "Growing Up Healthy"
   - ✅ Should successfully view module
   - ✅ Should be able to enroll
5. Try accessing teens module via URL: `/learn/modules/2` (assuming ID 2 is teens module)
   - ❌ Should redirect with error: "This module is not available for your age group"

**Login as Teen** (`teen@test.com`):
1. Go to "Explore Modules"
2. ✅ Should see:
   - "Understanding Changes" (Teens module)
   - "Basic Health Awareness" (All ages module)
3. ❌ Should NOT see:
   - "Growing Up Healthy" (Kids only)
   - "Adult Health & Wellness" (Adults only)
4. Click on "Understanding Changes"
   - ✅ Should successfully view module
   - ✅ Should be able to enroll

**Login as Adult** (`adult@test.com`):
1. Go to "Explore Modules"
2. ✅ Should see:
   - "Adult Health & Wellness" (Adults module)
   - "Basic Health Awareness" (All ages module)
3. ❌ Should NOT see:
   - "Growing Up Healthy" (Kids only)
   - "Understanding Changes" (Teens only)
4. Click on "Adult Health & Wellness"
   - ✅ Should successfully view module
   - ✅ Should be able to enroll

### 5. Test Enrollment

**As any learner**:
1. Go to a module you can access
2. Click "Enroll Now" button
3. ✅ Should successfully enroll
4. ✅ Should see "✓ Enrolled" badge on module thumbnail
5. Try to enroll again
   - ✅ Should show message: "You are already enrolled in this module"

### 6. Test Admin Module Management

**Login as admin**:
1. Go to `/admin/modules`
2. ✅ Should see list of all modules with:
   - Title
   - Age range (e.g., "5-12 years")
   - Lesson count
   - Status (Published/Draft)
3. Click "Create New Module"
   - ✅ Test "Kids" preset button (should set min=5, max=12)
   - ✅ Test "Teens" preset button (should set min=13, max=17)
   - ✅ Test "Adults" preset button (should set min=18, max=100)
   - ✅ Manually adjust ages (e.g., 10-14 for tweens)
   - ✅ Upload thumbnail image
   - ✅ Set difficulty level
   - ✅ Save and verify it appears in list
4. Click "Edit" on a module
   - ✅ Should load with correct age values
   - ✅ Change age range (e.g., from Kids to Teens)
   - ✅ Save and verify changes
5. Click "Delete" on a test module
   - ✅ Should soft delete (not permanently removed)

## Common Issues & Solutions

### Issue: "Please complete your profile to access modules"
**Solution**: Make sure the test users have all required profile fields:
- username
- birthdate
- city_code
- barangay_code

### Issue: Module shows for wrong age group
**Solution**: Check the module's min_age and max_age in the database:
```bash
php artisan tinker
Module::find(1)->only(['title', 'min_age', 'max_age'])
```

### Issue: Dashboard not showing correct theme
**Solution**: 
1. Check user's age: `User::find(1)->learnerProfile->getAge()`
2. Check age bracket: `User::find(1)->learnerProfile->getAgeBracket()`
3. Clear cache: `php artisan cache:clear`

### Issue: "Undefined method 'getAge'"
**Solution**: This method exists in LearnerProfile model. Make sure:
1. The relationship is loaded: `$user->learnerProfile`
2. Profile exists: `$user->learnerProfile()->exists()`

## Database Queries for Testing

### Check user's age
```php
php artisan tinker
$user = User::where('email', 'kid@test.com')->first();
$user->learnerProfile->getAge(); // Should return 8
$user->learnerProfile->getAgeBracket(); // Should return 'kids'
```

### Check module age range
```php
php artisan tinker
Module::all()->map(fn($m) => [
    'title' => $m->title,
    'min_age' => $m->min_age,
    'max_age' => $m->max_age,
]);
```

### Check which modules a user can access
```php
php artisan tinker
$user = User::where('email', 'kid@test.com')->first();
$age = $user->learnerProfile->getAge();
Module::where('is_published', true)
    ->where('min_age', '<=', $age)
    ->where('max_age', '>=', $age)
    ->pluck('title');
```

## Testing Checklist

### Dashboard Tests
- [ ] Kid dashboard shows playful theme
- [ ] Teen dashboard shows modern purple theme
- [ ] Adult dashboard shows professional theme
- [ ] Profile cards display correctly on all dashboards
- [ ] Gamification stats show correct data
- [ ] Edit profile button works

### Module Access Tests
- [ ] Kids only see kids + all-ages modules
- [ ] Teens only see teens + all-ages modules
- [ ] Adults only see adults + all-ages modules
- [ ] Direct URL access to wrong-age module is blocked
- [ ] Enrollment in wrong-age module is blocked

### Admin Tests
- [ ] Can create module with age presets
- [ ] Can manually set custom age ranges
- [ ] Can edit module age ranges
- [ ] Can upload thumbnails
- [ ] Can publish/unpublish modules
- [ ] Module list shows correct age ranges

### Security Tests
- [ ] Unauthenticated users cannot access modules
- [ ] Unpublished modules don't show for learners
- [ ] Age verification works on enrollment
- [ ] Profile completion is enforced

## Expected Results Summary

| User Age | Can See Modules | Dashboard Theme |
|----------|----------------|-----------------|
| 5-12 (Kids) | Kids + All Ages | Colorful, playful, emojis |
| 13-17 (Teens) | Teens + All Ages | Purple, modern, XP bars |
| 18+ (Adults) | Adults + All Ages | Gray, professional, SVG icons |

## Next Steps After Testing

1. ✅ If all tests pass, system is ready for production
2. 📝 Document any issues found
3. 🎨 Hand off to UI/UX team for design refinement
4. 📚 Create actual lesson content for modules
5. 🧪 Add more comprehensive quiz questions

## Need Help?

Check these files for implementation details:
- Dashboard routing: `app/Http/Controllers/DashboardController.php`
- Module filtering: `app/Http/Controllers/Learner/ModuleController.php`
- Admin module CRUD: `app/Http/Controllers/Admin/ModuleController.php`
- Dashboard views: `resources/views/dashboards/{kids,teens,adults}.blade.php`
- Summary: `AGE_BRACKET_SYSTEM_SUMMARY.md`
