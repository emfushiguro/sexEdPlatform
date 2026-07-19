# System Module Audit

Audit date: 2026-07-07

Scope inspected: `routes/*.php`, `bootstrap/app.php`, controllers, services, models, migrations, policies, middleware, notifications, jobs, scheduled commands, and Blade navigation/layouts.

Only modules with routed behavior plus implementation support are counted as fully implemented. Schema-only or placeholder areas are listed at the end.

## Fully Implemented Modules

### 1. Authentication, Registration, and Account Access
- Implements learner registration, parent registration, unified learner/instructor login, hidden admin login, logout, password reset, password confirmation, email verification, and signed parent approval links.
- Evidence: `routes/auth.php`, `routes/web.php`; controllers under `app/Http/Controllers/Auth`; views under `resources/views/auth`.
- Data/support: `users`, `learner_profiles`, `user_profiles`, `parent_child_accounts`; temp upload support via `RegistrationTempUploadService`.
- Notifications: `CustomVerifyEmail`, `CustomResetPassword`.

### 2. User Profile and Profile Completion
- Implements profile completion, learner profile editing, password changes, username availability checks, and account deletion.
- Evidence: `routes/web.php`; `ProfileController`; `Learner/ProfileCompletionController`; views under `resources/views/profile` and learner profile partials.
- Guarding: `profile.completed` middleware via `EnsureProfileCompleted`.
- Data/support: `users`, `learner_profiles`, `user_profiles`.

### 3. Admin Dashboard and Operations Shell
- Implements admin dashboard, admin profile, admin notifications, admin sidebar navigation, operational badges, and admin message entrypoint.
- Evidence: `routes/admin.php`; `Admin/DashboardController`, `Admin/AdminProfileController`, `Admin/NotificationController`; `resources/views/layouts/admin.blade.php`; `resources/views/admin/dashboard.blade.php`.
- Services/support: `AdminDashboardService`, `AdminActivityLogService`, `NotificationReadService`, `NotificationPayloadNormalizer`.
- Data: `admin_activity_logs`, `notifications`.

