# Global Pop-up Chat System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Provide a modern messaging experience that allows users to communicate via a Facebook-Messenger style global pop-up chat window without leaving their current page, ensuring design consistency with the platform’s TailAdmin-based UI.

**Architecture:** A globally injected Alpine.js component (`x-data="globalChat"`) on the app layout that persists active conversation state across pages and polls/listens for messages via the existing REST API.

**Tech Stack:** Laravel, Blade, Alpine.js (`$persist`), Tailwind CSS.

---

### Task 1: Create the Global Alpine component and attach to Layouts

**Files:**
- Create: `resources/views/chat/partials/global-popup.blade.php`
- Modify: `resources/views/layouts/app.blade.php`, `resources/views/layouts/admin.blade.php`, `resources/views/layouts/instructor-app.blade.php`, `resources/views/layouts/learner-sidebar.blade.php`.

**Step 1: Create the basic Alpine component shell**
Create the component structure:
```html
<div x-data="globalChat" x-cloak class="fixed bottom-0 right-4 z-50 flex items-end gap-3 pointer-events-none">
   <template x-for="conv in openWindows" :key="conv.id">
       <div class="pointer-events-auto w-80 bg-white rounded-t-2xl shadow-xl overflow-hidden border border-gray-200">
           <!-- header, body, footer -->
       </div>
   </template>
</div>
```
With an Alpine component containing `openWindows: this.$persist([])`.

**Step 2: Include the component in layouts**
In the bottom of every layout (before `</body>`), add `@include('chat.partials.global-popup')`.

**Step 3: Define Global Alpine Component Script**
Add `<script>` tag inside `global-popup.blade.php` defining the `document.addEventListener('alpine:init')` and `Alpine.data('globalChat', ...)`.
Add functionality to open, minimize, close chat windows.

### Task 2: Implement "Refined Minimalist" UI for Pop-Up Window

**Files:**
- Modify: `resources/views/chat/partials/global-popup.blade.php`

**Step 1: Header UI**
Create a refined header with avatar, name, and minimize/close icons. Background soft-white, subtle gray border. No heavy colors.

**Step 2: Body (Messages List)**
Add the message bubble UI design: `rounded-2xl`, with a tail for the sender side. Use `bg-gray-100 text-gray-800` for received, `bg-blue-600 text-white` for sent. Add spacing and distinct text formatting for sender. Include an auto-scroll to bottom behavior.

**Step 3: Footer (Input Area)**
Minimalist input area: border-top thin gray.
Icon actions only (no "Send" text): microphone, paperclip, send arrows. 

### Task 3: Contextual Access inside Modules

**Files:**
- Modify: `resources/views/learner/modules/show.blade.php`
- Modify: `resources/views/learner/lessons/show.blade.php`
- Modify: `resources/views/learner/lessons/partials/topic-page.blade.php`
- Modify: `resources/views/learner/lessons/partials/quiz-page.blade.php`

**Step 1: Convert Links to Dispatch Event**
Replace `href="{{ route('chat.page', [...]) }}"` triggers with Alpine `$dispatch('open-global-chat', { target_user_id: X, module_id: Y })` so that a pop-up opens immediately instead of redirecting the user to the chat page.

### Task 4: Fix Main Chat Page UI to match New Aesthetic

**Files:**
- Modify: `resources/views/chat/partials/conversation-panel.blade.php`

**Step 1: Sync Bubble Design**
Apply the new bubble aesthetic (`rounded-2xl`, tail-styles, spacing) inside `conversation-panel.blade.php` to match the popup window.

**Step 2: Sync Input UI**
Change full-page input buttons to strictly use icons, retaining functionality (attachments/voice recording).

---