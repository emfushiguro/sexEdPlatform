# Guardian–Dependent Existing-Learner Invitation and Messaging Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give an existing learner privacy-safe context and learner-controlled messaging for a guardian invitation while upgrading invitation evidence to one-to-ten files and preserving administrative relationship verification as the only source of guardian privileges.

**Architecture:** Extend the existing invitation aggregate with an explicit relationship link and reuse the existing conversation/message infrastructure through a `guardian_invitation` conversation context. Keep invitation creation and evidence transfer in `ParentChildInvitationService`, centralize conversation policy in `ChatAuthorizationService`, and add one focused service for invitation-conversation creation and lifecycle synchronization.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, Blade, Alpine.js, Tailwind CSS, PHPUnit 11, private Laravel filesystem storage, database notifications, Laravel broadcasting, existing Vite pipeline.

## Global Constraints

- Follow `docs/superpowers/specs/2026-09-09-guardian-dependent-invitation-messaging-design.md`.
- Reuse `ParentChildInvitation`, `ParentChildAccount`, `GuardianRelationshipVerificationService`, `GuardianRelationshipEvidenceService`, `Conversation`, `ChatService`, and existing moderation/notification infrastructure.
- Do not add a public guardian profile, a second messaging system, a new message-request framework, or a temporary-upload subsystem.
- Every selectable relationship requires evidence and administrative review; invitation acceptance never activates guardian privileges.
- Invitation evidence remains private from the learner, instructors, unrelated guardians, and unauthorized administrators.
- Invitation conversations are learner-initiated and text-only.
- Use “administrative verification of submitted evidence”; do not claim legal adjudication.
- Do not add Health & Support Information in Phase 2.
- Do not add dependencies.
- Preserve unrelated worktree changes and generated assets.
- Apply TDD: write a focused failing test, observe the expected failure, implement the smallest change, rerun the focused test, then commit only task files.
- Use `php vendor/bin/phpunit` on Windows because the Phase 1 report records a current-directory failure with `php artisan test`.
- Capture the pre-existing full-suite, Pint, and browser-QA gaps from `docs/superpowers/verification/2026-09-07-guardian-dependent-relationship-verification-e2e.md`; do not silently attribute them to Phase 2.

---

## File Structure

### New files

- `database/migrations/2026_09_09_000001_add_guardian_invitation_context.php` — adds invitation-to-relationship and conversation-to-invitation provenance.
- `app/Services/Chat/GuardianInvitationConversationService.php` — creates the single scoped conversation and synchronizes its stored open/closed status.
- `tests/Feature/Parent/GuardianInvitationMessagingTest.php` — invitation conversation creation, authorization, lifecycle, privacy, and multi-guardian behavior.
- `docs/superpowers/verification/2026-09-09-guardian-dependent-invitation-messaging-e2e.md` — observed commands and browser results.

### Existing files to modify

- `app/Models/ParentChildInvitation.php` — relationship and conversation relations.
- `app/Models/Conversation.php` — invitation context constant, fillable foreign key, relation, and context key.
- `app/Services/ParentChildInvitationService.php` — normalized document collection, duplicate rejection, relationship linkage, safe eager loading, terminal-state synchronization.
- `app/Http/Requests/Parent/SendParentChildInvitationRequest.php` — one-to-ten evidence validation and metadata checks.
- `app/Http/Controllers/ParentInvitationController.php` — collection payload, whitelisted view data, and scoped conversation entry point.
- `app/Support/GuardianRelationshipEvidenceRules.php` — complete front/back pair validation shared by all evidence paths.
- `resources/views/parent/invitations/index.blade.php` — multi-document uploader.
- `resources/views/parent/invitations/history.blade.php` — remove unnecessary personal data.
- `resources/views/parent/invitations/show.blade.php` — privacy-safe guardian context and actions.
- `routes/web.php` — invitation conversation route and policy-backed chat open route.
- `app/Services/Chat/ChatAuthorizationService.php` — view/live/send distinctions and invitation lifecycle policy.
- `app/Services/Chat/ChatService.php` — read-only transcript support and service-layer text-only enforcement.
- `app/Http/Requests/Chat/StartConversationRequest.php` — forbid generic creation of invitation conversations.
- `app/Http/Requests/Chat/SendMessageRequest.php` — require text and prohibit attachments for invitation conversations.
- `app/Http/Controllers/Chat/ConversationController.php` — invitation label and attachment capability.
- `app/Http/Controllers/Chat/MessageController.php` — use view policy for retained transcripts and live policy for polling/mutation.
- `resources/js/chat/store.js` — retain `allows_attachments` and group invitation conversations with direct conversations.
- `resources/views/chat/partials/conversation-panel.blade.php` — hide attachment and voice controls for invitation conversations and show read-only state.
- `routes/channels.php` — continue using the live subscription policy.
- `app/Services/GuardianRelationshipVerificationService.php` — synchronize invitation conversation after relationship transitions.
- `tests/Feature/Parent/ParentChildInvitationFlowTest.php` — collection evidence and accepted relationship link.
- `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php` — front/back completeness regression.
- `tests/Feature/Chat/ChatSchemaCoreTest.php` — provenance schema.
- `tests/Unit/Chat/ChatAuthorizationServiceTest.php` — revoked/suspended and invitation state matrix.
- `tests/Unit/Chat/ChatServiceTest.php` — text-only and retained transcript behavior.
- `tests/Feature/Chat/ChatHttpFlowTest.php` — HTTP send/read/open restrictions.
- `tests/Feature/Chat/ChatChannelAuthorizationTest.php` — live subscription lifecycle.
- `tests/Feature/Chat/ChatPageRenderTest.php` — attachment-free/read-only UI contract.
- `tests/Feature/Chat/ChatRealtimeUiContractTest.php` — serialized capability contract.

---

### Task 1: Close the Phase 1 existing-conversation authorization gap

**Files:**

- Modify: `app/Services/Chat/ChatAuthorizationService.php`
- Modify: `app/Services/Chat/ChatService.php`
- Modify: `app/Http/Controllers/Chat/MessageController.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/Chat/ChatAuthorizationServiceTest.php`
- Test: `tests/Feature/Chat/ChatHttpFlowTest.php`
- Test: `tests/Feature/Chat/ChatChannelAuthorizationTest.php`

**Interfaces:**

- Consumes: `ParentChildAccount::accessEligible()`, `User::STATUS_ACTIVE`, existing participant and admin-support rules.
- Produces: `ChatAuthorizationService::canViewConversation(User, Conversation): bool`, strengthened `canSubscribeToConversation(User, Conversation): bool`, and strengthened `canSendMessage(User, Conversation): bool`.

