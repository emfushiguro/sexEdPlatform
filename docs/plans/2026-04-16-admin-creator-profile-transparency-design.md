# Admin Creator Profile Transparency Design

Date: 2026-04-16
Status: Approved (Brainstorming Completed)
Owner: Platform Engineering

## 1. Purpose
Create a dedicated Admin Creator Profile that represents platform developers professionally while improving public transparency for module ownership across learner and instructor experiences.

The profile must communicate credibility, accountability, and ownership clarity without exposing sensitive internal administration details.

## 2. Problem Statement
Current behavior has two gaps:

1. Admin profile is currently minimal and lacks creator identity depth.
2. Admin-owned module attribution is mostly static and does not provide a full information path similar to instructor ownership transparency.

As a result, users can see platform ownership labels but cannot consistently inspect creator identity context in the same way they can inspect instructor context.

## 3. Approved Decisions
Locked decisions from brainstorming:

1. Ownership presentation mode: team-first public identity.
2. Individual attribution control: module-level optional visibility.
3. Role label: Platform Developer.
4. Public name source: dedicated public display name field.
5. Default public fields: display name, avatar, role label, short bio, affiliation.
6. Always private fields: email, permissions, internal role mappings, and audit/security metadata.
7. Affiliation model: editable with prefilled default and max length.
8. Ownership UI: owner row with avatar plus owner text, no Official Platform Content badge.
9. Transparency flow: same learner-side pattern as instructor, with View Full Information page for admin creators.
10. Public contribution metrics: modules published, latest updated module, learners reached (aggregated).
11. Publishing model: immediate update for low-risk profile fields.
12. Restricted fields: role and permissions locked from profile UI.
13. Validation strategy: align with existing learner and instructor profile validation patterns.
14. Propagation: immediate render-based reflection from source of truth.
15. Trust marker: none for this phase.
16. Incomplete profile fallback: team name plus default platform avatar.
17. Rollout scope: dedicated-domain full rebuild.
18. Completion criteria: end-to-end behavior plus tests and authorization boundaries.

## 4. Goals
Primary goals:

1. Establish a professional, consistent identity layer for platform creators.
2. Make module ownership transparency easy to understand for learners and instructors.
3. Preserve strict security boundaries between creator profile data and system authorization data.

Secondary goals:

1. Keep visual and interaction consistency with existing profile surfaces.
2. Keep implementation additive and reversible.
3. Keep controllers thin and service-first.

## 5. Non-Goals
This phase will not:

1. Change RBAC model or permission schema.
2. Add public trust badges or verification markers.
3. Rework instructor profile architecture.
4. Expose sensitive admin internals publicly.

## 6. Data Architecture
### 6.1 Dedicated Domain
Introduce a dedicated profile domain instead of reusing generic user profile records.

Proposed table: admin_creator_profiles

Proposed core columns:

1. id
2. user_id (unique, foreign key to users)
3. public_display_name
4. bio
5. affiliation
6. avatar_path
7. show_individual_attribution (boolean)
8. created_at
9. updated_at

### 6.2 Why Dedicated Table
1. Keeps admin creator identity concerns isolated.
2. Avoids overloading learner-oriented user profile fields.
3. Simplifies policy and DTO-level field whitelisting for public exposure.

## 7. Identity Field Contract
### 7.1 Publicly Visible Fields
1. Public display name
2. Avatar
3. Fixed role label: Platform Developer
4. Short bio
5. Affiliation
6. Aggregated contribution summary

### 7.2 Private or Locked Fields
1. Role and permission assignments
2. Email and verification state
3. Internal moderation and audit metadata
4. Any security or credential management field

### 7.3 Validation Contract
Use profile-style validation parity with learner and instructor flows:

1. public_display_name: required string, bounded length
2. bio: nullable string, bounded length
3. affiliation: required string, bounded length, default prefill
4. avatar: nullable image, mime and size constraints aligned to existing profile standards
5. show_individual_attribution: boolean

## 8. Ownership Transparency Contract
### 8.1 Team-First Ownership Label
Admin-owned module displays remain team-first by default for consistency and safety.

### 8.2 Optional Individual Attribution
If enabled, module ownership can reveal individual admin creator context using approved public fields.

### 8.3 No Additional Badge Layer
Do not introduce Official Platform Content or trust badges in this implementation.

## 9. UI and UX Design Contract
### 9.1 Admin Self Profile (Private Admin Side)
Sections:

1. Hero identity block (avatar, display name, role label, affiliation)
2. About section (bio)
3. Contributions summary (modules published, learners reached, latest updated module)
4. Edit call to action

