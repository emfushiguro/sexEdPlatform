# Instructor Profile Enhancement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enhance the Instructor Profile pages to align with the core visual theme (Dashboard/Bento UI), add avatar upload functionality, and utilize Alpine.js to manage complex JSON fields (`expertise_tags`, `credentials`, `certifications`).

**Architecture:** 
- Form Request validation is updated to accept an image upload (`profile_photo`) instead of a simple string path, along with validating the JSON array fields.
- The `ProfileController` will handle storing the image on the public disk and generating the `profile_photo_path`.
- The views (`edit.blade.php` and `show.blade.php`) are updated heavily using Tailwind CSS classes (`blue-600`, `rounded-xl`, `bg-blue-50`) to provide a polished bento-grid layout. Alpine.js is used to add/remove elements from array inputs dynamically without full page reloads.

**Tech Stack:** Laravel, Blade, Tailwind CSS, Alpine.js

---

### Task 1: Update the Validation Request and Controller

**Files:**
- Modify: `app/Http/Requests/Instructor/UpdateInstructorProfileRequest.php`
- Modify: `app/Http/Controllers/Instructor/ProfileController.php`
- Modify: `tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php`

**Step 1: Write the failing tests**

```php
// In tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
// Add this method:
public function test_instructor_can_update_avatar_and_array_fields(): void
{
    Storage::fake('public');
    
    $instructor = User::factory()->create(['role' => 'instructor']);
    $instructor->assignRole('instructor');
    
    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->actingAs($instructor)
        ->put(route('instructor.profile.update'), [
            'bio' => 'New bio',
            'profile_photo' => $file,
            'expertise_tags' => ['Laravel', 'PHP'],
            'certifications' => ['AWS Certified'],
            'credentials' => ['BSc Computer Science']
        ]);

    $response->assertRedirect(route('instructor.profile.show'));
    $this->assertDatabaseHas('instructor_profiles', [
        'user_id' => $instructor->id,
        'bio' => 'New bio',
        'expertise_tags' => json_encode(['Laravel', 'PHP']),
    ]);
    
    $profile = $instructor->profile;
    $this->assertNotNull($profile->profile_photo_path);
    Storage::disk('public')->assertExists($profile->profile_photo_path);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_instructor_can_update_avatar_and_array_fields`
Expected: FAIL (Validation error on `profile_photo` or `credentials`, or image not stored)

**Step 3: Write minimal implementation**

Update `UpdateInstructorProfileRequest.php`:
```php
public function rules(): array
{
    return [
        'bio' => ['nullable', 'string', 'max:2000'],
        'educational_background' => ['nullable', 'string', 'max:255'],
        'professional_background' => ['nullable', 'string', 'max:3000'],
        'primary_expertise' => ['nullable', 'string', 'max:255'],
        'expertise_tags' => ['nullable', 'array'],
        'expertise_tags.*' => ['string', 'max:100'],
        'years_experience' => ['nullable', 'integer', 'min:0'],
        'certifications' => ['nullable', 'array'],
        'certifications.*' => ['string', 'max:255'],
        'credentials' => ['nullable', 'array'],
        'credentials.*' => ['string', 'max:255'],
        'profile_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
    ];
}
```

Update `ProfileController.php@update`:
```php
public function update(UpdateInstructorProfileRequest $request): RedirectResponse
{
    $user = Auth::user();
    $profile = InstructorProfile::firstOrCreate(['user_id' => $user->id], ['bio' => '']);
    
    $this->authorize('update', $profile);
    
    $validated = $request->validated();
    
    if ($request->hasFile('profile_photo')) {
        if ($profile->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($profile->profile_photo_path);
        }
        $validated['profile_photo_path'] = $request->file('profile_photo')->store('avatars', 'public');
    }
    
    unset($validated['profile_photo']); // Remove so it doesn't cause mass assignment issues if unfillable
    
    // Ensure array fields are handled properly if not sent
    $validated['expertise_tags'] = $validated['expertise_tags'] ?? [];
    $validated['certifications'] = $validated['certifications'] ?? [];
    $validated['credentials'] = $validated['credentials'] ?? [];

    $profile->update($validated);

    return redirect()->route('instructor.profile.show')
        ->with('success', 'Instructor profile updated successfully.');
}
```

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_instructor_can_update_avatar_and_array_fields`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Requests/Instructor/UpdateInstructorProfileRequest.php app/Http/Controllers/Instructor/ProfileController.php tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php
git commit -m "feat: handle avatar upload and array fields in instructor profile update"
```

---

### Task 2: Redesign Edit Profile View (Basic Setup & Avatar Upload)

**Files:**
- Modify: `resources/views/instructor/profile/edit.blade.php`

**Step 1: Write the failing test**

We bypass unit testing for pure view structural changes, relying on manual visual verification.

**Step 2: Write minimal implementation**