- [ ] **Step 1: Add a failing unit matrix for an already-open relationship conversation**

Create a verified active Guardian–Dependent relationship and active direct conversation. Assert:

```php
$this->assertTrue($authorization->canViewConversation($guardian, $conversation));
$this->assertTrue($authorization->canSubscribeToConversation($guardian, $conversation));
$this->assertTrue($authorization->canSendMessage($guardian, $conversation));

$relationship->update([
    'relationship_status' => ParentChildAccount::STATUS_REVOKED,
    'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
]);

$this->assertTrue($authorization->canViewConversation($guardian, $conversation->fresh()));
$this->assertFalse($authorization->canSubscribeToConversation($guardian, $conversation->fresh()));
$this->assertFalse($authorization->canSendMessage($guardian, $conversation->fresh()));
```

Add a second assertion set where the guardian status becomes `User::STATUS_SUSPENDED`; view, subscribe, and send must all be false.

- [ ] **Step 2: Run the focused tests and confirm the policy fails**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php --testdox
```

Expected: FAIL because there is no view policy and current subscribe/send checks do not reevaluate account or relationship state.

- [ ] **Step 3: Separate retained-transcript access from live access**

Add the public view method and private account/pair helpers to `ChatAuthorizationService`:

```php
public function canViewConversation(User $user, Conversation $conversation): bool
{
    if ($user->status !== User::STATUS_ACTIVE) {
        return false;
    }

    return $this->isParticipant($user, $conversation)
        || $this->isAdminSupportSharedConversation($user, $conversation);
}

private function participantsAreActive(Conversation $conversation): bool
{
    $conversation->loadMissing(['participantOne:id,status', 'participantTwo:id,status']);

    return $conversation->participantOne?->status === User::STATUS_ACTIVE
        && $conversation->participantTwo?->status === User::STATUS_ACTIVE;
}

private function directParentChildRelationship(Conversation $conversation): ?ParentChildAccount
{
    if ((string) $conversation->conversation_type !== Conversation::TYPE_DIRECT) {
        return null;
    }

    return ParentChildAccount::withTrashed()
        ->where(function ($query) use ($conversation): void {
            $query->where('parent_user_id', $conversation->participant_one_id)
                ->where('child_user_id', $conversation->participant_two_id);
        })
        ->orWhere(function ($query) use ($conversation): void {
            $query->where('parent_user_id', $conversation->participant_two_id)
                ->where('child_user_id', $conversation->participant_one_id);
        })
        ->first();
}
```

Update live and send decisions:

```php
public function canSubscribeToConversation(User $user, Conversation $conversation): bool
{
    if (! $this->canViewConversation($user, $conversation) || ! $this->participantsAreActive($conversation)) {
        return false;
    }

    $relationship = $this->directParentChildRelationship($conversation);

    return $relationship === null
        || (! $relationship->trashed() && $relationship->isVerifiedActive());
}

public function canSendMessage(User $user, Conversation $conversation): bool
{
    return $this->canSubscribeToConversation($user, $conversation)
        && in_array((string) $conversation->status, [
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_ACCEPTED,
        ], true);
}
```

Use grouped closures around both pair directions so the soft-deleted lookup cannot leak constraints across the `OR`.

- [ ] **Step 4: Route transcript reads through `canViewConversation()`**

In `MessageController::index()`, use `canViewConversation()`. Keep `since()` on `canSubscribeToConversation()` because it is live backfill. Use `canSendMessage()` for edit/delete mutations and `canViewConversation()` for reporting. Change `ChatService::markConversationRead()` to use `canViewConversation()`.

Replace the inline participant-only check in `chat.conversation.open` with:

```php
$authorization = app(AppServicesChatChatAuthorizationService::class);
abort_unless($authorization->canViewConversation($request->user(), $conversation), 403);
```

- [ ] **Step 5: Run focused authorization regressions**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php tests/Feature/Chat/ChatReconnectBackfillTest.php --testdox
```

Expected: PASS. Revoked relationship transcripts remain readable to active participants, while send and broadcast subscription fail. Suspended users fail every operation.

- [ ] **Step 6: Commit the prerequisite**

```powershell
git add app/Services/Chat/ChatAuthorizationService.php app/Services/Chat/ChatService.php app/Http/Controllers/Chat/MessageController.php routes/web.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php
git commit -m "fix: recheck guardian chat eligibility"
```

---

### Task 2: Add invitation and conversation provenance

**Files:**

- Create: `database/migrations/2026_09_09_000001_add_guardian_invitation_context.php`
- Modify: `app/Models/ParentChildInvitation.php`
- Modify: `app/Models/Conversation.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Test: `tests/Feature/Chat/ChatSchemaCoreTest.php`
- Test: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`

**Interfaces:**

- Consumes: the relationship returned to `GuardianRelationshipVerificationService::submitStaged()` callbacks.
- Produces: `ParentChildInvitation::parentChildAccount()`, `ParentChildInvitation::conversation()`, `Conversation::parentChildInvitation()`, `Conversation::TYPE_GUARDIAN_INVITATION`, and persisted invitation `parent_child_account_id`.

- [ ] **Step 1: Add failing schema and linkage tests**

Assert both columns exist and acceptance stores the exact relationship ID:

```php
$this->assertTrue(Schema::hasColumn('parent_child_invitations', 'parent_child_account_id'));
$this->assertTrue(Schema::hasColumn('conversations', 'parent_child_invitation_id'));

$invitation = $invitation->fresh();
$relationship = ParentChildAccount::query()
    ->where('parent_user_id', $parent->id)
    ->where('child_user_id', $child->id)
    ->sole();

$this->assertSame($relationship->id, $invitation->parent_child_account_id);
$this->assertTrue($invitation->parentChildAccount->is($relationship));
```

Also assert `Conversation::makeContextKey(Conversation::TYPE_GUARDIAN_INVITATION, 42)` returns `guardian_invitation:42` and the type is supported.

- [ ] **Step 2: Run tests and confirm missing schema/model failures**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Chat/ChatSchemaCoreTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
```

Expected: FAIL on missing columns, constant, and relationship methods.

- [ ] **Step 3: Add the migration**

Create an anonymous migration whose `up()` performs:

```php
Schema::table('parent_child_invitations', function (Blueprint $table): void {
    $table->foreignId('parent_child_account_id')
        ->nullable()
        ->after('child_user_id')
        ->constrained('parent_child_accounts')
        ->nullOnDelete();
});

