# Instructor Profile Enhancement Design

## Context
The current instructor profile pages (`show` and `edit`) lack visual polish and do not utilize all the available fields in the `InstructorProfile` model (such as `profile_photo_path`, `specialization`, `expertise_tags`, `certifications`, and `credentials`). This design aims to align the UI/UX with the current instructor theme (Tailwind, light-mode, blue-600 accents, soft rounded-xl borders).

## Features & UI/UX Design

### 1. Unified Theme Alignment
- Use `rounded-xl` for cards, matching the instructor dashboard.
- Consistent typography and spacing (using Tailwind utility classes).
- Use `blue-600` for primary actions and soft `blue-50` backgrounds for highlights.

### 2. Instructor Profile Show Page
- **Hero Section:** A top profile card displaying the instructor's avatar (circular, bordered), name, email, primary expertise, and a prominent "Edit Profile" button.
- **Tags & Specialties:** A visual section displaying `expertise_tags`, `certifications`, and `credentials` as styled pill badges.
- **Bento-Grid Layout:** Side-by-side or balanced sections for Educational Background, Professional Background, and Instructor Overview Stats (Modules created, etc.).

### 3. Instructor Profile Edit Page
- **Avatar Upload:** A visually distinct section at the top of the form with a live preview of the currently uploaded avatar and file input.
- **Segmented Form Layout:** 
  - *Basic Details Card*: Avatar, Bio, Primary Expertise, Specialization, Years of Experience.
  - *Expanded Background Card*: Educational Background, Professional Background.
  - *Dynamic Arrays Card*: `expertise_tags`, `certifications`, and `credentials`.
- **Alpine.js Dynamic Inputs:** Given the user's choice, complex array fields (`expertise_tags`, `certifications`, `credentials`) will use Alpine.js to allow users to dynamically "Add Tag", "Remove Tag", etc.
- **Password Form:** Maintained at the bottom in its own visual container.

## Architecture & Data Flow
- **Controller/Request:** `UpdateInstructorProfileRequest` and `ProfileController@update` will be updated to handle `profile_photo_path` file uploads (storing to public disk) and the array fields.
- **Alpine Store/Data:** Form views will use lightweight `x-data` blocks to manage the dynamic lists before submission.
- **Casts:** Modifying array fields is naturally supported since `InstructorProfile` already casts these fields to `array`.