Update `edit.blade.php` to include `enctype` and the avatar upload component:
- Add `enctype="multipart/form-data"` to the form.
- Split into a 2-column aesthetic or stacked cards with `rounded-xl` and shadow.
- Create an avatar preview component using pure HTML/JS (or Alpine):
```html
<div x-data="{ photoName: null, photoPreview: null }" class="col-span-12 sm:col-span-12 mt-4 flex items-center gap-x-6">
    <div class="relative h-24 w-24 rounded-full overflow-hidden border bg-gray-100">
        <template x-if="!photoPreview">
            <img src="{{ $profile->profile_photo_path ? Storage::url($profile->profile_photo_path) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" class="h-full w-full object-cover">
        </template>
        <template x-if="photoPreview">
            <img :src="photoPreview" class="h-full w-full object-cover">
        </template>
    </div>
    <div>
        <input type="file" name="profile_photo" class="hidden" x-ref="photo" @change="
                photoName = $refs.photo.files[0].name;
                const reader = new FileReader();
                reader.onload = (e) => { photoPreview = e.target.result; };
                reader.readAsDataURL($refs.photo.files[0]);
            ">
        <button type="button" @click="$refs.photo.click()" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Change avatar</button>
    </div>
</div>
```
- Wrap main form fields in `<div class="bg-white shadow-sm sm:rounded-xl p-6 mb-6">` with soft blue highlights.

**Step 3: Run test to verify**

Run: `php artisan test --filter=InstructorProfilePageTest`
Expected: PASS (Ensure page still loads).

**Step 4: Commit**

```bash
git add resources/views/instructor/profile/edit.blade.php
git commit -m "style: redesign instructor profile edit basic layout and add avatar upload"
```

---

### Task 3: Add Alpine.js Dynamic Array Fields to Edit Profile

**Files:**
- Modify: `resources/views/instructor/profile/edit.blade.php`

**Step 1: Write the implementation**

Add Alpine components for `expertise_tags`, `credentials`, and `certifications` to `edit.blade.php`.

Implementation example for one of them (`expertise_tags`), replicate similarly for `certifications` and `credentials`:
```html
<div class="bg-white shadow-sm sm:rounded-xl p-6 mb-6" x-data="{
    tags: {{ json_encode(old('expertise_tags', $profile->expertise_tags ?? [])) }},
    newTag: '',
    addTag() {
        if(this.newTag.trim() !== '') {
            this.tags.push(this.newTag.trim());
            this.newTag = '';
        }
    },
    removeTag(index) {
        this.tags.splice(index, 1);
    }
}">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Expertise Tags</h3>
    <div class="flex flex-wrap gap-2 mb-3">
        <template x-for="(tag, index) in tags" :key="index">
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg">
                <input type="hidden" :name="`expertise_tags[${index}]`" :value="tag">
                <span x-text="tag"></span>
                <button type="button" @click="removeTag(index)" class="text-blue-500 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </template>
    </div>
    <div class="flex items-center gap-2">
        <input type="text" x-model="newTag" @keydown.enter.prevent="addTag()" placeholder="Add a tag..." class="block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        <button type="button" @click="addTag()" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-blue-600 shadow-sm ring-1 ring-inset ring-blue-300 hover:bg-blue-50">Add</button>
    </div>
</div>
```

**Step 2: Review visually manually**

Open the instructor profile page and verify that adding and removing dynamic arrays works correctly and submits properly.

**Step 3: Commit**

```bash
git add resources/views/instructor/profile/edit.blade.php
git commit -m "feat: add alpine dynamic array inputs for tags, certifications, and credentials"
```

---

### Task 4: Redesign the Profile Show Page (Bento Layout & Avatars)

**Files:**
- Modify: `resources/views/instructor/profile/show.blade.php`

**Step 1: Implement the visual design**

Update `show.blade.php`:
- Create the **Hero Profile Card**: 
  - Avatar on the left (rounded-full, h-24 w-24, shadow-sm, using `Storage::url`).
  - Name, Email, Primary Expertise on the right.
  - "Edit Profile" button floating or properly positioned.
- Add an explicit visual section for the array tags (using `bg-blue-50 text-blue-700 rounded-lg` pill shapes).
```html
@if(!empty($profile->expertise_tags) && count($profile->expertise_tags) > 0)
    <div class="flex flex-wrap gap-2 mt-4">
        @foreach($profile->expertise_tags as $tag)
            <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 border border-blue-100">{{ $tag }}</span>
        @endforeach
    </div>
@endif
```
- Restyle the bento-grid. Replace simple flat borders with `rounded-xl` and use consistent structural gaps. Apply `bg-gray-50/50 border border-gray-100` to the internal stat wrappers instead of `bg-gray-50`. Let's elevate it to premium quality.

**Step 2: Run test to verify**

Run: `php artisan test --filter=InstructorProfilePageTest`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/views/instructor/profile/show.blade.php
git commit -m "style: redesign instructor profile show page with bento-grid and theme alignment"
```
