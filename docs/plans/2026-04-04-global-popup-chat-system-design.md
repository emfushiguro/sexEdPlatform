# Global Pop-Up Chat System & UI Improvements Design

## 1. Purpose & Goals
Provide a modern messaging experience that allows users to communicate without leaving their current page, ensuring design consistency with the platform’s TailAdmin-based UI.

## 2. Architecture & Data Flow
- **Framework:** Pure Alpine.js managed via a global store (`$store.globalChat`) interacting with the existing Laravel REST API for messages.
- **State Management:** Open/minimized chat windows will be persisted using Alpine's `$persist` (localStorage) across page navigations. 
- **Real-Time Data Flow:** Laravel Reverb (WebSockets) will be integrated at the global layout level to push notifications and incoming messages directly to the active components.

## 3. UI/UX Design Direction ("Refined Minimalist")
- **Input Area:** Icon-driven action buttons (mic, paperclip, send) replacing wordy buttons to declutter the UI.
- **Bubbles:** Rounded (`rounded-2xl` with a sharp tail `rounded-br-none` style for sender), soft gray borders, clear contrast for sender/receiver. High emphasis on readable typography.
- **Attachment Cards:** Visually balanced cards displaying previews (image/video/document icons), truncated filenames, and optional sizes.
- **Motion:** Subtle CSS transitions (`duration-200 ease-out`) for opening, closing, and minimizing floating windows to prevent jarring jumps.

## 4. Contextual Integration
- The global chat pop-up trigger will be positioned in the bottom-right corner or accessed via the global navigation header.
- The "Chat" button present inside modules, lessons, lesson topics, and quizzes will directly trigger the respective conversation window to open globally rather than fully redirecting to the `/chat` page. Multiple concurrent instances will be stacked side-by-side with horizontal constraints.

## 5. Accessibility & Theming
- Strict adherence to TailAdmin color variables and Tailwind standard classes.
- Proper `aria-labels` and `title` tags on icon-only buttons for screen readers.
- Predictable focus outlines via Keyboard navigation (`focus:ring-2 focus:ring-offset-2`).

**Approval Status:** Approved by User.