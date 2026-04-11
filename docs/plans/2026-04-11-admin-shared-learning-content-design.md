# Admin Shared Learning Content Design

Date: 2026-04-11
Status: Approved
Owner: Platform Engineering

## 1. Problem Statement

The platform already has a complete Instructor Learning Content Creation System (modules, lessons, topics, quizzes, enrollments, learner monitoring). Admin currently reaches these workflows by redirecting to instructor-facing surfaces, causing:

- role-interface confusion (Admin appears to switch panels)
- unclear module ownership and crediting
- admin-created modules entering instructor review workflow unintentionally
- inconsistent admin UX for official platform content authoring

## 2. Goal

Integrate Learning Content management directly inside the Admin panel while reusing existing backend logic, controllers, and views as much as possible.

## 3. Approved Decisions

1. Admin default module scope: all modules with segmented ownership tabs.
2. Admin create workflow supports: publish, draft, archive.
3. Platform attribution label: fixed as "Conscious Connections Team" for admin-owned modules.
4. Admin learners content page default: all learners, with ownership-based filters.
5. Architecture approach: shared panel context adapter (balanced reuse + maintainability).

## 4. Architecture

### 4.1 Shared Controller Strategy

- Reuse existing instructor content controllers for:
  - modules
  - lessons
  - lesson topics
  - quizzes
  - enrollments
- Do not duplicate these controllers for admin.
- Keep current admin moderation/governance controllers (content reviews) intact.

### 4.2 Panel Context Adapter

Add a lightweight content panel context layer that resolves from route prefix/name:

- panel: admin|instructor
- route namespace: admin|instructor
- layout: layouts.admin|layouts.instructor-app
- capability flags for conditional UI/actions

This context will be used by shared controllers and views to generate routes and choose layout dynamically.

### 4.3 Route Topology

Keep two route groups, both mapping to shared content controllers:

- Instructor:
  - /instructor/modules
  - /instructor/lessons
  - /instructor/topics
  - /instructor/quizzes
  - /instructor/enrollments
- Admin:
  - /admin/modules
  - /admin/lessons
  - /admin/topics
  - /admin/quizzes
  - /admin/enrollments
  - /admin/learners (new content-management learners surface)

Access remains permission-based.

## 5. Ownership, Crediting, and Publishing Rules

### 5.1 Canonical Fields

Continue using existing module fields as source of truth:

- created_by
- content_owner_type
- current_review_status
- published_by_admin_id
- is_published

### 5.2 Ownership/Crediting

- Instructor-created modules:
  - content_owner_type = instructor
  - credit label: instructor identity
- Admin-created modules:
  - content_owner_type = admin
  - credit label: Conscious Connections Team
  - created_by still stores real admin author for auditability

### 5.3 Workflow Rules

Instructor workflow (unchanged):

- author draft
- submit/resubmit for admin review
- publish only through moderation approval

Admin workflow (inside admin panel):

- Publish: approved + learner-visible
- Draft: non-visible draft
- Archive: archived/non-visible via soft delete pattern

Admin-owned content must not enter instructor submission queue.

## 6. Authorization and Scope

### 6.1 RBAC

- Keep permission-first middleware and policy checks.
- Admin already has full permissions through current role seeding.

### 6.2 Policy Alignment

Extend owner-only policies for shared mode:

- LessonPolicy
- TopicPolicy
- QuizPolicy
- enrollment ownership checks in shared enrollment flows

Rules:

- Instructor remains owner-scoped.
- Admin receives full cross-owner visibility and management rights where permitted.

## 7. UI/UX Reuse Plan

### 7.1 View Reuse

- Reuse existing instructor content Blade views.
- Add dynamic layout selection so shared views render under:
  - admin layout in admin panel
  - instructor layout in instructor panel

### 7.2 Route Reuse in Views

Replace hardcoded instructor route references in shared content views with context-aware route generation using the panel context namespace.

### 7.3 Admin Sidebar Integration

Add Admin sidebar section: Create Learning Contents

- Modules
- Lessons
- Lesson Topics
- Quizzes
- Enrollments
- Learners

### 7.4 Module Listing Organization (Admin)

All-modules page should support segmented organization:

- All Modules
- Platform Modules
- Instructor Modules
- Archived

## 8. Learners and Enrollment Monitoring

### Admin

- view all learners
- filter by enrollment ownership (platform/instructor)
- manage enrollments across modules
- monitor progress and remove enrollments as permitted

### Instructor

- view/manage only learners enrolled in instructor-owned modules
- retain existing scoped behavior

## 9. Error Handling and Security

- keep policy authorization at each action
- use 403/404 patterns as currently established
- preserve Form Request validation where available
- maintain service-layer governance behavior as canonical

## 10. Testing Requirements

Implement/extend tests to verify:

1. Admin can access shared content routes in admin namespace.
2. Instructor route behavior remains unchanged.
3. Policy regression coverage for admin override + instructor ownership isolation.
4. Enrollment visibility/management scope by role and ownership.
5. Module attribution rendering for instructor vs platform content.
6. Admin publish/draft/archive behavior correctness.
7. Review queue excludes admin-owned direct-authoring modules.

## 11. Non-Goals

- No SPA/API architecture migration.
- No duplication of content controller stacks.
- No replacement of moderation queue architecture.

## 12. Rollout Notes

- Prefer no schema migration unless a gap is discovered during implementation.
- If any DB changes are required, use additive reversible migrations.
- Preserve learner visibility constraints and governance behavior.

## 13. Next Step

Create a detailed implementation plan using the writing-plans workflow, then execute incrementally with verification and regression tests.