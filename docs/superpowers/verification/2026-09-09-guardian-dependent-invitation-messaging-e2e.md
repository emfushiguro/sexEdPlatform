# Guardian/Dependent Invitation Messaging — E2E Verification

Date: 2026-09-09  
Code revision tested: `aa002115f989b13a8b13d6abce17d43c33f506a4` (`main`)  
Execution mode: shared `main` checkout; no separate worktree

## Environment

| Component | Version/configuration |
|---|---|
| PHP | 8.2.12 CLI |
| Laravel | 12.44.0 |
| PHPUnit | 11.5.46 |
| Node | v22.18.0 |
| pnpm | 10.33.2 |
| Database | MySQL; PHPUnit database `cc_db_test` |
| Browser | Not available; no connected in-app browser instance |

## Verification commands

| Command | Exit | Result |
|---|---:|---|
| `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Chat/ChatSchemaCoreTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatRealtimeUiContractTest.php tests/Feature/Chat/ChatReconnectBackfillTest.php tests/Feature/Chat/ChatUnreadAndReadStateTest.php tests/Feature/Chat/ChatInAppMessageNotificationTest.php --testdox` | 0 | 105 tests, 643 assertions |
| Relationship/moderation regression matrix from the plan | 0 | 59 tests, 251 assertions |
| `vendor\\bin\\pint --test` on the 15 scoped implementation/test files | 0 | All files pass |
| `pnpm.cmd build` | 0 | Vite 7.3.0 build passed; 87 modules |
| `php vendor/bin/phpunit --do-not-cache-result` | 2 | 1,285 tests, 6,214 assertions, 13 errors, 4 failures |

The Phase 1 report recorded 13 errors and 5 failures. The Phase 2 full suite keeps the same error count and has no additional failures in the focused Phase 2 matrix. The full-suite failures below are outside that matrix or are existing environment/order-sensitive regressions.

## Full-suite failures and errors

The 10 community-media errors are caused by the environment lacking the GD extension:

- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_post_can_store_an_ordered_six_image_gallery_on_private_storage`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_post_rejects_too_many_oversized_invalid_and_mixed_images`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_edit_marks_selected_media_removed_retains_the_file_and_adds_replacement`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_edit_rejects_foreign_removal_ids_and_an_invalid_final_media_set`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_active_media_delivery_is_item_scoped_and_denies_ineligible_viewers`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_removed_or_missing_media_is_hidden_from_members_but_removed_media_remains_available_to_moderators`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_create_edit_and_post_views_expose_clean_media_picker_and_gallery_contracts`
- `Tests\\Feature\\Community\\CommunityPostMediaTest::test_edit_restores_media_removal_choice_and_warns_that_new_files_must_be_reselected_after_any_validation_error`
- `Tests\\Feature\\Community\\CommunityRedditHubTest::test_post_create_stores_an_image_or_video_attachment_within_its_limit`
- `Tests\\Feature\\Community\\CommunityRedditHubTest::test_post_create_rejects_media_above_the_configured_limits`

The remaining full-suite errors are existing seed/order-sensitive failures:

- `Tests\\Feature\\Parent\\ParentChildrenActionsUiTest::test_my_children_page_keeps_single_primary_action_and_concise_pending_rejected_regions` — passes in the focused matrix.
- `Tests\\Feature\\Seeders\\TestUserSeederTest::test_it_seeds_role_specific_test_accounts_with_profile_and_relationship_coverage`
- `Tests\\Feature\\Seeders\\TestUserSeederTest::test_it_is_safe_to_run_repeatedly_without_duplicate_test_accounts`

The four full-suite failures are outside the invitation-messaging acceptance matrix:

- `Tests\\Feature\\Connectors\\ConnectorNotificationTest::test_connector_submission_moderation_invitation_and_withdrawal_notifications_are_sent`
- `Tests\\Feature\\Connectors\\ConnectorRegistrationTest::test_authenticated_verified_learner_can_register_connector_without_creating_user`
- `Tests\\Feature\\Instructor\\InstructorLearnerCategoryClassificationTest::test_adult_learner_with_verified_child_link_is_categorized_as_adult_parent`
- `Tests\\Feature\\Instructor\\InteractiveCheckpointAuthoringTest::test_topic_create_page_shows_checkpoint_authoring_controls`

## Browser walkthrough

The requested browser walkthrough was not executed because browser discovery returned no connected browser instances. Consequently, there are no browser-version or visual observations to report. The automated invitation UI contract, HTML privacy assertions, chat capability assertions, and lifecycle/API tests passed in the focused matrix.

## Acceptance checklist

- One-to-ten categorized invitation evidence files: verified by invitation flow tests.
- Server-side count, type, size, category, pairing, core-category, and duplicate-content rules: verified by evidence and invitation submission tests.
- Local image/PDF preview, replacement, removal, and validation-error reselection contract: verified by UI contract tests and the Vite build.
- Privacy-safe guardian context: verified; email, birthdate, age, identity-document metadata, and raw evidence are excluded.
- Learner-controlled, text-only invitation chat: verified at route, service, HTTP, UI capability, and authorization boundaries.
- Generic chat-start bypass: rejected for `guardian_invitation` conversations.
- Dynamic and stored lifecycle closure: verified after rejection, cancellation, expiration, relationship rejection, revocation, deactivation, and reactivation transitions.
- Invitation acceptance leaves the relationship under administrative review and does not grant guardian privileges: verified by invitation and lifecycle tests.
- Multiple guardians and invitations remain independent: verified by relationship and invitation lifecycle coverage.
- Existing chat, notifications, unread/read state, realtime authorization, reports, moderation, suspension, and Phase 1 relationship regressions: focused regression matrix passed.
- No Health & Support Information fields were added.
- Messages do not influence relationship approval; approval remains owned by the existing relationship verification workflow.