Schema::table('conversations', function (Blueprint $table): void {
    $table->foreignId('parent_child_invitation_id')
        ->nullable()
        ->after('status')
        ->constrained('parent_child_invitations')
        ->nullOnDelete();
    $table->unique('parent_child_invitation_id', 'conversations_guardian_invitation_unique');
});
```

Its `down()` drops the conversation unique index and constrained foreign ID first, then drops the invitation constrained foreign ID.

- [ ] **Step 4: Add model constants and relations**

Add to `Conversation`:

```php
public const TYPE_GUARDIAN_INVITATION = 'guardian_invitation';

public function parentChildInvitation(): BelongsTo
{
    return $this->belongsTo(ParentChildInvitation::class);
}
```

Add `parent_child_invitation_id` to `$fillable` and the constant to `supportedConversationTypes()`.

Add to `ParentChildInvitation`:

```php
public function parentChildAccount(): BelongsTo
{
    return $this->belongsTo(ParentChildAccount::class);
}

public function conversation(): HasOne
{
    return $this->hasOne(Conversation::class);
}
```

Add `parent_child_account_id` to `$fillable` and import `HasOne`.

- [ ] **Step 5: Persist the relationship link inside acceptance**

Change both acceptance callbacks to accept the locked submitted relationship and update the invitation atomically:

```php
function (ParentChildAccount $submittedRelationship) use ($invitation, $decisionNote): void {
    $invitation->update([
        'parent_child_account_id' => $submittedRelationship->id,
        'status' => ParentChildInvitationStatus::Accepted->value,
        'decision_note' => $decisionNote,
        'responded_at' => now(),
        'relationship_verification_documents' => null,
    ]);
}
```

Keep `parent_child_account_id` null on reject, cancel, and expiry.

- [ ] **Step 6: Run tests and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Chat/ChatSchemaCoreTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
git add database/migrations/2026_09_09_000001_add_guardian_invitation_context.php app/Models/ParentChildInvitation.php app/Models/Conversation.php app/Services/ParentChildInvitationService.php tests/Feature/Chat/ChatSchemaCoreTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php
git commit -m "feat: link guardian invitations to chat context"
```

Expected: PASS and only the listed task files are committed.

---

### Task 3: Accept one-to-ten invitation evidence files server-side

**Files:**

- Modify: `app/Http/Requests/Parent/SendParentChildInvitationRequest.php`
- Modify: `app/Http/Controllers/ParentInvitationController.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Modify: `app/Support/GuardianRelationshipEvidenceRules.php`
- Test: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`
- Test: `tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php`

**Interfaces:**

- Consumes: `GuardianRelationshipEvidenceRules::for(array $acceptedTypes): array`, `GuardianRelationshipTypes::requiredDocumentTypes(?string $type): array`, and the existing `documents` normalization path.
- Produces: validated `documents[]` rows containing `document_type`, `document_side`, nullable `pairing_key`, `file`, and service-assigned `display_order`.

- [ ] **Step 1: Replace old test payloads and add boundary tests**

Use the collection shape in invitation tests:

```php
'documents' => [
    [
        'document_type' => 'court_order',
        'document_side' => 'not_applicable',
        'pairing_key' => null,
        'file' => UploadedFile::fake()->create('court-order.pdf', 64, 'application/pdf'),
    ],
],
'confirm_relationship_verification' => '1',
```

Add tests for one accepted file, ten accepted files, zero files, eleven files, an unsupported category, an invalid side, an incomplete front/back pair, a repeated side, and duplicate file content. Assert invalid submissions create no invitation and leave `Storage::disk('local')->allFiles()` empty.

- [ ] **Step 2: Run tests and confirm the fixed-field request fails**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php --testdox
```

Expected: FAIL because the invitation request still expects `relationship_document` and `relationship_document_type`.

- [ ] **Step 3: Reuse shared evidence rules in the invitation request**

Replace the fixed file rules with:

```php
return array_merge(
    GuardianRelationshipEvidenceRules::for(
        GuardianRelationshipTypes::acceptedDocumentTypes($relationshipType),
    ),
    [
        'identifier' => ['required', 'string', 'max:255'],
        'relationship_type' => ['required', Rule::in(GuardianRelationshipTypes::selectableValues())],
        'relationship_custom' => ['nullable', 'required_if:relationship_type,other', 'string', 'max:120'],
        'message' => ['nullable', 'string', 'max:500'],
        'confirm_relationship_verification' => ['accepted'],
    ],
);
```

Add an `after(): array` callback that appends `metadataErrors()`, collects submitted types, and requires at least one configured core category using the same wording as `StoreGuardianRelationshipVerificationRequest`.

- [ ] **Step 4: Require complete front/back pairs in the shared metadata validator**

After collecting `$pairSides`, add:

```php
foreach ($pairSides as $pairingKey => $sides) {
    if (! isset($sides['front'], $sides['back'])) {
        $errors['documents'] = 'Every front/back document pair must include both sides.';
        break;
    }
}
```

Retain the existing UUID, same-category, and no-duplicate-side checks. Run the direct evidence tests because this shared rule affects registration and resubmission too.

- [ ] **Step 5: Pass normalized rows through the controller**

Build the service payload from validated data:

```php
$documents = collect($request->validated('documents'))
    ->values()
    ->map(static fn (array $document, int $index): array => [
        'document_type' => (string) $document['document_type'],
        'document_side' => (string) $document['document_side'],
        'pairing_key' => filled($document['pairing_key'] ?? null)
            ? (string) $document['pairing_key']
            : null,
        'display_order' => $index,
        'file' => $document['file'],
    ])
    ->all();
```

Pass `['documents' => $documents]` to `sendInvitation()` and delete the old primary/supporting adaptation from the controller and request.

- [ ] **Step 6: Reject duplicate content during staging**

In the existing staging map, track hashes after `stagedDocument()` returns. If a hash has already been seen, throw:

```php
throw new InvalidArgumentException('Duplicate evidence files are not allowed.');
```

Allow the existing outer catch to remove every staged path. Do not compare filenames as the server security check.

- [ ] **Step 7: Run focused tests and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php --testdox
git add app/Http/Requests/Parent/SendParentChildInvitationRequest.php app/Http/Controllers/ParentInvitationController.php app/Services/ParentChildInvitationService.php app/Support/GuardianRelationshipEvidenceRules.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php
git commit -m "feat: accept multiple invitation evidence files"
```

Expected: PASS with one-to-ten files and complete pairing enforced across all evidence entry points.

---

