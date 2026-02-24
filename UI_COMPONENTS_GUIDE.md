# UI Component Library - Usage Guide

## 🎨 Overview

This document provides usage examples for the custom UI component library built for the Sex Ed Platform front-end enhancement.

## 📦 Available Components

### 1. **Button** (`<x-ui.button>`)

Enhanced button component with multiple variants and states.

**Props:**
- `variant`: `primary` | `secondary` | `success` | `danger` | `outline` | `ghost` (default: `primary`)
- `size`: `sm` | `md` | `lg` | `xl` (default: `md`)
- `type`: `button` | `submit` | `reset` (default: `button`)
- `loading`: `true` | `false` (default: `false`)
- `icon`: HTML icon markup (optional)

**Examples:**
```blade
<!-- Primary button -->
<x-ui.button>Save Changes</x-ui.button>

<!-- Success button with icon -->
<x-ui.button variant="success" icon='<svg>...</svg>'>
    Complete Lesson
</x-ui.button>

<!-- Loading state -->
<x-ui.button :loading="true">Processing...</x-ui.button>

<!-- Large danger button -->
<x-ui.button variant="danger" size="lg">Delete Account</x-ui.button>

<!-- Outline button -->
<x-ui.button variant="outline">Learn More</x-ui.button>
```

---

### 2. **Card** (`<x-ui.card>`)

Flexible card container with multiple styling variants.

**Props:**
- `variant`: `default` | `hover` | `gradient` | `glass` (default: `default`)
- `padding`: Any Tailwind padding class (default: `p-6`)

**Examples:**
```blade
<!-- Default card -->
<x-ui.card>
    <h3>Card Title</h3>
    <p>Card content goes here</p>
</x-ui.card>

<!-- Hover effect card -->
<x-ui.card variant="hover">
    <h3>Click me!</h3>
</x-ui.card>

<!-- Gradient card (great for premium features) -->
<x-ui.card variant="gradient">
    <h3 class="text-white">Premium Feature</h3>
</x-ui.card>

<!-- Glass morphism card -->
<x-ui.card variant="glass" padding="p-8">
    <p>Modern glass effect</p>
</x-ui.card>
```

---

### 3. **Badge** (`<x-ui.badge>`)

Status indicators and labels.

**Props:**
- `variant`: `primary` | `success` | `warning` | `danger` | `info` (default: `primary`)
- `size`: `sm` | `md` | `lg` (default: `md`)
- `outline`: `true` | `false` (default: `false`)

**Examples:**
```blade
<!-- Success badge -->
<x-ui.badge variant="success">Completed</x-ui.badge>

<!-- Warning badge -->
<x-ui.badge variant="warning">Pending</x-ui.badge>

<!-- Large outline badge -->
<x-ui.badge variant="primary" size="lg" :outline="true">
    Level 5
</x-ui.badge>
```

---

### 4. **Alert** (`<x-ui.alert>`)

Notification and feedback messages.

**Props:**
- `type`: `success` | `error` | `warning` | `info` (default: `info`)
- `dismissible`: `true` | `false` (default: `false`)

**Examples:**
```blade
<!-- Success alert -->
<x-ui.alert type="success">
    Your profile has been updated successfully!
</x-ui.alert>

<!-- Dismissible error alert -->
<x-ui.alert type="error" :dismissible="true">
    There was an error processing your request.
</x-ui.alert>

<!-- Warning alert -->
<x-ui.alert type="warning">
    Your subscription expires in 3 days.
</x-ui.alert>
```

---

### 5. **Spinner** (`<x-ui.spinner>`)

Loading indicator.

**Props:**
- `size`: `sm` | `md` | `lg` | `xl` (default: `md`)
- `color`: `purple` | `blue` | `pink` | `gray` (default: `purple`)

**Examples:**
```blade
<!-- Default spinner -->
<x-ui.spinner />

<!-- Large blue spinner -->
<x-ui.spinner size="lg" color="blue" />

<!-- Inline with text -->
<div class="flex items-center space-x-2">
    <x-ui.spinner size="sm" />
    <span>Loading...</span>
</div>
```

---

### 6. **Empty State** (`<x-ui.empty-state>`)

Display when no data is available.

**Props:**
- `icon`: Emoji or HTML (default: `📭`)
- `title`: Heading text (default: `'No data found'`)
- `description`: Description text
- `actionText`: Button text (optional)
- `actionUrl`: Button URL (optional)

**Examples:**
```blade
<!-- Basic empty state -->
<x-ui.empty-state
    icon="📚"
    title="No modules yet"
    description="Start by creating your first module."
    actionText="Create Module"
    actionUrl="{{ route('instructor.modules.create') }}"
/>

<!-- With custom content -->
<x-ui.empty-state icon="🎯" title="No quiz attempts">
    <p class="text-gray-600">Take your first quiz to see results here.</p>
</x-ui.empty-state>
```

---

### 7. **Skeleton** (`<x-ui.skeleton>`)

Loading placeholder for content.

**Props:**
- `width`: Tailwind width class (default: `w-full`)
- `height`: Tailwind height class (default: `h-4`)
- `rounded`: Tailwind border-radius class (default: `rounded`)