### 9.2 Admin Edit Profile Page
Editable controls:

1. Avatar upload with preview
2. Public display name
3. Bio
4. Affiliation
5. Individual attribution visibility toggle

Non-editable controls:

1. Role/permission/system security fields shown read-only or omitted

### 9.3 Learner Module Overview Integration
For admin-owned modules, render an Admin Creator Information panel parallel to the instructor information pattern.

Interaction:

1. Keep CTA text as View Full Information page.
2. Route to admin creator public information page.

### 9.4 Public Admin Creator Information Page
Sections:

1. Identity header
2. Bio and affiliation
3. Contribution summaries

Tone:

1. Minimal and professional
2. No internal jargon
3. No sensitive data leakage

### 9.5 Visual Consistency Rules
1. Preserve existing profile spacing, typography rhythm, and card hierarchy.
2. Reuse established theme palette and component language.
3. Keep desktop and mobile layout quality consistent with existing profile surfaces.

## 10. Application Architecture
### 10.1 Controllers (Thin)
Admin side:

1. show profile
2. edit profile
3. update profile

Public side:

1. show admin creator information page

### 10.2 Service Layer
Proposed services:

1. AdminCreatorProfileService for profile read/update/media handling
2. AdminOwnershipDisplayService for ownership display normalization and fallback behavior

### 10.3 Request Validation
Use dedicated Form Request for admin profile update.

## 11. Routing Strategy
### 11.1 Admin Routes
Add admin self-profile edit and update routes in admin route group.

### 11.2 Learner/Public Transparency Route
Add learner-side route for viewing admin creator information pages, parallel to learner instructor profile route behavior.

## 12. Policy and Security Boundaries
1. Admin can edit own creator profile.
2. Admin cannot change role/permissions through profile form.
3. Public page only exposes whitelisted fields.
4. All views and controllers must use explicit projection to avoid accidental field leakage.

## 13. Data Flow
### 13.1 Read Flow
1. Module page resolves owner type.
2. Ownership display service returns a normalized display DTO.
3. View renders team-first identity plus optional individual attribution.

### 13.2 Update Flow
1. Admin submits profile form.
2. Form Request validates and sanitizes.
3. Service updates profile and avatar path.
4. Ownership-rendering surfaces reflect changes immediately.

## 14. Error Handling and Fallbacks
1. Missing profile: fallback to team label and platform avatar.
2. Missing avatar: initials or platform avatar fallback.
3. Invalid media: standard validation feedback.
4. Unauthorized edit attempts: policy denial and safe redirect/403.

## 15. Testing Strategy
### 15.1 Feature Tests
1. Admin can view edit update own creator profile.
2. Restricted fields cannot be edited through profile payload.
3. Public admin creator page shows only allowed fields.
4. Learner module overview displays admin creator information for admin-owned modules.
5. View Full Information page link resolves correctly for admin-owned modules.
6. Team-first fallback behavior works with incomplete profile.

### 15.2 Authorization Tests
1. Non-owner cannot update another admin creator profile.
2. Public projection excludes sensitive columns.

### 15.3 Manual Checklist
1. Profile edit UX consistency with existing profile forms.
2. Ownership display consistency across module cards and module overview.
3. Mobile and desktop readability.
4. Immediate propagation after profile update.

## 16. Rollout Sequence
1. Add schema and model relationships.
2. Add service and request layers.
3. Add admin profile edit UI and private profile display enrichment.
4. Add public admin creator information page.
5. Integrate admin creator information panel and View Full Information page flow into learner module overview for admin-owned modules.
6. Add tests and verification.

## 17. Acceptance Criteria
Done means all are true:

1. Dedicated admin creator profile domain exists and is editable by admins for approved fields.
2. Learner module overview provides admin creator transparency equivalent to instructor pattern for admin-owned modules.
3. View Full Information page exists for admin creator public information.
4. Team-first fallback identity remains safe and consistent.
5. Restricted system fields are not editable via profile UI.
6. Relevant feature and authorization tests pass.

## 18. Risks and Mitigations
Risk: accidental exposure of internal admin fields.
Mitigation: explicit view-model projection and policy tests.

Risk: inconsistency across module ownership surfaces.
Mitigation: centralized ownership display service and shared helper contract.

Risk: UX drift from existing profile standards.
Mitigation: reuse of existing profile layout and styling patterns.

---

Approval status: approved by user during brainstorming, finalized for documentation on 2026-04-16.