### Task 4: Build the multi-document invitation uploader

**Files:**

- Modify: `resources/views/parent/invitations/index.blade.php`
- Test: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`
- Test: `tests/Feature/Parent/ParentChildrenActionsUiTest.php`

**Interfaces:**

- Consumes: `documents[index][document_type|document_side|pairing_key|file]`, relationship-specific category maps, maximum ten rows.
- Produces: accessible browser-local add, add-pair, preview, replace, and remove controls without uploading before form submission.

- [ ] **Step 1: Add failing UI contract assertions**

Assert the invitation center contains:

```php
->assertSee('Add document')
->assertSee('Add front/back pair')
->assertSee('documents[', false)
->assertSee('Remove document')
->assertSee('Replace file')
->assertSee('Maximum 10 files')
->assertDontSee('relationship_document_type', false)
->assertDontSee('relationship_supporting_document', false);
```

Also assert validation errors for `documents.0.file` render near the collection.

- [ ] **Step 2: Run the UI tests and confirm failure**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
```

Expected: FAIL because the view still renders two fixed file inputs.

- [ ] **Step 3: Replace the fixed uploader with one Alpine component**

Initialize the form with a component whose state and methods are fully local:

```html
x-data="guardianInvitationEvidence({
    oldRows: @js(old('documents', [])),
    documentTypeMap: @js($relationshipDocumentTypeMap),
    maxFiles: 10
})"
```

Define `guardianInvitationEvidence` once in the view's scripts stack with these concrete operations:

```javascript
window.guardianInvitationEvidence = ({ oldRows, documentTypeMap, maxFiles }) => ({
    relationshipType: @js(old('relationship_type', '')),
    documentTypeMap,
    maxFiles,
    rows: [],
    init() {
        const restored = Array.isArray(oldRows) ? oldRows : [];
        this.rows = restored.length > 0
            ? restored.map((row) => this.makeRow(row))
            : [this.makeRow()];
    },
    makeRow(row = {}) {
        return {
            key: crypto.randomUUID(),
            document_type: String(row.document_type || ''),
            document_side: String(row.document_side || 'not_applicable'),
            pairing_key: row.pairing_key ? String(row.pairing_key) : '',
            file: null,
            fileName: 'No file selected',
            previewUrl: null,
            previewKind: null,
        };
    },
    addDocument() {
        if (this.rows.length < this.maxFiles) this.rows.push(this.makeRow());
    },
    addPair() {
        if (this.rows.length > this.maxFiles - 2) return;
        const pairingKey = crypto.randomUUID();
        this.rows.push(this.makeRow({ document_side: 'front', pairing_key: pairingKey }));
        this.rows.push(this.makeRow({ document_side: 'back', pairing_key: pairingKey }));
    },
    chooseFile(index, event) {
        const file = event.target.files?.[0] || null;
        this.revokePreview(this.rows[index]);
        this.rows[index].file = file;
        this.rows[index].fileName = file?.name || 'No file selected';
        this.rows[index].previewKind = file?.type === 'application/pdf'
            ? 'pdf'
            : (file?.type || '').startsWith('image/') ? 'image' : null;
        this.rows[index].previewUrl = file ? URL.createObjectURL(file) : null;
    },
    removeDocument(index) {
        this.revokePreview(this.rows[index]);
        this.rows.splice(index, 1);
        if (this.rows.length === 0) this.rows.push(this.makeRow());
    },
    revokePreview(row) {
        if (row?.previewUrl) URL.revokeObjectURL(row.previewUrl);
    },
    duplicateWarning(index) {
        const row = this.rows[index];
        if (!row?.file) return '';
        return this.rows.some((candidate, candidateIndex) => candidateIndex !== index
            && candidate.file
            && candidate.file.name === row.file.name
            && candidate.file.size === row.file.size
            && candidate.file.lastModified === row.file.lastModified)
            ? 'This appears to duplicate another selected file.'
            : '';
    },
    destroy() {
        this.rows.forEach((row) => this.revokePreview(row));
    },
});
```

Render row names from the current index, category options from `documentTypeMap[relationshipType]`, hidden pairing key inputs, image/PDF preview branches, and buttons with `type="button"`. Use `aria-live="polite"` for duplicate and file-selection feedback. Label every file control with its row number and side.

- [ ] **Step 4: Explain file reselection after server validation**

Render this message whenever document validation fails:

```blade
@if($errors->has('documents') || $errors->has('documents.*'))
    <p class="mt-2 text-xs text-amber-800" role="status">
        Your document categories were restored. For your security, please reselect the files before submitting again.
    </p>
@endif
```

Use Laravel's error bag iteration to show nested document errors. Do not persist browser object URLs or file contents.

- [ ] **Step 5: Run UI/build checks and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
pnpm.cmd build
git add resources/views/parent/invitations/index.blade.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php
git commit -m "feat: add invitation evidence collection UI"
```

Expected: tests and Vite build pass. Do not stage generated `public/build` changes unless repository policy explicitly requires them.

---

### Task 5: Render a privacy-safe invitation identity view

**Files:**

- Modify: `app/Http/Controllers/ParentInvitationController.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Modify: `resources/views/parent/invitations/show.blade.php`
- Modify: `resources/views/parent/invitations/index.blade.php`
- Modify: `resources/views/parent/invitations/history.blade.php`
- Modify: `app/Notifications/Learner/ParentChildInvitationReceivedNotification.php`
- Test: `tests/Feature/Parent/GuardianInvitationMessagingTest.php`
- Test: `tests/Feature/Parent/ParentChildInvitationFlowTest.php`

**Interfaces:**

- Consumes: existing `User`, `LearnerProfile`, invitation, and guardian identity status fields.
- Produces: whitelisted `$guardianSummary` and `$learnerSummary` arrays; no public guardian profile endpoint.

- [ ] **Step 1: Write failing privacy and presentation tests**

As the invited learner, request the invitation detail and assert:

```php
$response->assertOk()
    ->assertSee($parent->name)
    ->assertSee('Guardian identity administratively verified')
    ->assertSee('Claimed relationship')
    ->assertSee('View Guardian Information')
    ->assertSee('Message Guardian')
    ->assertDontSee($parent->email)
    ->assertDontSee((string) $parent->birthdate)
    ->assertDontSee('relationship_verification_documents');
```

As the guardian, assert the learner's email, birthdate, and age are absent while name, avatar fallback, and username remain. Assert an unrelated user receives 403.