**Examples:**
```blade
<!-- Text skeleton -->
<x-ui.skeleton height="h-4" width="w-3/4" />
<x-ui.skeleton height="h-4" width="w-1/2" />

<!-- Avatar skeleton -->
<x-ui.skeleton width="w-12" height="h-12" rounded="rounded-full" />

<!-- Card skeleton -->
<x-ui.card>
    <x-ui.skeleton height="h-6" width="w-1/3" class="mb-3" />
    <x-ui.skeleton height="h-4" class="mb-2" />
    <x-ui.skeleton height="h-4" width="w-5/6" class="mb-2" />
    <x-ui.skeleton height="h-4" width="w-4/6" />
</x-ui.card>
```

---

### 8. **Progress Bar** (`<x-ui.progress-bar>`)

Visual progress indicator.

**Props:**
- `value`: Current value (default: `0`)
- `max`: Maximum value (default: `100`)
- `color`: `gradient` | `purple` | `blue` | `green` | `red` | `yellow` (default: `gradient`)
- `showLabel`: Show percentage label (default: `false`)
- `height`: Tailwind height class (default: `h-2`)

**Examples:**
```blade
<!-- Basic progress bar -->
<x-ui.progress-bar :value="75" :max="100" />

<!-- With label -->
<x-ui.progress-bar :value="$userXP" :max="$nextLevelXP" :showLabel="true" color="purple">
    Level Progress
</x-ui.progress-bar>

<!-- Lesson progress -->
<x-ui.progress-bar 
    :value="$completedLessons" 
    :max="$totalLessons" 
    color="green"
    :showLabel="true"
/>
```

---

## 🎨 Toast Notifications

JavaScript toast notification system powered by Toastify.js.

**Available globally as `window.toast`**

### Methods:

```javascript
// Success toast
toast.success('Profile updated successfully!');

// Error toast
toast.error('Failed to save changes');

// Warning toast
toast.warning('Your session will expire soon');

// Info toast
toast.info('New feature available!');

// Primary/Purple toast
toast.primary('Welcome to the platform!');

// Achievement toast (centered, special styling)
toast.achievement('First Quiz Completed!');

// Level up toast (extra special)
toast.levelUp(5);

// XP gained toast
toast.xp(25);

// Custom toast with options
toast.show('Custom message', 'success', {
    duration: 5000,
    gravity: 'bottom',
    position: 'left'
});
```

### Usage in Blade:

```blade
<script>
    // On success
    @if(session('success'))
        toast.success('{{ session('success') }}');
    @endif

    // On error
    @if($errors->any())
        toast.error('{{ $errors->first() }}');
    @endif
</script>
```

### Usage with Alpine.js:

```blade
<div x-data="{ saving: false }">
    <button @click="
        saving = true;
        // ... save logic
        toast.success('Changes saved!');
        saving = false;
    ">
        Save
    </button>
</div>
```

---

## 🎨 Utility Classes

### Animations:
- `animate-fade-in` - Fade in
- `animate-slide-in-up` - Slide in from bottom
- `animate-scale-in` - Scale in
- `animate-bounce-in` - Bounce in
- `hover-lift` - Lift on hover
- `btn-press` - Press effect on click

### Effects:
- `shimmer` - Shimmer loading effect
- `gradient-animate` - Animated gradient
- `glow-hover` - Glow on hover
- `pulse-notification` - Pulse for notifications

### Shadows:
- `shadow-soft` - Soft shadow
- `shadow-medium` - Medium shadow
- `shadow-large` - Large shadow
- `shadow-glow-purple` - Purple glow
- `shadow-glow-blue` - Blue glow

---

## 📋 Component Composition Examples

### Stat Card with Progress:
```blade
<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Module Progress</h3>
            <p class="text-sm text-gray-500">5 of 10 lessons completed</p>
        </div>
        <x-ui.badge variant="primary">50%</x-ui.badge>
    </div>
    <x-ui.progress-bar :value="5" :max="10" color="purple" />
</x-ui.card>
```

### Loading State:
```blade
<div x-data="{ loading: true }">
    <div x-show="loading">
        <x-ui.card>
            <x-ui.skeleton height="h-6" width="w-1/3" class="mb-3" />
            <x-ui.skeleton class="mb-2" />
            <x-ui.skeleton width="w-5/6" />
        </x-ui.card>
    </div>
    
    <div x-show="!loading" x-cloak>
        <!-- Actual content -->
    </div>
</div>
```

### Action Card:
```blade
<x-ui.card variant="hover">
    <div class="flex items-start space-x-4">
        <div class="text-4xl">🎯</div>
        <div class="flex-1">
            <h3 class="text-lg font-semibold mb-2">Complete Your Profile</h3>
            <p class="text-gray-600 mb-4">Add more information to personalize your experience</p>
            <x-ui.button variant="primary" size="sm">
                Complete Now
            </x-ui.button>
        </div>
    </div>
</x-ui.card>
```

---

## 🎨 Color Palette

### Brand Colors:
- **Purple**: `brand-purple-50` to `brand-purple-900`
- **Blue**: `brand-blue-50` to `brand-blue-900`
- **Pink**: `brand-pink-50` to `brand-pink-900`

### Usage:
```blade
<div class="bg-brand-purple-600 text-white">Primary Action</div>
<div class="text-brand-blue-700">Link Text</div>
<div class="border-brand-pink-300">Pink Border</div>
```

---

## 📚 Next Steps

When you provide your Figma designs, we can:
1. Match exact colors and spacing
2. Create page-specific components
3. Implement your custom layouts
4. Add any additional components needed

**Ready for the next phase!** 🚀