### 4. Admin User Management and RBAC
- Implements user CRUD, learner listing, status changes, role changes, role assignment, permission syncing, and user relationship administration.
- Evidence: `routes/admin.php`; `Admin/UserAdminController`, `RoleAdminController`, `PermissionAdminController`, `UserRelationshipAdminController`; views under `resources/views/admin/users`.
- Data/support: `users`, Spatie `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `role_transitions`, `parent_child_accounts`.
- Services: `UserManagementService`, `RoleSyncService`, `UserRelationshipService`.

### 5. Learning Content Authoring
- Implements module, lesson, lesson-topic, quiz, question, quiz import, image upload/library, activation/deactivation, restore, and force-delete flows.
- Evidence: `routes/instructor.php` and reused admin routes in `routes/admin.php`; `Instructor/ModuleController`, `LessonController`, `TopicController`, `QuizManagementController`, `ImageLibraryController`; views under `resources/views/instructor/{modules,lessons,topics,quizzes,image-library}` and `resources/views/admin/modules`.
- Policies: `ModulePolicy`, `LessonPolicy`, `TopicPolicy`, `QuizPolicy`.
- Data/support: `modules`, `lessons`, `lesson_topics`, `module_attachments`, `quizzes`, `quiz_questions`, `quiz_options`, `module_revisions`, `module_review_requests`.
- Services: `ContentAuthoringService`, `ContentAccessService`, `ContentOwnershipGuard`, `ContentGovernanceService`, `AdminModuleReviewWorkspaceService`.

### 6. Module Review and Content Governance
- Implements instructor module review submit/resubmit/withdraw and admin content review workspace with preview, start review, approve, reject, archive, and penalty confirmation.
- Evidence: `routes/instructor.php`, `routes/admin.php`; `Instructor/ModuleReviewController`; `Admin/ContentReviewController`, `ContentReviewPreviewController`; views under `resources/views/admin/content-reviews`.
- Data/support: `module_review_requests`, `module_revisions`, content governance columns on `modules`, instructor moderation history/profile tables.
- Notifications: `NewModuleSubmissionNotification`, `InstructorModuleReviewStatusNotification`, `InstructorModuleReviewDecisionNotification`.

### 7. Learner Module Discovery, Enrollment, Lessons, and Progress
- Implements learner dashboard, module browsing/details/reviews, enrollment, lesson/topic viewing, topic completion/uncompletion, module completion, and learner search.
- Evidence: `routes/web.php`; `Learner/DashboardController`, `ModuleController`, `LessonController`, `SearchController`, `ModuleReviewPageController`; views under `resources/views/learner/{dashboard,modules,lessons}`.
- Data/support: `module_enrollments`, `user_progress`, `lesson_topic_progress`, `modules`, `lessons`, `lesson_topics`.
- Services: `LearnerModuleCompletionService`, `EntitlementService`, `ContentAccessService`.

### 8. Enrollment Management
- Implements instructor/admin enrollment queues, module enrollment views, approve/reject/archive/delete flows, parent approval for child enrollments, and enrollment notifications.
- Evidence: `routes/instructor.php`, `routes/admin.php`, parent routes in `routes/web.php`; `Instructor/EnrollmentController`, `ParentController`; views under `resources/views/instructor/enrollments` and `resources/views/parent/children`.
- Data/support: `module_enrollments`, parent-child relationship tables.
- Notifications: `EnrollmentApproved`, `EnrollmentRejected`, learner/parent enrollment approval and rejection notifications.

### 9. Quiz Taking and Assessment Logs
- Implements learner quiz show/start/submit/result/history, instructor quiz management, quiz attempt logs, daily shield limits, assessment insight pages, and quiz activity notifications.
- Evidence: `routes/web.php`, `routes/instructor.php`; `Learner/QuizController`, `Instructor/QuizManagementController`, `Instructor/AssessmentLogController`; views under `resources/views/quizzes`, `resources/views/instructor/quizzes`, `resources/views/instructor/assessments`.
- Data/support: `quizzes`, `quiz_questions`, `quiz_options`, `quiz_attempts`, `user_daily_shields`.
- Services: `InstructorAssessmentInsightsService`.

### 10. Gamification
- Implements learner gamification panel/rules, streak savers, shield refill, admin gamification policy settings, policy history, restore, and reward logs.
- Evidence: `routes/web.php`, `routes/admin.php`; `Learner/GamificationController`, `ShieldRefillController`, `StreakSaverController`, `Admin/GamificationSettingsController`; views under `resources/views/learner/gamification` and `resources/views/admin/gamification`.
- Data/support: `user_gamifications`, `achievements`, `rewards_logs`, `user_daily_shields`, `gamification_policies`, `gamification_policy_versions`.
- Services: `GamificationService`, `GamificationPolicyAdminService`, `GamificationPolicyResolver`, validator/normalizer/default services.

### 11. Certificates
- Implements public certificate verification, learner certificate listing/show/download, authenticated certificate generation/download, certificate PDF rendering, and certificate-issued notifications.
- Evidence: `routes/web.php`; root `CertificateController`, `Learner/CertificateController`; views under `resources/views/learner/certificates`.
- Data/support: `certificates` with snapshot/PDF fields.
- Services: `CertificatePdfService`.
- Notifications: learner and instructor certificate-issued notifications.

### 12. Subscriptions, Plans, Billing, and PayMongo Payments
- Implements learner and instructor subscription browsing/subscribe/cancel/refund/renew/upgrade/status, admin subscriber management, plan CRUD/archive/restore/reorder/impact, payment checkout/history/status/receipt, PayMongo webhooks, invoices, refunds, and subscription lifecycle jobs.
- Evidence: `routes/web.php`, `routes/instructor.php`, `routes/admin.php`; `PaymentController`, `Learner/SubscriptionController`, `Instructor/SubscriptionController`, admin subscriber/plan/payment controllers; views under `resources/views/subscriptions`, `resources/views/payments`, `resources/views/instructor/payments`, `resources/views/admin/{subscriber,subscription-plans,payments}`.
- Data/support: `subscribers` via `Subscription` model, `subscription_plans`, `plan_prices`, `feature_catalog`, `plan_feature_entitlements`, `payments`, `refunds`, `invoices`.
- Services/jobs: `SubscriptionService`, `SubscriptionDunningService`, `PayMongoPaymentLinkService`, `PaymentReceiptService`, `InvoiceService`, `RefundService`, `GenerateInvoiceJob`, payment/subscription email jobs.
- Scheduled commands: `subscriptions:expire` every 15 minutes; `subscriptions:process-renewals` hourly.

### 13. Module Purchases, Monetization, Earnings, and Financial Reporting
- Implements paid module purchase flow, module sale ledger, commission settings, admin module revenue management, instructor earnings with exports, admin financial reports with PDF/CSV/XLSX export, and report generation logs.
- Evidence: `routes/web.php`, `routes/instructor.php`, `routes/admin.php`; `Learner/ModuleController` purchase actions, `Admin/CommissionSettingsController`, `Admin/ModuleRevenueController`, `Admin/FinancialReportController`, `Instructor/ModuleEarningsController`, `Instructor/InstructorFinancialReportExportController`; views under `resources/views/admin/monetization`, `resources/views/admin/financial-reports`, `resources/views/instructor/earnings`.
- Data/support: `module_purchases`, `module_sale_ledgers`, `commission_policies`, `commission_policy_audits`, `instructor_earnings_visibility`, `report_generation_logs`.
- Services: `ModulePurchaseService`, `RevenueSplitCalculator`, `ModuleSaleLedgerService`, `CommissionPolicyResolver`, `FinancialReportService`, export/filter/trend services.

### 14. Instructor Application and Instructor Profiles
- Implements learner-to-instructor application, submit/withdraw/submitted pages, admin review/approve/reject/archive/delete, instructor profiles, role transitions, and backfill command.
- Evidence: `routes/web.php`, `routes/admin.php`, `routes/instructor.php`; `Learner/InstructorApplicationController`, `Admin/InstructorApplicationController`, `Instructor/ProfileController`; views under `resources/views/learner/instructor-application`, `resources/views/admin/instructor-applications`, `resources/views/instructor/profile`.
- Data/support: `instructor_applications`, `instructor_application_reviews`, `instructor_profiles`, `role_transitions`.
- Services/commands: `InstructorApplicationService`, `BackfillInstructorProfileFromApplications`.
- Notifications: instructor application submitted/approved/rejected/status update notifications.

### 15. Parent-Child Management and Verification
- Implements parent onboarding, document temp uploads, child account creation wizard, parent/child verification status and resubmission, admin parent-child verification moderation, parent dashboard for children, child quiz/enrollment visibility, parent-child invitations, and learner "My Parent" visibility.
- Evidence: `routes/auth.php`, `routes/web.php`, `routes/admin.php`; `Auth/ParentRegistrationController`, `ParentController`, `ParentInvitationController`, `Learner/ParentVisibilityController`, `Admin/ParentChildVerificationController`; views under `resources/views/parent`, `resources/views/auth/child`, `resources/views/learner/parent`, `resources/views/admin/parent-verifications`.
- Data/support: `parent_child_accounts`, `parent_child_invitations`, verification fields on users/accounts.
- Policy/services: `ParentChildPolicy`, `ParentChildService`, `ParentChildInvitationService`, `ParentChildVerificationService`.
- Notifications: parent/child verification, parent-child invitation, and child enrollment approval notifications.

### 16. Chat and Message Requests
- Implements chat page, conversation discovery/start/open, message list/send/since/update/delete, read receipts, chat status, message requests accept/decline, attachments, message reports, unread badges, and in-app chat notifications.
- Evidence: `routes/web.php`; controllers under `app/Http/Controllers/Chat`; views under `resources/views/chat`; global popup included in admin/instructor layouts.
- Data/support: `conversations`, `messages`, `message_attachments`, `message_requests`, `conversation_reads`, `message_reports`, chat status fields on `users`.
- Services/events/listeners: `ChatService`, `ChatAuthorizationService`, `ChatContextResolver`, `SupportAdminResolver`, chat events, `SendInAppChatMessageNotification`.
- Notifications: `NewChatMessageNotification`, `MessageReportSubmittedNotification`.

### 17. Content Reports and Centralized Moderation
- Implements learner content reports, chat/message reports, centralized moderation cases, violations, enforcement actions, user suspensions, suspension status, suspension appeals with thread messages, admin suspension/report dashboard, admin appeal review, automation rules/logs, and moderation backfill.
- Evidence: `routes/web.php`, `routes/admin.php`, `bootstrap/app.php`; `Learner/ContentReportController`, `Admin/ModerationSuspensionController`, `Admin/ModerationAppealController`, `Moderation/SuspensionStatusController`, `Moderation/SuspensionAppealController`; views under `resources/views/admin/moderation` and `resources/views/moderation`.
- Data/support: `content_reports`, `content_report_activities`, `message_reports`, `moderation_cases`, `violations`, `enforcement_actions`, `moderation_automation_rules`, `automation_rule_logs`, `user_suspensions`, `suspension_appeals`, `appeal_thread_messages`.
- Middleware/services/jobs: `CheckUserSuspensionStatus`, moderation services/source adapters, `EvaluateAutomationRulesJob`, `BackfillCentralizedModeration`.
- Notifications: enforcement, suspension, appeal submitted/decision, learner report submitted/status, message report submitted.

### 18. Connectors
- Implements connector discovery, registration/withdrawal/status, admin approval/rejection/suspension, connector dashboard, members, removed members, membership requests, invitations/inbox, custom connector roles/permissions, connector subscription screen, and connector navigation.
- Evidence: `routes/connector.php`, admin connector routes in `routes/admin.php`; controllers under `app/Http/Controllers/Connector`; `Admin/ConnectorController`; views under `resources/views/connectors` and `resources/views/admin/connectors`; connector nav in `resources/views/layouts/connector-app.blade.php`.
- Data/support: `connectors`, `connector_roles`, `connector_role_permissions`, `connector_memberships`, `connector_membership_requests`, `connector_invitations`, `connector_reviews`, connector link on `subscribers`.
- Services: `ConnectorAccessService`, `ConnectorEntitlementService`, `ConnectorInvitationService`, `ConnectorMembershipRequestService`, `ConnectorRegistrationService`, `ConnectorRoleService`.
- Notifications: connector application, invitation, membership request, role update, and moderation decision notifications.

### 19. Seminars, Livestreams, Speakers, Attendance, and Interactions
- Implements public/authenticated seminar browsing, registration/cancellation, join, Agora token, attendance join/heartbeat/leave, comments/questions, connector seminar CRUD/review/publish/archive/cancel/complete, speaker assignment/invitations, registrant approval/export, attendance export, livestream host view, and admin seminar moderation.
- Evidence: `routes/web.php`, `routes/connector.php`, `routes/admin.php`, `routes/instructor.php`; seminar controllers at root, connector, admin, and instructor namespaces; views under `resources/views/seminars`, `resources/views/connectors/seminars`, `resources/views/admin/seminars`, `resources/views/instructor/speaker-invitations`.
- Data/support: `seminars`, `seminar_organizations`, `seminar_registrants`, `seminar_speakers`, `seminar_comments`, `seminar_questions`, `seminar_attendances`, `seminar_moderation_reviews`.
- Services: `AgoraTokenService`, `SeminarAccessService`, `SeminarAttendanceService`, `SeminarCategoryService`, `SeminarDiscoveryService`, `SeminarExportService`, `SeminarInteractionService`, `SeminarLifecycleService`, `SeminarRegistrationService`, `SeminarSpeakerEligibilityService`, `SeminarSpeakerService`.
- Notifications: seminar available, cancelled, moderation decision, registration confirmed/rejected, reminder, speaker assigned, speaker invitation responded.

### 20. Notifications
- Implements role-scoped notification centers for learner, instructor, and admin, dropdown read-sync, mark-all-read, deep-link resolution, normalized payload display, and many module-specific notification classes.
- Evidence: notification routes in `routes/web.php`, `routes/instructor.php`, `routes/admin.php`; notification controllers; views under `resources/views/{learner,instructor,admin}/notifications`.
- Data/support: Laravel `notifications` table.
- Services/support: `NotificationReadService`, `NotificationDeepLinkResolver`, `NotificationPayloadNormalizer`.

### 21. Location and PSGC Data
- Implements API endpoints for cities/barangays and vendor PSGC routes for regions/provinces/cities/barangays.
- Evidence: `routes/api.php`, route list PSGC routes, `Api/LocationController`.
- Data/support: `regions`, `provinces`, `cities`, `barangays`.

### 22. Public Landing, Legal Pages, and APK Download
- Implements guest landing page, privacy/terms pages, APK download endpoint, local APK validation, and QR-code endpoint with cache/fallback.
- Evidence: public routes in `routes/web.php`; views under `resources/views/landing` and `resources/views/legal`.
- Support: `config/apk.php` if configured; cache-backed QR generation.

## Cross-Cutting Infrastructure

- Middleware: premium entitlement guard, profile-completed guard, suspension guard, PayMongo webhook verification, Spatie role/permission middleware.
- Policies: module, lesson, topic, quiz, parent-child, instructor profile, admin creator profile.
- Scheduled work: subscription expiration and renewal processing are scheduled; analytics report, scheduled module publishing, billing setup/test, and moderation/instructor backfill commands exist but are not scheduled in `routes/console.php`.
- Events/listeners: payment/subscription lifecycle events and chat events are implemented; payment/subscription listeners handle downstream work.
- Navigation coverage: admin, learner, instructor, and connector sidebars expose the implemented modules above.

## Not Counted as Fully Implemented Modules

These have tables/models or old naming but no complete routed module surface found in this audit:

- Clinics, counselors, consultations, and organizations: migrations/models exist, but no complete routed UI workflow was found.
- Connector "Modules" and "Educators" workspace pages: routes and stub views exist, but current views are under `resources/views/connectors/stubs`, so they are not counted as fully implemented standalone connector submodules.
- `PublishScheduledModules` command: command exists, but scheduled publish columns were removed from modules and no scheduler entry was found.