- [ ] **Step 2: Run the new test and confirm data exposure**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php --testdox
```

Expected: FAIL because the current detail view displays both email addresses and learner age and lacks the new context/actions.

- [ ] **Step 3: Restrict eager-loaded columns**

Change invitation detail loading to:

```php
$invitation->load([
    'inviterParent:id,name,status,parent_verification_status,created_at',
    'inviterParent.learnerProfile:id,user_id,avatar_path',
    'child:id,name,status',
    'child.learnerProfile:id,user_id,username,avatar_path',
    'parentChildAccount:id,parent_user_id,child_user_id,relationship_status,relationship_verified_status',
    'conversation:id,parent_child_invitation_id,status',
]);
```

Remove `email` and `birthdate` from outgoing/incoming invitation eager loads unless they are required internally for identifier resolution; identifier resolution remains a separate query.

- [ ] **Step 4: Build explicit summaries in the controller**

Pass arrays containing only:

```php
$guardianSummary = [
    'name' => (string) ($invitation->inviterParent?->name ?: 'Guardian'),
    'avatar_path' => $invitation->inviterParent?->learnerProfile?->avatar_path,
    'identity_verified' => $invitation->inviterParent?->parent_verification_status === 'approved',
    'member_since' => $invitation->inviterParent?->created_at?->format('F Y'),
];

$learnerSummary = [
    'name' => (string) ($invitation->child?->name ?: 'Learner'),
    'avatar_path' => $invitation->child?->learnerProfile?->avatar_path,
    'username' => $invitation->child?->learnerProfile?->username,
];
```

Also pass booleans for the existing viewer roles and message-action visibility. Do not pass identity or evidence models.

- [ ] **Step 5: Replace invitation detail identity cards**

Use the summary arrays for avatar/name presentation, label the invitation relationship as “Claimed relationship,” and add disclosure copy:

```text
This information is shown so you can understand who requested the connection. Accepting sends the relationship evidence for administrative review and does not grant guardian access immediately.
```

Implement “View Guardian Information” as an Alpine disclosure with a real button, `aria-expanded`, `aria-controls`, escape handling, and focus returned to the trigger. Show only the whitelisted fields.

Delete the age calculation and every email rendering from the detail, outgoing cards, and history.

- [ ] **Step 6: Verify notification privacy**

Keep the received-notification payload to invitation ID, guardian display name, claimed relationship label, status, date, and safe URL. Add a test that serialized notification data excludes email, avatar storage path, evidence metadata, identity fields, and message contents.

- [ ] **Step 7: Run focused tests and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox
git add app/Http/Controllers/ParentInvitationController.php app/Services/ParentChildInvitationService.php resources/views/parent/invitations/show.blade.php resources/views/parent/invitations/index.blade.php resources/views/parent/invitations/history.blade.php app/Notifications/Learner/ParentChildInvitationReceivedNotification.php tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php
git commit -m "feat: show privacy-safe guardian invitation context"
```

Expected: PASS with no unnecessary personal or evidence data in HTML or notification payloads.

---

### Task 6: Create the learner-controlled invitation conversation

**Files:**

- Create: `app/Services/Chat/GuardianInvitationConversationService.php`
- Modify: `app/Services/Chat/ChatAuthorizationService.php`
- Modify: `app/Http/Controllers/ParentInvitationController.php`
- Modify: `app/Http/Requests/Chat/StartConversationRequest.php`
- Modify: `routes/web.php`
- Modify: `resources/views/parent/invitations/show.blade.php`
- Test: `tests/Feature/Parent/GuardianInvitationMessagingTest.php`

**Interfaces:**

- Consumes: `ChatAuthorizationService::canInitiateGuardianInvitationConversation(User, ParentChildInvitation): bool`.
- Produces: `GuardianInvitationConversationService::createOrGet(User, ParentChildInvitation): Conversation` and `ParentInvitationController::conversation(Request, ParentChildInvitation): RedirectResponse`.

- [ ] **Step 1: Add failing creation and denial tests**

Cover:

- invited learner creates the conversation while pending;
- participants match guardian and learner in normalized ID order;
- context key and invitation foreign key are correct;
- a repeated click returns the same conversation;
- guardian cannot create it before learner initiation;
- unrelated learner and administrator receive 403;
- rejected, cancelled, and expired invitations receive 409 or 403 without a conversation;
- generic `/chat/conversations/start` rejects `guardian_invitation`.

Core assertion:

```php
$response = $this->actingAs($child)
    ->post(route('parent.invitations.conversation', $invitation));

$conversation = Conversation::query()->sole();
$response->assertRedirect(route('chat.conversation.open', $conversation));
$this->assertSame(Conversation::TYPE_GUARDIAN_INVITATION, $conversation->conversation_type);
$this->assertSame($invitation->id, $conversation->parent_child_invitation_id);
$this->assertSame('guardian_invitation:'.$invitation->id, $conversation->context_key);
```

- [ ] **Step 2: Run the new tests and confirm route/service failures**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php --testdox
```

Expected: FAIL because the route and service do not exist.

- [ ] **Step 3: Add the initiation policy**

Add:

```php
public function canInitiateGuardianInvitationConversation(User $actor, ParentChildInvitation $invitation): bool
{
    if ($actor->status !== User::STATUS_ACTIVE
        || (int) $actor->id !== (int) $invitation->child_user_id
        || $invitation->isExpired()) {
        return false;
    }

    return $invitation->status === ParentChildInvitationStatus::Pending;
}
```

Task 7 expands this method to allow the invited learner to create the conversation after acceptance while the linked relationship remains in an allowed review state. Returning an already-existing conversation is handled before this initiation check.

- [ ] **Step 4: Implement the focused creation service**

`createOrGet()` must lock and reload the invitation, return an already-existing conversation to either participant, require the learner initiation policy when none exists, normalize participant IDs, and use:

```php
return Conversation::query()->firstOrCreate(
    ['parent_child_invitation_id' => $locked->id],
    [
        'participant_one_id' => $participantIds[0],
        'participant_two_id' => $participantIds[1],
        'pair_key' => Conversation::makePairKey($participantIds[0], $participantIds[1]),
        'conversation_type' => Conversation::TYPE_GUARDIAN_INVITATION,
        'status' => Conversation::STATUS_ACTIVE,
        'context_key' => Conversation::makeContextKey(
            Conversation::TYPE_GUARDIAN_INVITATION,
            $locked->id,
        ),
    ],
);
```

Use a database transaction and the unique foreign key as the race-safe final guard. Verify an existing conversation's participant IDs match the invitation before returning it; mismatches throw `AuthorizationException`.

- [ ] **Step 5: Add the scoped controller route and button**

Add inside the existing invitation route group:

```php
Route::post('/invitations/{invitation}/conversation', [ParentInvitationController::class, 'conversation'])
    ->name('invitations.conversation');
