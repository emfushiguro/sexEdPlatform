# Admin Integration & Instructor Application Improvements Design

## 1. Overview
This document outlines the design for enhancing the Admin Integration in the platform, specifically focusing on the admin navigation structure, UI/UX consistency, and the Instructor Application management system.

## 2. Architecture & Database Updates
*   **Database Schema:** Add two new nullable columns to the `instructor_applications` table via a new migration:
    *   `rejection_reason` (string): Stores the system key for the preset reason (e.g., `incomplete_info`, `insufficient_background`, `guidelines_mismatch`, `invalid_credentials`, `topic_mismatch`, `custom`).
    *   `rejection_notes` (text): Stores the custom message if the admin chooses "Other" or needs to provide additional context.
*   **Notifications:** Create a new Laravel Notification (`ApplicationRejectedNotification`) that sends an email and potentially an in-app database notification to the learner, cleanly formatting the reason so they understand exactly what they need to fix.

## 3. Sidebar Navigation Enhancements
*   Create a new **Moderation** group in the left sidebar, logically positioned for admin workflows.
*   **Links inside Moderation:**
    *   *Instructor Applications*: Redirects to the application review index. Include a badge for pending counts if possible.
    *   *Module Published Review*: Redirects to the module moderation page. Include a badge for pending counts if possible.

## 4. Admin Dashboard UI/UX
*   **Top Row (Stats Grid):** 4-6 polished cards displaying key metrics (Total Users, Total Instructors, Total Learners, Total Modules, Active Subscriptions).
*   **Bottom Section (Two-Column Layout):**
    *   *Left Column:* "System Activity" feed (recent registrations, subscriptions, etc.).
    *   *Right Column:* "Action Required" section, split into tabs or stacked cards showing the queue of Pending Instructor Applications and Pending Module Reviews.
*   **Styling:** Align entirely with existing styling in Subscription Plans, Subscribers, and Payments (PSR-12, reusable Blade components, Tailwind CSS).

## 5. Instructor Application Management
*   **Index View:** A standard data table matching existing admin pages (e.g., Subscribers).
    *   *Columns:* Applicant Name, Username, Date of Application, Status.
    *   *Actions:* A "Review" button that goes to the detail view.
*   **Detail View:** Clean layout showing the applicant's Educational Background, Professional Expertise, Location, etc., using standard admin card styling.
*   **Rejection Flow (UI):**
    *   Implemented via an Alpine.js/Livewire modal overlay.
    *   Presents built-in reasons via clean radio buttons.
    *   Selecting "Other/Custom" dynamically reveals a text area for custom reasons.
    *   Submitting the modal updates the application status to "Rejected", saves the exact reason/notes, and triggers the `ApplicationRejectedNotification`.