```

Inject the new service into `ParentInvitationController`, call `createOrGet()`, translate authorization failures to 403 and invalid lifecycle to a validation error, then redirect to `chat.conversation.open`.

Render “Message Guardian” for the child while initiation is allowed and “Open Conversation” for either party after a conversation exists.

- [ ] **Step 6: Block the generic start endpoint**

In `StartConversationRequest`, add:

```php
Rule::notIn([Conversation::TYPE_GUARDIAN_INVITATION]),
```

The scoped invitation route is the only creation path.

- [ ] **Step 7: Run focused tests and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Chat/ChatHttpFlowTest.php --testdox
git add app/Services/Chat/GuardianInvitationConversationService.php app/Services/Chat/ChatAuthorizationService.php app/Http/Controllers/ParentInvitationController.php app/Http/Requests/Chat/StartConversationRequest.php routes/web.php resources/views/parent/invitations/show.blade.php tests/Feature/Parent/GuardianInvitationMessagingTest.php
git commit -m "feat: add learner-controlled invitation chat"
```

Expected: PASS with one conversation per invitation and no generic start bypass.

---

### Task 7: Enforce invitation lifecycle authorization on every chat path

**Files:**

- Modify: `app/Services/Chat/ChatAuthorizationService.php`
- Modify: `app/Services/Chat/ChatService.php`
- Modify: `app/Http/Controllers/Chat/ConversationController.php`
- Modify: `app/Http/Controllers/Chat/MessageController.php`
- Modify: `routes/channels.php`
- Test: `tests/Unit/Chat/ChatAuthorizationServiceTest.php`
- Test: `tests/Unit/Chat/ChatServiceTest.php`
- Test: `tests/Feature/Chat/ChatHttpFlowTest.php`
- Test: `tests/Feature/Chat/ChatChannelAuthorizationTest.php`

**Interfaces:**

- Consumes: invitation status, expiry, linked `ParentChildAccount`, account status, participants, and stored conversation status.
- Produces: `ChatAuthorizationService::invitationRelationshipAllowsMessaging(ParentChildInvitation): bool` and invitation-aware view/subscribe/send decisions.

- [ ] **Step 1: Add the lifecycle policy matrix as failing tests**

Assert:

- pending, unexpired invitation: view/subscribe/send true after learner creation;
- accepted plus relationship pending, under review, or resubmission required: true;
- accepted plus verified active relationship: true;
- accepted without a relationship link: view true, subscribe/send false;
- final rejection, revocation, inactivity, deleted relationship: view true, subscribe/send false;
- rejected/cancelled/expired invitation: view true, subscribe/send false;
- mismatched participants: all false;
- suspended/inactive/archived actor: all false;
- unrelated active user: all false.

- [ ] **Step 2: Run the policy tests and observe lifecycle failures**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php --testdox
```

Expected: FAIL because invitation conversations are not handled by live authorization.

- [ ] **Step 3: Implement relationship and invitation lifecycle checks**

Add:

```php
public function invitationRelationshipAllowsMessaging(ParentChildInvitation $invitation): bool
{
    $invitation->loadMissing('parentChildAccount');
    $relationship = $invitation->parentChildAccount;

    if (! $relationship instanceof ParentChildAccount || $relationship->trashed()) {
        return false;
    }

    if ($relationship->isVerifiedActive()) {
        return true;
    }

    return $relationship->relationship_status === ParentChildAccount::STATUS_PENDING
        && in_array($relationship->relationship_verified_status, [
            ParentChildAccount::VERIFICATION_PENDING,
            ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            ParentChildAccount::VERIFICATION_RESUBMISSION_REQUIRED,
        ], true);
}
```

Add the state-only wrapper used by conversations and lifecycle synchronization:

```php
public function invitationAllowsLiveMessaging(ParentChildInvitation $invitation): bool
{
    if ($invitation->isExpired()) {
        return false;
    }

    if ($invitation->status === ParentChildInvitationStatus::Pending) {
        return true;
    }

    return $invitation->status === ParentChildInvitationStatus::Accepted
        && $this->invitationRelationshipAllowsMessaging($invitation);
}
```

Expand `canInitiateGuardianInvitationConversation()` so its final return uses `invitationAllowsLiveMessaging($invitation)`. Add a private conversation checker that verifies the invitation FK, participant pair, both active accounts, unexpired status, and the state-only wrapper. Use it from `canSubscribeToConversation()` before the ordinary direct-pair logic. `canViewConversation()` permits active participants to retain the transcript but denies mismatches and missing actor status.

- [ ] **Step 4: Apply the correct policy to each endpoint**

Use:

- `canViewConversation()` for paginated message history, mark-read, and report;
- `canSubscribeToConversation()` for broadcast authorization and `/messages/since/...`;
- `canSendMessage()` for send, edit, and delete;
- `canSendMessage()` when serializing `can_send` in the conversation list;
- `canViewConversation()` for `chat.conversation.open`.

Keep admin-support shared-conversation behavior unchanged. Do not grant relationship-review administrators participant access to private invitation messages.

- [ ] **Step 5: Add the invitation context label and capability**

In `ConversationController::buildContextLabel()` add:

```php
Conversation::TYPE_GUARDIAN_INVITATION => 'Guardian Invitation',
```

Serialize:

```php
'allows_attachments' => $conversation->conversation_type !== Conversation::TYPE_GUARDIAN_INVITATION,
```

- [ ] **Step 6: Run all server chat regressions and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php tests/Feature/Chat/ChatReconnectBackfillTest.php tests/Feature/Chat/ChatUnreadAndReadStateTest.php --testdox
git add app/Services/Chat/ChatAuthorizationService.php app/Services/Chat/ChatService.php app/Http/Controllers/Chat/ConversationController.php app/Http/Controllers/Chat/MessageController.php routes/channels.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php
git commit -m "feat: enforce invitation chat lifecycle"
```

Expected: PASS. Closed relationships retain authorized history but cannot send, poll live backfill, or subscribe.

---

### Task 8: Make invitation conversations text-only in API and UI

**Files:**

- Modify: `app/Http/Requests/Chat/SendMessageRequest.php`
- Modify: `app/Services/Chat/ChatService.php`
- Modify: `resources/js/chat/store.js`
- Modify: `resources/views/chat/partials/conversation-panel.blade.php`
- Test: `tests/Unit/Chat/ChatServiceTest.php`
- Test: `tests/Feature/Chat/ChatHttpFlowTest.php`
- Test: `tests/Feature/Chat/ChatPageRenderTest.php`
- Test: `tests/Feature/Chat/ChatRealtimeUiContractTest.php`

**Interfaces:**

- Consumes: route-bound `Conversation` and serialized `allows_attachments`.
- Produces: required text plus prohibited attachments for invitation conversations; unchanged attachments elsewhere.

- [ ] **Step 1: Add failing request, service, and UI tests**

Assert an invitation conversation:

- accepts a non-empty text message;
- rejects attachment-only requests;
- rejects mixed text and attachment requests;
- rejects direct service calls with an attachment;
- serializes `allows_attachments: false`;
- hides attach and voice-note controls;
- shows a read-only explanation when `can_send` is false.

Assert a normal direct conversation still accepts existing attachment types.

- [ ] **Step 2: Run focused tests and confirm attachment bypasses**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatRealtimeUiContractTest.php --testdox
```

Expected: FAIL because the current form request and service allow attachments for all conversation types.

- [ ] **Step 3: Branch request rules on the route-bound conversation**

At the start of `rules()` resolve:

```php
$conversation = $this->route('conversation');
$invitationTextOnly = $conversation instanceof Conversation
    && $conversation->conversation_type === Conversation::TYPE_GUARDIAN_INVITATION;
```

For invitation conversations return:

```php
return [
    'message_body' => ['required', 'string', 'max:5000'],
    'attachments' => ['prohibited'],
    'retry_of' => ['nullable', 'string', 'max:100'],
];
```

Return the current attachment-capable rules unchanged for every other type.

- [ ] **Step 4: Add service-layer defense**

Before persistence in `ChatService::sendMessage()`:

```php
if ($conversation->conversation_type === Conversation::TYPE_GUARDIAN_INVITATION
    && $uploadedFiles !== []) {
    throw new InvalidArgumentException('Guardian invitation conversations support text messages only.');
}
```

This prevents internal callers from bypassing the HTTP request.

- [ ] **Step 5: Carry the capability through the store**

Add to normalized conversations in `resources/js/chat/store.js`:

```javascript
allows_attachments: conversation.allows_attachments !== false,
```

Treat `guardian_invitation` as a direct-list conversation:

```javascript
if (['direct', 'guardian_invitation'].includes(conversation.conversation_type)) {
    groups.direct.push(conversation);
    return;
}
```

- [ ] **Step 6: Hide unsupported composer controls**

Wrap the queued-attachment area, file input, attach button, and voice-note button with an invitation capability condition. Clear queued attachments when a newly selected conversation has `allows_attachments === false`. Keep the text composer and report actions.

When `can_send` is false, render:

```html
<p class="text-xs text-gray-600" role="status">
    This guardian invitation conversation is read-only because the invitation or relationship is no longer active.
</p>
```

- [ ] **Step 7: Run tests/build and commit**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatRealtimeUiContractTest.php --testdox
pnpm.cmd build
git add app/Http/Requests/Chat/SendMessageRequest.php app/Services/Chat/ChatService.php resources/js/chat/store.js resources/views/chat/partials/conversation-panel.blade.php tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatRealtimeUiContractTest.php
git commit -m "feat: keep invitation conversations text only"
```

Expected: PASS with existing attachment-capable chats unchanged.

---

### Task 9: Synchronize stored conversation state after lifecycle changes

**Files:**

- Modify: `app/Services/Chat/GuardianInvitationConversationService.php`
- Modify: `app/Services/ParentChildInvitationService.php`
- Modify: `app/Services/GuardianRelationshipVerificationService.php`
- Test: `tests/Feature/Parent/GuardianInvitationMessagingTest.php`
- Test: `tests/Feature/GuardianRelationshipLifecycleTest.php`

**Interfaces:**

- Produces: `GuardianInvitationConversationService::syncForInvitation(int $invitationId): void` and `syncForRelationship(int $relationshipId): void`.
- Consumes: dynamic lifecycle decision from `ChatAuthorizationService`; database `afterCommit` callbacks.

- [ ] **Step 1: Add failing transition tests**

Create an invitation conversation, then separately exercise:

- invitation rejection;
- guardian cancellation;
- invitation expiration;
- final relationship rejection;
- revocation;
- deactivation;
- resubmission or reactivation request;
- approval.

Assert terminal states store `Conversation::STATUS_CLOSED`; pending/under-review/verified allowed states store `STATUS_ACTIVE`. In every case also assert the dynamic send policy, because stored status is not the security boundary.

- [ ] **Step 2: Run lifecycle tests and confirm status remains stale**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/GuardianRelationshipLifecycleTest.php --testdox
```

Expected: FAIL because conversation rows are not synchronized after transitions.

- [ ] **Step 3: Implement idempotent synchronization**

Add:

```php
public function syncForInvitation(int $invitationId): void
{
    $invitation = ParentChildInvitation::query()
        ->with(['parentChildAccount', 'conversation'])
        ->find($invitationId);

    if (! $invitation?->conversation) {
        return;
    }

    $allowed = $this->authorization->invitationAllowsLiveMessaging($invitation);
    $status = $allowed ? Conversation::STATUS_ACTIVE : Conversation::STATUS_CLOSED;

    if ((string) $invitation->conversation->status !== $status) {
        $invitation->conversation->forceFill(['status' => $status])->save();
    }
}

public function syncForRelationship(int $relationshipId): void
{
    ParentChildInvitation::query()
        ->where('parent_child_account_id', $relationshipId)
        ->pluck('id')
        ->each(fn (int $invitationId) => $this->syncForInvitation($invitationId));
}
```

Expose a state-only `invitationAllowsLiveMessaging(ParentChildInvitation): bool` method from `ChatAuthorizationService`; participant/account checks remain in actor-specific methods.

- [ ] **Step 4: Schedule synchronization only after committed transitions**

After reject, cancel, expiry, and successful acceptance, register:

```php
DB::afterCommit(fn () => $this->invitationConversations->syncForInvitation((int) $invitation->id));
```

After submit, resubmit, approve, final reject, revoke, deactivate, and reactivation request, register:

```php
DB::afterCommit(fn () => $this->invitationConversations->syncForRelationship((int) $locked->id));
```

Inject `GuardianInvitationConversationService` into both lifecycle services. Do not synchronize before commit and do not emit duplicate relationship notifications.

- [ ] **Step 5: Run lifecycle and notification regressions**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Chat/ChatInAppMessageNotificationTest.php --testdox
```

Expected: PASS with idempotent status updates and unchanged notification counts.

- [ ] **Step 6: Commit synchronization**

```powershell
git add app/Services/Chat/GuardianInvitationConversationService.php app/Services/ParentChildInvitationService.php app/Services/GuardianRelationshipVerificationService.php tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/GuardianRelationshipLifecycleTest.php
git commit -m "feat: close invitation chat on terminal states"
```

---

### Task 10: Complete Phase 2 integration, privacy, and browser QA

**Files:**

- Modify: relevant Phase 2 tests only when an observed integration defect requires a correction
- Create: `docs/superpowers/verification/2026-09-09-guardian-dependent-invitation-messaging-e2e.md`

**Interfaces:**

- Consumes: all Phase 2 behavior and the Phase 1 verification baseline.
- Produces: reproducible automated and browser verification evidence with exact revision, environment, commands, results, and accepted unrelated failures.

- [ ] **Step 1: Run the focused Phase 2 matrix**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Chat/ChatSchemaCoreTest.php tests/Unit/Chat/ChatAuthorizationServiceTest.php tests/Unit/Chat/ChatServiceTest.php tests/Feature/Chat/ChatHttpFlowTest.php tests/Feature/Chat/ChatChannelAuthorizationTest.php tests/Feature/Chat/ChatPageRenderTest.php tests/Feature/Chat/ChatRealtimeUiContractTest.php tests/Feature/Chat/ChatReconnectBackfillTest.php tests/Feature/Chat/ChatUnreadAndReadStateTest.php tests/Feature/Chat/ChatInAppMessageNotificationTest.php --testdox
```

Expected: PASS with zero failures and errors.

- [ ] **Step 2: Run relationship, moderation, and invitation regressions**

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/GuardianRelationshipTypesTest.php tests/Feature/GuardianRelationshipSchemaTest.php tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/ParentChildMonitoringTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php tests/Feature/Admin/Moderation/UserSuspensionLifecycleTest.php --testdox
```

Expected: PASS. If a test is already modified in the user's worktree, inspect and preserve that work before resolving any overlap.

- [ ] **Step 3: Run formatting, build, and the full suite**

```powershell
vendor\bin\pint --test app/Models/ParentChildInvitation.php app/Models/Conversation.php app/Services/ParentChildInvitationService.php app/Services/GuardianRelationshipVerificationService.php app/Services/Chat/GuardianInvitationConversationService.php app/Services/Chat/ChatAuthorizationService.php app/Services/Chat/ChatService.php app/Http/Requests/Parent/SendParentChildInvitationRequest.php app/Http/Requests/Chat/StartConversationRequest.php app/Http/Requests/Chat/SendMessageRequest.php app/Http/Controllers/ParentInvitationController.php app/Http/Controllers/Chat/ConversationController.php app/Http/Controllers/Chat/MessageController.php app/Support/GuardianRelationshipEvidenceRules.php
pnpm.cmd build
php vendor/bin/phpunit --do-not-cache-result
```

Expected: Phase 2 files pass Pint; Vite build passes; full-suite result is compared with the Phase 1 baseline of 13 errors and 5 failures. Any new failure is fixed before completion. Existing failures must be listed individually rather than described generically.

- [ ] **Step 4: Perform the browser walkthrough**

Use a disposable database and non-sensitive fake files. Record observed results for:

1. Guardian selects biological parent, adds documents, previews, removes, replaces, and submits.
2. Adoptive parent adds several documents and a front/back pair; missing back is rejected.
3. Non-parent caregiver sees flexible categories and submits context/evidence.
4. Learner sees avatar, name, identity badge, claimed relationship, date, and message, with no email, birthdate, age, identity document, or evidence.
5. Keyboard user opens and closes Guardian Information and returns focus to the trigger.
6. Learner opens Message Guardian; guardian can reply only after this action.
7. Attachment and voice controls are absent; direct API attachment submission is rejected.
8. Learner accepts; relationship stays under review; chat remains available; monitoring remains denied.
9. Admin approves; configured relationship permissions activate.
10. Admin revokes; open browser tabs cannot send or subscribe; transcript is read-only.
11. Reject, cancel, and expire separate invitations; each conversation closes.
12. Two guardians create independent invitations and conversations for one learner.
13. Suspend one guardian; their chat stops without affecting the other guardian.
14. Test mobile, tablet, and desktop widths plus image and PDF preview fallbacks.

- [ ] **Step 5: Write the verification report**

Record:

- exact Git revision;
- PHP, Laravel, PHPUnit, Node, pnpm, database driver, and browser versions;
- exact commands and exit codes;
- focused and full-suite counts;
- each browser scenario and observed result;
- Pint and Vite results;
- pre-existing failures compared with Phase 1;
- confirmation that no Health & Support Information was added;
- confirmation that messages do not influence relationship approval.

- [ ] **Step 6: Review the Phase 2 acceptance criteria**

Confirm every item:

- one-to-ten invitation evidence files;
- server-enforced type, size, category, pairing, core-category, and duplicate rules;
- image/PDF preview, replace, and remove before submission;
- privacy-safe guardian information;
- no unnecessary email, birthdate, age, identity, or evidence exposure;
- learner-controlled text-only conversation;
- no generic chat-start bypass;
- dynamic and stored lifecycle closure;
- invitation acceptance grants no guardian privileges;
- multiple guardians remain independent;
- existing chat, notification, unread, real-time, report, moderation, and Phase 1 verification regressions pass.

- [ ] **Step 7: Commit the verification report and any narrowly scoped final fixes**

```powershell
git add docs/superpowers/verification/2026-09-09-guardian-dependent-invitation-messaging-e2e.md
git commit -m "docs: record guardian invitation messaging results"
```

If final fixes were required, commit each fix with its corresponding test before committing the report. Do not stage unrelated dirty files or generated build output.

---

## Plan Self-Review

- Every design acceptance criterion maps to Tasks 1–10.
- Phase 1 relationship verification remains authoritative; no invitation or message path approves a relationship.
- Invitation evidence uses the Phase 1 collection contract and private storage.
- Conversation provenance is explicit and independently unique per invitation.
- Learner control is enforced at creation, while every later operation rechecks lifecycle state.
- Read-only transcript access is distinct from live subscription and mutation.
- Text-only enforcement exists in both HTTP validation and the service layer.
- Existing instructor requests, direct chat, contextual chat, support chat, notifications, reporting, and moderation have named regression coverage.
- No Phase 3 health fields, new dependency, public profile, or speculative framework is included.
