# Perspective Feedback Interactive Checkpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an ungraded Perspective Feedback Interactive Checkpoint with guided response-specific feedback and an optional learner-written perspective pathway.

**Architecture:** Extend `quiz_questions`, `quiz_options`, and `interactive_checkpoint_progress` instead of creating a parallel system. Keep the new type checkpoint-only, synchronize Perspective Feedback options by stable database ID, snapshot completed learner responses, and introduce a neutral `completed` progress state that bypasses `QuestionEvaluator` and formal quiz scoring.

**Tech Stack:** Laravel 12, PHP 8.2+, Eloquent, Blade, Alpine.js 3, Tailwind CSS, PHPUnit 11, Node's built-in test runner, Vite 7.

## Global Constraints

- The persisted question type is exactly `perspective_feedback`.
- Perspective Feedback is available only to Interactive Checkpoints in both inside-topic and between-topic placements.
- Every Perspective Feedback checkpoint has 2–12 guided response options.
- Every guided option has required response text (maximum 500 characters) and required feedback (maximum 5,000 characters).
- The scenario is required rich text; context is optional plain text with a 5,000-character maximum.
- Written perspectives are optional and instructor-controlled; when enabled, the prompt is required with a 500-character maximum.
- The written-response limit defaults to 1,000 and is configurable from 100 through 5,000 characters.
- Reflection Guide and general explanation are optional plain text with 5,000-character maxima.
- Guided and written submissions use `status = completed`, `is_correct = null`, and `points = 0`.
- Skipping remains neutral and continues to resolve checkpoint progression.
- The first valid guided or written submission is final and must be restored from its progress snapshot.
- Learner-written perspectives are never graded, compared, scored, AI-evaluated, or marked correct/incorrect.
- Existing quiz and checkpoint types keep their current authoring, evaluation, scoring, feedback, retry, and completion behavior.
- Do not add a reporting screen, analytics view, response roster, or export.
- Use only incremental migrations. Never reset, wipe, truncate, recreate, or destructively reseed the development database.
- Run database tests only through the isolated test configuration in `phpunit.xml`.
- Preserve unrelated user files, including the existing untracked `docs/audio-feedback-assets.md` and `public/audio/` work.

---

### Task 1: Add the additive schema and model metadata

**Files:**
- Create: `database/migrations/2026_09_17_000001_add_perspective_feedback_to_interactive_checkpoints.php`
- Modify: `app/Models/QuizQuestion.php:11-34`
- Modify: `app/Models/QuizOption.php:9-21`
- Modify: `tests/Feature/Learner/InteractiveCheckpointSchemaTest.php`

**Interfaces:**
- Consumes: Existing `quiz_questions`, `quiz_options`, and `interactive_checkpoint_progress` tables.
- Produces: `QuizQuestion` configuration attributes and `QuizOption::feedback` used by authoring, submission, and rendering tasks.

- [ ] **Step 1: Write the failing schema and cast tests**

Add these methods to `InteractiveCheckpointSchemaTest`:

```php
public function test_perspective_feedback_schema_is_additive(): void
{
    $this->assertTrue(Schema::hasColumns('quiz_questions', [
        'context_description',
        'allow_own_perspective',
        'perspective_prompt',
        'perspective_character_limit',
        'reflection_guide',
    ]));
    $this->assertTrue(Schema::hasColumn('quiz_options', 'feedback'));
}

public function test_perspective_feedback_model_fields_are_cast(): void
{
    $question = new QuizQuestion([
        'allow_own_perspective' => 1,
        'perspective_character_limit' => '1000',
    ]);

    $this->assertTrue($question->allow_own_perspective);
    $this->assertSame(1000, $question->perspective_character_limit);

    $option = new QuizOption(['feedback' => 'Consider the impact of this response.']);
    $this->assertSame('Consider the impact of this response.', $option->feedback);
}
```

Add imports if absent:

```php
use App\Models\QuizOption;
use Illuminate\Support\Facades\Schema;
```

- [ ] **Step 2: Run the focused schema tests and verify failure**

Run:

```powershell
php artisan test tests/Feature/Learner/InteractiveCheckpointSchemaTest.php
```

Expected: FAIL because the new columns and model metadata do not exist.

- [ ] **Step 3: Create the incremental migration**

Create `database/migrations/2026_09_17_000001_add_perspective_feedback_to_interactive_checkpoints.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification', 'perspective_feedback') DEFAULT 'multiple_choice'");
        }

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->text('context_description')->nullable()->after('question_text');
            $table->boolean('allow_own_perspective')->default(false)->after('explanation');
            $table->text('perspective_prompt')->nullable()->after('allow_own_perspective');
            $table->unsignedSmallInteger('perspective_character_limit')->nullable()->after('perspective_prompt');
            $table->text('reflection_guide')->nullable()->after('perspective_character_limit');
        });

        Schema::table('quiz_options', function (Blueprint $table): void {
            $table->text('feedback')->nullable()->after('option_text');
        });
    }

    public function down(): void
    {
        if (DB::table('quiz_questions')->where('question_type', 'perspective_feedback')->exists()) {
            throw new \RuntimeException('Remove Perspective Feedback records before rolling back this migration.');
        }

        Schema::table('quiz_options', function (Blueprint $table): void {
            $table->dropColumn('feedback');
        });

        Schema::table('quiz_questions', function (Blueprint $table): void {
            $table->dropColumn([
                'context_description',
                'allow_own_perspective',
                'perspective_prompt',
                'perspective_character_limit',
                'reflection_guide',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE quiz_questions MODIFY COLUMN question_type ENUM('multiple_choice', 'true_false', 'multiple_select', 'fill_blank_text', 'fill_blank_select', 'identification') DEFAULT 'multiple_choice'");
        }
    }
};
```

- [ ] **Step 4: Add model fillable fields and casts**

Add to `QuizQuestion::$fillable`:

```php
'context_description',
'allow_own_perspective',
'perspective_prompt',
'perspective_character_limit',
'reflection_guide',
```

Extend `QuizQuestion::casts()`:

```php
'allow_own_perspective' => 'boolean',
'perspective_character_limit' => 'integer',
```

Add to `QuizOption::$fillable`:

```php
'feedback',
```

- [ ] **Step 5: Run the migration and focused tests against the isolated test database**

Run:

```powershell
php artisan test tests/Feature/Learner/InteractiveCheckpointSchemaTest.php
```

Expected: PASS. Do not run any reset or fresh-migration command against the development database.

- [ ] **Step 6: Commit the schema slice**

```powershell
git add database/migrations/2026_09_17_000001_add_perspective_feedback_to_interactive_checkpoints.php app/Models/QuizQuestion.php app/Models/QuizOption.php tests/Feature/Learner/InteractiveCheckpointSchemaTest.php
git commit -m "feat(checkpoints): add perspective feedback schema"
```

---

### Task 2: Add checkpoint-only server validation and stable option persistence

**Files:**
- Modify: `app/Services/Learning/QuestionAuthoringService.php:14-274`
- Modify: `app/Http/Controllers/Instructor/TopicController.php:603-750`
- Modify: `tests/Unit/Services/Learning/QuestionAuthoringServiceTest.php`

**Interfaces:**
- Consumes: New question and option columns from Task 1.
- Produces: `QuestionAuthoringService::CHECKPOINT_TYPES`, `validateCheckpoint(Request, ?QuizQuestion): array`, and stable Perspective Feedback option synchronization.

- [ ] **Step 1: Write failing validation and persistence tests**

Add tests covering the public contract:

```php
public function test_perspective_feedback_is_valid_only_in_checkpoint_context(): void
{
    $request = Request::create('/', 'POST', [
        'question_text' => '<p>How would you respond?</p>',
        'question_type' => 'perspective_feedback',
        'points' => 99,
        'context_description' => 'A friend asks for support.',
        'perspective_options' => [
            ['text' => 'Listen first.', 'feedback' => 'Listening creates room for the person to explain.'],
            ['text' => 'Decide for them.', 'feedback' => 'Support differs from taking control of another person’s decision.'],
        ],
        'allow_own_perspective' => 1,
        'perspective_prompt' => 'What would you do?',
        'perspective_character_limit' => 1000,
        'reflection_guide' => 'Consider boundaries and possible effects.',
        'explanation' => 'Support should respect the other person’s agency.',
    ]);

    $data = app(QuestionAuthoringService::class)->validateCheckpoint($request);

    $this->assertSame('perspective_feedback', $data['question_type']);
    $this->assertSame(0, $data['points']);
    $this->expectException(ValidationException::class);
    app(QuestionAuthoringService::class)->validate($request);
}

public function test_perspective_option_ids_and_feedback_survive_reordering(): void
{
    $question = QuizQuestion::create([
        'checkpoint_topic_id' => LessonTopic::factory()->create()->id,
        'question_text' => '<p>Scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'order' => 1,
    ]);
    $first = $question->options()->create([
        'option_text' => 'First',
        'feedback' => 'First feedback',
        'is_correct' => false,
        'order' => 0,
    ]);
    $second = $question->options()->create([
        'option_text' => 'Second',
        'feedback' => 'Second feedback',
        'is_correct' => false,
        'order' => 1,
    ]);

    $updated = app(QuestionAuthoringService::class)->updateQuestion($question, [
        'question_text' => '<p>Scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'context_description' => null,
        'perspective_options' => [
            ['id' => $second->id, 'text' => 'Second edited', 'feedback' => 'Second feedback edited'],
            ['id' => $first->id, 'text' => 'First', 'feedback' => 'First feedback'],
        ],
        'allow_own_perspective' => false,
        'perspective_prompt' => null,
        'perspective_character_limit' => null,
        'reflection_guide' => null,
        'explanation' => null,
    ]);

    $this->assertSame([$second->id, $first->id], $updated->options->pluck('id')->all());
    $this->assertSame('Second feedback edited', $updated->options->first()->feedback);
    $this->assertFalse($updated->options->first()->is_correct);
}

public function test_perspective_type_conversion_is_blocked_after_learner_progress(): void
{
    $topic = LessonTopic::factory()->create();
    $question = QuizQuestion::create([
        'checkpoint_topic_id' => $topic->id,
        'question_text' => '<p>Existing question</p>',
        'question_type' => 'multiple_choice',
        'points' => 1,
        'order' => 1,
    ]);
    InteractiveCheckpointProgress::create([
        'user_id' => User::factory()->create()->id,
        'lesson_topic_id' => $topic->id,
        'quiz_question_id' => $question->id,
        'status' => 'skipped',
        'skipped_at' => now(),
        'completed_at' => now(),
    ]);
    $request = Request::create('/', 'PUT', $this->validPerspectivePayload());

    try {
        app(QuestionAuthoringService::class)->validateCheckpoint($request, $question);
        $this->fail('Validation should have failed.');
    } catch (ValidationException $exception) {
        $this->assertArrayHasKey('question_type', $exception->errors());
    }
}

public function test_perspective_update_rejects_an_option_id_owned_by_another_question(): void
{
    $topic = LessonTopic::factory()->create();
    $question = QuizQuestion::create([
        'checkpoint_topic_id' => $topic->id,
        'question_text' => '<p>Scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'order' => 1,
    ]);
    $other = QuizQuestion::create([
        'checkpoint_topic_id' => $topic->id,
        'question_text' => '<p>Other scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'order' => 2,
    ]);
    $foreign = $other->options()->create([
        'option_text' => 'Foreign',
        'feedback' => 'Foreign feedback',
        'is_correct' => false,
        'order' => 0,
    ]);
    $data = array_replace($this->validPerspectivePayload(), [
        'perspective_options' => [
            ['id' => $foreign->id, 'text' => 'Foreign', 'feedback' => 'Foreign feedback'],
            ['text' => 'Local', 'feedback' => 'Local feedback'],
        ],
    ]);

    $this->expectException(ValidationException::class);
    app(QuestionAuthoringService::class)->updateQuestion($question, $data);
}
```

```php
#[DataProvider('invalidPerspectivePayloads')]
public function test_invalid_perspective_feedback_configuration_is_rejected(array $changes, string $errorKey): void
{
    $request = Request::create('/', 'POST', array_replace($this->validPerspectivePayload(), $changes));

    try {
        app(QuestionAuthoringService::class)->validateCheckpoint($request);
        $this->fail('Validation should have failed.');
    } catch (ValidationException $exception) {
        $this->assertArrayHasKey($errorKey, $exception->errors());
    }
}

public static function invalidPerspectivePayloads(): array
{
    $validOption = ['text' => 'Listen', 'feedback' => 'Listening makes space for the concern.'];

    return [
        'fewer than two responses' => [
            ['perspective_options' => [$validOption]],
            'perspective_options',
        ],
        'more than twelve responses' => [
            ['perspective_options' => array_fill(0, 13, $validOption)],
            'perspective_options',
        ],
        'empty response text' => [
            ['perspective_options' => [
                ['text' => '', 'feedback' => 'Feedback'],
                ['text' => 'Respond', 'feedback' => 'Feedback'],
            ]],
            'perspective_options.0.text',
        ],
        'missing response feedback' => [
            ['perspective_options' => [
                ['text' => 'Listen', 'feedback' => ''],
                ['text' => 'Respond', 'feedback' => 'Feedback'],
            ]],
            'perspective_options.0.feedback',
        ],
        'character limit below minimum' => [
            ['perspective_character_limit' => 99],
            'perspective_character_limit',
        ],
        'character limit above maximum' => [
            ['perspective_character_limit' => 5001],
            'perspective_character_limit',
        ],
        'missing enabled pathway prompt' => [
            ['perspective_prompt' => ''],
            'perspective_prompt',
        ],
    ];
}

private function validPerspectivePayload(): array
{
    return [
        'question_text' => '<p>How would you respond?</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'context_description' => null,
        'perspective_options' => [
            ['text' => 'Listen', 'feedback' => 'Listening makes space for the concern.'],
            ['text' => 'Direct them', 'feedback' => 'Direction can replace support with control.'],
        ],
        'allow_own_perspective' => 1,
        'perspective_prompt' => 'What would you do?',
        'perspective_character_limit' => 1000,
        'reflection_guide' => null,
        'explanation' => null,
    ];
}
```

- [ ] **Step 2: Run the focused service tests and verify failure**

Run:

```powershell
php artisan test tests/Unit/Services/Learning/QuestionAuthoringServiceTest.php
```

Expected: FAIL because `validateCheckpoint`, checkpoint type isolation, Perspective Feedback rules, and stable option synchronization are absent.

- [ ] **Step 3: Split formal and checkpoint type allowlists**

Replace the single public type list with:

```php
private const CHOICE_TYPES = ['multiple_choice', 'true_false', 'multiple_select'];

private const TEXT_ANSWER_TYPES = ['fill_blank_text', 'fill_blank_select', 'identification'];

private const PERSPECTIVE_TYPE = 'perspective_feedback';

public const TYPES = [
    'multiple_choice',
    'true_false',
    'multiple_select',
    'fill_blank_text',
    'fill_blank_select',
    'identification',
];

public const CHECKPOINT_TYPES = [
    ...self::TYPES,
    self::PERSPECTIVE_TYPE,
];
```

Keep `QuizManagementController` on `TYPES`. Add this checkpoint entry point:

```php
public function validateCheckpoint(Request $request, ?QuizQuestion $question = null): array
{
    return $this->validateForTypes($request, self::CHECKPOINT_TYPES, $question);
}

public function validate(Request $request): array
{
    return $this->validateForTypes($request, self::TYPES);
}
```

- [ ] **Step 4: Add exact Perspective Feedback normalization and rules**

Add a private validator entry point that calls `normalizeRequest`, selects rules by type, and attaches conversion validation:

```php
private function validateForTypes(Request $request, array $allowedTypes, ?QuizQuestion $question = null): array
{
    $this->normalizeRequest($request);

    $type = (string) $request->input('question_type');
    $rules = $type === self::PERSPECTIVE_TYPE
        ? $this->perspectiveRules($allowedTypes)
        : $this->rules($allowedTypes);

    $validator = ValidatorFacade::make(
        array_merge($request->all(), ['image' => $request->file('image')]),
        $rules,
    );
    $validator->after(function (Validator $validator) use ($request, $question): void {
        $this->validateConfiguration($validator, $request);
        $this->validatePerspectiveTypeConversion($validator, $request, $question);
    });

    return $validator->validate();
}

private function perspectiveRules(array $allowedTypes): array
{
    return [
        'question_text' => ['required', 'string'],
        'question_type' => ['required', 'in:'.implode(',', $allowedTypes)],
        'points' => ['required', 'integer', 'min:0', 'max:0'],
        'context_description' => ['nullable', 'string', 'max:5000'],
        'perspective_options' => ['required', 'array', 'min:2', 'max:12'],
        'perspective_options.*.id' => ['nullable', 'integer', 'distinct'],
        'perspective_options.*.text' => ['required', 'string', 'max:500'],
        'perspective_options.*.feedback' => ['required', 'string', 'max:5000'],
        'allow_own_perspective' => ['required', 'boolean'],
        'perspective_prompt' => ['nullable', 'required_if:allow_own_perspective,1', 'string', 'max:500'],
        'perspective_character_limit' => ['nullable', 'required_if:allow_own_perspective,1', 'integer', 'min:100', 'max:5000'],
        'reflection_guide' => ['nullable', 'string', 'max:5000'],
        'explanation' => ['nullable', 'string', 'max:5000'],
    ];
}
```

At the beginning of `normalizeRequest`, normalize Perspective Feedback and return before existing choice/text logic:

```php
if ($type === self::PERSPECTIVE_TYPE) {
    $request->merge([
        'points' => 0,
        'allow_own_perspective' => $request->boolean('allow_own_perspective'),
        'perspective_options' => array_values((array) $request->input('perspective_options', [])),
    ]);
    $request->request->remove('options');
    $request->request->remove('correct_options');
    $request->request->remove('acceptable_answers');
    $request->request->remove('case_sensitive');
    $request->request->remove('word_bank');

    return;
}
```

Add meaningful scenario and conversion checks:

```php
private function validatePerspectiveTypeConversion(
    Validator $validator,
    Request $request,
    ?QuizQuestion $question,
): void {
    if (! $question || $question->question_type === $request->input('question_type')) {
        return;
    }

    $involvesPerspective = in_array(self::PERSPECTIVE_TYPE, [
        $question->question_type,
        (string) $request->input('question_type'),
    ], true);

    if ($involvesPerspective && $question->checkpointProgress()->exists()) {
        $validator->errors()->add(
            'question_type',
            'The checkpoint type cannot be changed after a learner has interacted with it.',
        );
    }
}
```

Change `rules()` to accept the allowlist:

```php
public function rules(array $allowedTypes = self::TYPES): array
```

and use:

```php
'question_type' => ['required', 'in:'.implode(',', $allowedTypes)],
```

- [ ] **Step 5: Persist Perspective Feedback metadata and synchronize options by ID**

In `questionPayload`, branch the new type and clear its fields when another type is selected:

```php
$isPerspective = $data['question_type'] === self::PERSPECTIVE_TYPE;

return array_merge($owner, [
    'question_text' => $data['question_text'],
    'question_type' => $data['question_type'],
    'points' => $isPerspective ? 0 : (int) $data['points'],
    'context_description' => $isPerspective ? ($data['context_description'] ?? null) : null,
    'acceptable_answers' => $acceptableAnswers,
    'case_sensitive' => ! $isPerspective && $usesTextAnswers && ! empty($data['case_sensitive']),
    'word_bank' => $data['question_type'] === 'fill_blank_select'
        ? array_map('trim', explode(',', $data['word_bank']))
        : null,
    'image_path' => $usesImage
        ? (($data['image'] ?? null) instanceof UploadedFile
            ? $data['image']->store($this->imageDirectory(), 'public')
            : (! empty($data['remove_existing_image']) ? null : ($data['image_path'] ?? $existingImagePath)))
        : null,
    'explanation' => $data['explanation'] ?? null,
    'allow_own_perspective' => $isPerspective && ! empty($data['allow_own_perspective']),
    'perspective_prompt' => $isPerspective ? ($data['perspective_prompt'] ?? null) : null,
    'perspective_character_limit' => $isPerspective
        ? ($data['perspective_character_limit'] ?? null)
        : null,
    'reflection_guide' => $isPerspective ? ($data['reflection_guide'] ?? null) : null,
]);
```

At the start of `replaceOptions`, branch to:

```php
if ($data['question_type'] === self::PERSPECTIVE_TYPE) {
    $this->syncPerspectiveOptions($question, $data['perspective_options']);

    return;
}
```

Add the synchronizer:

```php
private function syncPerspectiveOptions(QuizQuestion $question, array $submitted): void
{
    $existing = $question->options()->get()->keyBy('id');
    $keptIds = [];

    foreach (array_values($submitted) as $order => $optionData) {
        $id = isset($optionData['id']) ? (int) $optionData['id'] : null;

        if ($id !== null && ! $existing->has($id)) {
            throw ValidationException::withMessages([
                'perspective_options' => 'Every response option must belong to this checkpoint.',
            ]);
        }

        $option = $id !== null ? $existing->get($id) : $question->options()->make();
        $option->fill([
            'option_text' => $optionData['text'],
            'feedback' => $optionData['feedback'],
            'is_correct' => false,
            'order' => $order,
        ]);
        $option->save();
        $keptIds[] = $option->id;
    }

    $question->options()->whereNotIn('id', $keptIds)->delete();
}
```

Import:

```php
use Illuminate\Validation\ValidationException;
```

- [ ] **Step 6: Route checkpoint mutations through checkpoint validation**

In all three checkpoint mutation paths in `TopicController`, replace the points merge and generic validation with:

```php
$questionData = $this->questionAuthoring->validateCheckpoint($request);
```

For `updateCheckpoint` and `updateBetweenTopicCheckpoint`, load the question before validation and call:

```php
$questionData = $this->questionAuthoring->validateCheckpoint($request, $question);
```

Keep `QuizManagementController` unchanged on `validate()`.

- [ ] **Step 7: Run service and authoring regression tests**

Run:

```powershell
php artisan test tests/Unit/Services/Learning/QuestionAuthoringServiceTest.php tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php tests/Feature/Instructor/QuizQuestionAuthoringRegressionTest.php
```

Expected: PASS, including all existing question types.

- [ ] **Step 8: Commit the server authoring slice**

```powershell
git add app/Services/Learning/QuestionAuthoringService.php app/Http/Controllers/Instructor/TopicController.php tests/Unit/Services/Learning/QuestionAuthoringServiceTest.php
git commit -m "feat(checkpoints): author perspective feedback"
```

---

### Task 3: Build the create/edit Perspective Feedback authoring interface

**Files:**
- Modify: `resources/views/instructor/quizzes/partials/question-fields.blade.php`
- Modify: `resources/views/instructor/topics/create.blade.php:343-351`
- Modify: `resources/views/instructor/topics/edit-checkpoint.blade.php:37-46`
- Modify: `resources/js/question-authoring.js`
- Modify: `tests/JavaScript/question-authoring.test.mjs`
- Modify: `tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php`

**Interfaces:**
- Consumes: `CHECKPOINT_TYPES`, validation field names, and stable option IDs from Task 2.
- Produces: POST/PUT fields named `perspective_options[index][id|text|feedback]` plus all question-level Perspective Feedback configuration.

- [ ] **Step 1: Write failing JavaScript state and validation tests**

Add tests to `question-authoring.test.mjs`:

```javascript
test('perspective feedback starts with two neutral response rows', () => {
    const form = createQuestionAuthoring({ type: 'perspective_feedback' });

    assert.equal(form.isPerspectiveType(), true);
    assert.equal(form.perspectiveOptions.length, 2);
    assert.deepEqual(form.correctIndices(), []);
});

test('perspective feedback requires option feedback and enabled writing configuration', () => {
    const form = createQuestionAuthoring({
        type: 'perspective_feedback',
        questionText: '<p>Scenario</p>',
        perspectiveOptions: [
            { id: 10, text: 'Listen', feedback: '' },
            { id: 11, text: 'Ignore it', feedback: 'Ignoring may leave the concern unsupported.' },
        ],
        allowOwnPerspective: true,
        perspectivePrompt: '',
        perspectiveCharacterLimit: 99,
    });

    assert.deepEqual(form.validationErrors(), {
        perspective_options: 'Every response needs text and educational feedback.',
        perspective_prompt: 'Add a prompt for the learner’s own perspective.',
        perspective_character_limit: 'Use a character limit between 100 and 5000.',
    });
});

test('perspective response rows reorder without changing stable ids', () => {
    const form = createQuestionAuthoring({
        type: 'perspective_feedback',
        perspectiveOptions: [
            { id: 10, text: 'A', feedback: 'A feedback' },
            { id: 11, text: 'B', feedback: 'B feedback' },
        ],
    });

    form.movePerspectiveOption(1, -1);

    assert.deepEqual(form.perspectiveOptions.map((option) => option.id), [11, 10]);
});
```

- [ ] **Step 2: Run JavaScript tests and verify failure**

Run:

```powershell
node --test tests/JavaScript/question-authoring.test.mjs
```

Expected: FAIL because Perspective Feedback authoring state does not exist.

- [ ] **Step 3: Add Perspective Feedback state and operations**

Include `perspective_feedback` in `RICH_TYPES`, and add:

```javascript
function defaultPerspectiveOptions(nextKey) {
    return [
        { key: nextKey(), id: null, text: '', feedback: '' },
        { key: nextKey(), id: null, text: '', feedback: '' },
    ];
}
```

Initialize these properties in `createQuestionAuthoring`:

```javascript
perspectiveOptions: Array.isArray(config.perspectiveOptions) && config.perspectiveOptions.length
    ? config.perspectiveOptions.map((option) => ({
        key: nextKey(),
        id: option.id === null || option.id === undefined ? null : Number(option.id),
        text: String(option.text || ''),
        feedback: String(option.feedback || ''),
    }))
    : defaultPerspectiveOptions(nextKey),
contextDescription: config.contextDescription || '',
allowOwnPerspective: Boolean(config.allowOwnPerspective),
perspectivePrompt: config.perspectivePrompt || '',
perspectiveCharacterLimit: Number(config.perspectiveCharacterLimit || 1000),
reflectionGuide: config.reflectionGuide || '',
```

Add operations:

```javascript
isPerspectiveType() {
    return this.questionType === 'perspective_feedback';
},

addPerspectiveOption() {
    if (this.perspectiveOptions.length >= 12) return;
    this.perspectiveOptions.push({ key: nextKey(), id: null, text: '', feedback: '' });
},

removePerspectiveOption(index) {
    if (this.perspectiveOptions.length <= 2) return;
    this.perspectiveOptions.splice(index, 1);
},

movePerspectiveOption(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= this.perspectiveOptions.length) return;
    const [option] = this.perspectiveOptions.splice(index, 1);
    this.perspectiveOptions.splice(target, 0, option);
},
```

In `switchType`, reset Perspective Feedback state using:

```javascript
this.perspectiveOptions = defaultPerspectiveOptions(nextKey);
this.contextDescription = '';
this.allowOwnPerspective = false;
this.perspectivePrompt = '';
this.perspectiveCharacterLimit = 1000;
this.reflectionGuide = '';
```

At the start of the type-specific part of `validationErrors`, add:

```javascript
if (this.isPerspectiveType()) {
    if (this.perspectiveOptions.length < 2 || this.perspectiveOptions.length > 12
        || this.perspectiveOptions.some((option) => !option.text.trim() || !option.feedback.trim())) {
        errors.perspective_options = 'Every response needs text and educational feedback.';
    }
    if (this.allowOwnPerspective && !this.perspectivePrompt.trim()) {
        errors.perspective_prompt = 'Add a prompt for the learner’s own perspective.';
    }
    if (this.allowOwnPerspective
        && (this.perspectiveCharacterLimit < 100 || this.perspectiveCharacterLimit > 5000)) {
        errors.perspective_character_limit = 'Use a character limit between 100 and 5000.';
    }

    return errors;
}
```

- [ ] **Step 4: Make the shared Blade partial checkpoint-aware**

At the top of `question-fields.blade.php`, initialize:

```php
$isCheckpoint = $isCheckpoint ?? false;
$submittedPerspectiveOptions = old('perspective_options');
$perspectiveOptions = is_array($submittedPerspectiveOptions)
    ? collect($submittedPerspectiveOptions)->values()->map(fn ($option) => [
        'id' => isset($option['id']) ? (int) $option['id'] : null,
        'text' => $option['text'] ?? '',
        'feedback' => $option['feedback'] ?? '',
    ])->all()
    : $existingOptions->map(fn ($option) => [
        'id' => $option->id,
        'text' => $option->option_text,
        'feedback' => $option->feedback ?? '',
    ])->all();
```

Append type metadata only for checkpoint forms:

```php
if ($isCheckpoint) {
    $typeMeta['perspective_feedback'] = [
        'label' => 'Perspective Feedback',
        'description' => 'Learners choose a guided response or optionally share their own perspective.',
        'badge' => 'bg-sky-50 text-sky-700 border-sky-200',
    ];
}
```

Pass the new component configuration:

```blade
perspectiveOptions: @js($perspectiveOptions),
contextDescription: @js(old('context_description', $question->context_description ?? '')),
allowOwnPerspective: @js((bool) old('allow_own_perspective', $question->allow_own_perspective ?? false)),
perspectivePrompt: @js(old('perspective_prompt', $question->perspective_prompt ?? '')),
perspectiveCharacterLimit: @js(old('perspective_character_limit', $question->perspective_character_limit ?? 1000)),
reflectionGuide: @js(old('reflection_guide', $question->reflection_guide ?? '')),
```

Add the Perspective Feedback fields as one conditional template:

```blade
<template x-if="isPerspectiveType()">
    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <label for="context_description" class="block text-sm font-semibold text-gray-900">Context / Description <span class="font-normal text-gray-400">(Optional)</span></label>
            <textarea id="context_description" name="context_description" rows="4" maxlength="5000" x-model="contextDescription" class="mt-2 w-full rounded-xl border-gray-200 text-sm focus:border-purple-400 focus:ring-purple-300"></textarea>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm" role="group" aria-labelledby="perspective_options_heading">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 id="perspective_options_heading" class="text-sm font-semibold text-gray-900">Guided Responses</h3>
                    <p class="text-xs text-gray-500">Add 2–12 responses and educational feedback for each one.</p>
                </div>
                <button type="button" @click="addPerspectiveOption()" :disabled="perspectiveOptions.length >= 12" class="min-h-11 rounded-xl border border-purple-200 px-3 text-sm font-semibold text-purple-700 disabled:opacity-50">Add Response</button>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(option, index) in perspectiveOptions" :key="option.key">
                    <fieldset class="rounded-xl border border-gray-200 p-4">
                        <legend class="px-1 text-sm font-semibold text-gray-800" x-text="`Response ${index + 1}`"></legend>
                        <input x-show="option.id !== null" type="hidden" :name="`perspective_options[${index}][id]`" :value="option.id">
                        <label class="block text-xs font-semibold text-gray-700" :for="`perspective-option-${index}`">Response text</label>
                        <input :id="`perspective-option-${index}`" :name="`perspective_options[${index}][text]`" x-model="option.text" maxlength="500" required class="mt-1 w-full rounded-xl border-gray-200 text-sm">
                        <label class="mt-3 block text-xs font-semibold text-gray-700" :for="`perspective-feedback-${index}`">Educational feedback</label>
                        <textarea :id="`perspective-feedback-${index}`" :name="`perspective_options[${index}][feedback]`" x-model="option.feedback" maxlength="5000" rows="3" required class="mt-1 w-full rounded-xl border-gray-200 text-sm"></textarea>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" @click="movePerspectiveOption(index, -1)" :disabled="index === 0" :aria-label="`Move response ${index + 1} up`" class="min-h-11 rounded-lg border px-3 text-sm disabled:opacity-40">Move up</button>
                            <button type="button" @click="movePerspectiveOption(index, 1)" :disabled="index === perspectiveOptions.length - 1" :aria-label="`Move response ${index + 1} down`" class="min-h-11 rounded-lg border px-3 text-sm disabled:opacity-40">Move down</button>
                            <button type="button" @click="removePerspectiveOption(index)" :disabled="perspectiveOptions.length <= 2" :aria-label="`Remove response ${index + 1}`" class="min-h-11 rounded-lg border border-red-200 px-3 text-sm text-red-700 disabled:opacity-40">Remove</button>
                        </div>
                    </fieldset>
                </template>
            </div>
            <p x-show="errors.perspective_options" x-text="errors.perspective_options" class="mt-2 text-xs text-red-600" role="alert"></p>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="allow_own_perspective" value="1" x-model="allowOwnPerspective" class="mt-0.5 h-6 w-6 rounded text-purple-600">
                <span><span class="block text-sm font-semibold text-gray-900">Allow learner to share their own perspective</span><span class="block text-xs text-gray-500">The learner chooses this or a guided response, never both.</span></span>
            </label>
            <input type="hidden" name="allow_own_perspective" value="0" :disabled="allowOwnPerspective">
            <div x-show="allowOwnPerspective" class="mt-4 space-y-4">
                <div>
                    <label for="perspective_prompt" class="block text-sm font-semibold text-gray-700">Custom response prompt</label>
                    <input id="perspective_prompt" name="perspective_prompt" x-model="perspectivePrompt" maxlength="500" :required="allowOwnPerspective" class="mt-1 w-full rounded-xl border-gray-200">
                </div>
                <div>
                    <label for="perspective_character_limit" class="block text-sm font-semibold text-gray-700">Character limit</label>
                    <input id="perspective_character_limit" name="perspective_character_limit" type="number" min="100" max="5000" x-model.number="perspectiveCharacterLimit" :required="allowOwnPerspective" class="mt-1 w-40 rounded-xl border-gray-200">
                </div>
                <div>
                    <label for="reflection_guide" class="block text-sm font-semibold text-gray-700">Reflection Guide <span class="font-normal text-gray-400">(Optional)</span></label>
                    <textarea id="reflection_guide" name="reflection_guide" rows="4" maxlength="5000" x-model="reflectionGuide" class="mt-1 w-full rounded-xl border-gray-200"></textarea>
                </div>
            </div>
        </section>
    </div>
</template>
```

For this type, label the existing question field “Scenario / Question” and the explanation field “General Explanation.” Keep existing labels and helper text for all other types.

- [ ] **Step 5: Enable the type only in checkpoint includes**

Pass this variable from both checkpoint forms:

```php
'isCheckpoint' => true,
```

Do not pass it from formal quiz forms. Add this regression test to `QuizQuestionAuthoringRegressionTest`:

```php
public function test_formal_quiz_authoring_does_not_offer_perspective_feedback(): void
{
    $instructor = User::factory()->create(['role' => 'instructor']);
    $instructor->assignRole('instructor');
    $module = Module::factory()->create([
        'created_by' => $instructor->id,
        'content_owner_type' => 'instructor',
    ]);
    $quiz = Quiz::create([
        'module_id' => $module->id,
        'title' => 'Formal quiz',
        'passing_score' => 70,
        'is_active' => true,
    ]);

    $this->actingAs($instructor)
        ->get(route('instructor.quizzes.add-question', $quiz))
        ->assertOk()
        ->assertDontSee('<option value="perspective_feedback">', false);
}
```

Import `App\Models\Quiz` if the test file does not already import it.

- [ ] **Step 6: Add create, edit, reorder, delete, and reload feature coverage**

Add these integration tests to `InteractiveCheckpointAuthoringTest`:

```php
public function test_instructor_can_create_and_reload_perspective_feedback(): void
{
    [$instructor, $lesson] = $this->authoringFixture('instructor');

    $this->actingAs($instructor)
        ->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Perspective checkpoint',
            'type' => 'interactive_checkpoint',
            'checkpoint_placement' => 'between_topics',
            'question_type' => 'perspective_feedback',
            'question_text' => '<p>A friend describes a relationship concern. How would you respond?</p>',
            'context_description' => 'Think about support, autonomy, and boundaries.',
            'perspective_options' => [
                ['text' => 'Listen and ask what they need.', 'feedback' => 'Listening centers the person’s needs.'],
                ['text' => 'Tell them what to do.', 'feedback' => 'Directing them may replace support with control.'],
                ['text' => 'Ignore the concern.', 'feedback' => 'Ignoring the concern may leave the person unsupported.'],
            ],
            'allow_own_perspective' => 1,
            'perspective_prompt' => 'Share how you would respond.',
            'perspective_character_limit' => 1000,
            'reflection_guide' => 'Consider feelings, boundaries, and possible effects.',
            'explanation' => 'Support can combine care with respect for autonomy.',
        ])
        ->assertRedirect(route('instructor.lessons.show', $lesson));

    $topic = $lesson->topics()->where('type', 'interactive_checkpoint')->firstOrFail();
    $question = $topic->checkpointQuestion()->with('options')->firstOrFail();

    $this->assertSame('perspective_feedback', $question->question_type);
    $this->assertSame(0, $question->points);
    $this->assertTrue($question->allow_own_perspective);
    $this->assertSame(1000, $question->perspective_character_limit);
    $this->assertTrue($question->options->every(fn ($option) => ! $option->is_correct));
    $this->assertSame(
        ['Listening centers the person’s needs.', 'Directing them may replace support with control.', 'Ignoring the concern may leave the person unsupported.'],
        $question->options->pluck('feedback')->all(),
    );

    $this->actingAs($instructor)
        ->get(route('instructor.topics.edit', $topic))
        ->assertOk()
        ->assertSee('Share how you would respond.')
        ->assertSee('Consider feelings, boundaries, and possible effects.')
        ->assertSee('Support can combine care with respect for autonomy.');
}

public function test_perspective_feedback_edit_preserves_ids_while_reordering_and_deleting(): void
{
    [$instructor, $lesson] = $this->authoringFixture('instructor');
    $topic = LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'interactive_checkpoint',
        'interactive_config' => ['placement' => 'between_topics'],
    ]);
    $question = QuizQuestion::create([
        'checkpoint_topic_id' => $topic->id,
        'question_text' => '<p>Scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'order' => 1,
    ]);
    $first = $question->options()->create(['option_text' => 'First', 'feedback' => 'First feedback', 'is_correct' => false, 'order' => 0]);
    $second = $question->options()->create(['option_text' => 'Second', 'feedback' => 'Second feedback', 'is_correct' => false, 'order' => 1]);
    $removed = $question->options()->create(['option_text' => 'Remove me', 'feedback' => 'Removed feedback', 'is_correct' => false, 'order' => 2]);

    $this->actingAs($instructor)
        ->put(route('instructor.topics.update', $topic), [
            'title' => 'Edited perspective checkpoint',
            'question_type' => 'perspective_feedback',
            'question_text' => '<p>Scenario edited</p>',
            'perspective_options' => [
                ['id' => $second->id, 'text' => 'Second edited', 'feedback' => 'Second feedback edited'],
                ['id' => $first->id, 'text' => 'First', 'feedback' => 'First feedback'],
                ['text' => 'New response', 'feedback' => 'New feedback'],
            ],
            'allow_own_perspective' => 0,
            'explanation' => null,
        ])
        ->assertRedirect(route('instructor.lessons.show', $lesson));

    $options = $question->refresh()->options;
    $this->assertSame([$second->id, $first->id], $options->take(2)->pluck('id')->all());
    $this->assertSame('Second feedback edited', $options->first()->feedback);
    $this->assertFalse($options->contains('id', $removed->id));
    $this->assertSame('New response', $options->last()->option_text);
}
```

- [ ] **Step 7: Run authoring tests and build**

Run:

```powershell
node --test tests/JavaScript/question-authoring.test.mjs
php artisan test tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php tests/Feature/Instructor/QuizQuestionAuthoringRegressionTest.php
pnpm.cmd build
```

Expected: all tests PASS and Vite exits successfully.

- [ ] **Step 8: Commit the authoring UI slice**

```powershell
git add resources/views/instructor/quizzes/partials/question-fields.blade.php resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/edit-checkpoint.blade.php resources/js/question-authoring.js tests/JavaScript/question-authoring.test.mjs tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php
git commit -m "feat(checkpoints): build perspective authoring UI"
```

---

### Task 4: Store neutral learner submissions and integrate completion

**Files:**
- Modify: `app/Http/Controllers/Learner/InteractiveCheckpointController.php:18-115`
- Modify: `app/Http/Controllers/Learner/LessonController.php:230-235`
- Modify: `app/Services/LearnerModuleCompletionService.php:66-74`
- Create: `tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php`
- Modify: `tests/Feature/Learner/ModuleInteractionCompletionTest.php`

**Interfaces:**
- Consumes: Perspective Feedback configuration and option feedback from Tasks 1–3.
- Produces: Neutral guided/written submission JSON and the resolved `completed` status used by the learner UI and completion system.

- [ ] **Step 1: Write failing guided, written, idempotency, validation, and no-scoring tests**

Create `PerspectiveFeedbackCheckpointFlowTest.php` with an enrolled learner fixture and these core assertions:

```php
public function test_guided_perspective_submission_is_completed_without_correctness(): void
{
    [$learner, $question] = $this->perspectiveFixture();
    $selected = $question->options->first();

    $this->actingAs($learner)
        ->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'guided', 'option_id' => $selected->id],
        ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('is_correct', null)
        ->assertJsonPath('result.option_id', $selected->id)
        ->assertJsonPath('feedback', $selected->feedback)
        ->assertJsonPath('explanation', $question->explanation);

    $this->assertDatabaseHas('interactive_checkpoint_progress', [
        'user_id' => $learner->id,
        'quiz_question_id' => $question->id,
        'status' => 'completed',
        'is_correct' => null,
        'attempt_count' => 1,
    ]);
    $this->assertDatabaseCount('quiz_attempts', 0);
}

public function test_written_perspective_is_preserved_and_never_graded(): void
{
    [$learner, $question] = $this->perspectiveFixture();
    $text = 'I would listen first and ask what support they want.';

    $this->actingAs($learner)
        ->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => $text],
        ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('is_correct', null)
        ->assertJsonPath('result.perspective_text', $text)
        ->assertJsonPath('feedback', null);

    $progress = InteractiveCheckpointProgress::firstOrFail();
    $this->assertSame($text, $progress->latest_answer['perspective_text']);
    $this->assertNull($progress->is_correct);
}
```

Add the following focused methods to the same test class:

```php
public function test_guided_submission_rejects_an_option_from_another_checkpoint(): void
{
    [$learner, $question] = $this->perspectiveFixture();
    [, $otherQuestion] = $this->perspectiveFixture();

    $this->actingAs($learner)
        ->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'guided', 'option_id' => $otherQuestion->options->first()->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answer.option_id');

    $this->assertDatabaseMissing('interactive_checkpoint_progress', [
        'user_id' => $learner->id,
        'quiz_question_id' => $question->id,
    ]);
}

public function test_written_submission_is_rejected_when_disabled_empty_or_over_limit(): void
{
    [$learner, $question] = $this->perspectiveFixture([
        'allow_own_perspective' => false,
        'perspective_character_limit' => 10,
    ]);

    $this->actingAs($learner)
        ->postJson(route('learner.checkpoints.submit', $question), [
            'answer' => ['pathway' => 'own', 'perspective_text' => 'My view'],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('answer.pathway');

    $question->update(['allow_own_perspective' => true]);

    $this->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'own', 'perspective_text' => ''],
    ])->assertUnprocessable()->assertJsonValidationErrors('answer.perspective_text');

    $this->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'own', 'perspective_text' => '12345678901'],
    ])->assertUnprocessable()->assertJsonValidationErrors('answer.perspective_text');
}

public function test_first_completed_response_and_feedback_snapshot_are_immutable(): void
{
    [$learner, $question] = $this->perspectiveFixture();
    $selected = $question->options->first();

    $this->actingAs($learner)->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'guided', 'option_id' => $selected->id],
    ])->assertOk();

    $selected->update(['option_text' => 'Edited later', 'feedback' => 'Edited feedback']);
    $selected->delete();

    $this->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'own', 'perspective_text' => 'Replacement attempt'],
    ])
        ->assertOk()
        ->assertJsonPath('result.option_text', 'Listen first.')
        ->assertJsonPath('feedback', 'Listening creates room for the concern.');

    $progress = InteractiveCheckpointProgress::firstOrFail();
    $this->assertSame(1, $progress->attempt_count);
    $this->assertSame('guided', $progress->latest_answer['pathway']);
}

public function test_skipped_perspective_feedback_can_later_be_completed(): void
{
    [$learner, $question] = $this->perspectiveFixture();

    $this->actingAs($learner)
        ->postJson(route('learner.checkpoints.skip', $question))
        ->assertJsonPath('status', 'skipped');

    $this->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'own', 'perspective_text' => 'My later reflection'],
    ])
        ->assertOk()
        ->assertJsonPath('status', 'completed')
        ->assertJsonPath('is_correct', null);
}

public function test_perspective_feedback_does_not_change_points_shields_or_quiz_attempts(): void
{
    [$learner, $question] = $this->perspectiveFixture();
    UserDailyShield::refillFull($learner);
    $shieldsBefore = UserDailyShield::getShields($learner);
    $pointsBefore = (int) $learner->gamification()->value('score');

    $this->actingAs($learner)->postJson(route('learner.checkpoints.submit', $question), [
        'answer' => ['pathway' => 'guided', 'option_id' => $question->options->first()->id],
    ])->assertOk();

    $this->assertSame($shieldsBefore, UserDailyShield::getShields($learner->refresh()));
    $this->assertSame($pointsBefore, (int) $learner->gamification()->value('score'));
    $this->assertDatabaseCount('quiz_attempts', 0);
}

private function perspectiveFixture(array $overrides = []): array
{
    $learner = User::factory()->create(['role' => 'learner']);
    $learner->assignRole('learner');
    $module = Module::factory()->create(['is_published' => true]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);
    $topic = LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'interactive_checkpoint',
        'interactive_config' => ['placement' => 'between_topics'],
    ]);
    ModuleEnrollment::create([
        'user_id' => $learner->id,
        'module_id' => $module->id,
        'status' => EnrollmentStatus::Approved,
        'enrolled_at' => now(),
    ]);

    $question = QuizQuestion::create(array_merge([
        'checkpoint_topic_id' => $topic->id,
        'question_text' => '<p>How would you respond?</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'order' => 1,
        'allow_own_perspective' => true,
        'perspective_prompt' => 'Share your perspective.',
        'perspective_character_limit' => 1000,
        'reflection_guide' => 'Consider boundaries and effects.',
        'explanation' => 'Respect and support can work together.',
    ], $overrides));
    $question->options()->createMany([
        ['option_text' => 'Listen first.', 'feedback' => 'Listening creates room for the concern.', 'is_correct' => false, 'order' => 0],
        ['option_text' => 'Decide for them.', 'feedback' => 'Support should not replace their agency.', 'is_correct' => false, 'order' => 1],
    ]);

    return [$learner, $question->refresh()->load('options')];
}
```

Import the fixture dependencies used above, including `EnrollmentStatus`, `ModuleEnrollment`, `InteractiveCheckpointProgress`, and `UserDailyShield`. In the first guided-submission test, bind a mock `QuestionEvaluator` with `shouldNotReceive('evaluate')` before posting; this proves the controller bypasses grading without adding a Perspective Feedback evaluator branch:

```php
$evaluator = Mockery::mock(QuestionEvaluator::class);
$evaluator->shouldNotReceive('evaluate');
$this->app->instance(QuestionEvaluator::class, $evaluator);
```

- [ ] **Step 2: Run the new learner tests and verify failure**

Run:

```powershell
php artisan test tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php
```

Expected: FAIL because Perspective Feedback currently falls through correctness evaluation and `completed` is not recognized.

- [ ] **Step 3: Add the neutral submission branch**

In `submit`, immediately after loading and authorization, branch before constructing the existing progress object and before the correct-answer guard:

```php
if ($question->question_type === 'perspective_feedback') {
    return $this->submitPerspectiveFeedback($request, $question);
}
```

Add:

```php
private function submitPerspectiveFeedback(
    Request $request,
    QuizQuestion $question,
): JsonResponse {
    $limit = (int) ($question->perspective_character_limit ?: 1000);

    return DB::transaction(function () use ($request, $question, $limit): JsonResponse {
        $progress = InteractiveCheckpointProgress::firstOrCreate([
            'user_id' => Auth::id(),
            'quiz_question_id' => $question->id,
        ], [
            'lesson_topic_id' => $question->checkpoint_topic_id,
            'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
            'status' => 'not_attempted',
        ]);
        $progress = InteractiveCheckpointProgress::query()
            ->whereKey($progress->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($progress->status === 'completed') {
            return $this->perspectiveFeedbackResponse($question, $progress);
        }

        $validated = $request->validate([
            'answer' => ['required', 'array'],
            'answer.pathway' => ['required', 'in:guided,own'],
            'answer.option_id' => ['nullable', 'required_if:answer.pathway,guided', 'integer'],
            'answer.perspective_text' => [
                'nullable',
                'required_if:answer.pathway,own',
                'string',
                'max:'.$limit,
            ],
        ]);
        $answer = $validated['answer'];
        if ($answer['pathway'] === 'guided') {
            $option = $question->options->firstWhere('id', (int) $answer['option_id']);
            if (! $option || trim((string) $option->feedback) === '') {
                throw ValidationException::withMessages([
                    'answer.option_id' => 'Select a response that belongs to this checkpoint.',
                ]);
            }

            $snapshot = [
                'pathway' => 'guided',
                'option_id' => (int) $option->id,
                'option_text' => $option->option_text,
                'feedback' => $option->feedback,
            ];
        } else {
            if (! $question->allow_own_perspective) {
                throw ValidationException::withMessages([
                    'answer.pathway' => 'Sharing your own perspective is not enabled for this checkpoint.',
                ]);
            }

            $snapshot = [
                'pathway' => 'own',
                'perspective_text' => $answer['perspective_text'],
            ];
        }

        $progress->fill([
            'lesson_topic_id' => $question->checkpoint_topic_id,
            'checkpoint_block_uuid' => $question->checkpoint_block_uuid,
            'status' => 'completed',
            'latest_answer' => $snapshot,
            'is_correct' => null,
            'attempt_count' => ((int) $progress->attempt_count) + 1,
            'answered_at' => now(),
            'skipped_at' => null,
            'completed_at' => now(),
        ])->save();

        return $this->perspectiveFeedbackResponse($question, $progress);
    });
}

private function perspectiveFeedbackResponse(
    QuizQuestion $question,
    InteractiveCheckpointProgress $progress,
): JsonResponse {
    $result = $progress->latest_answer ?? [];

    return response()->json([
        'status' => 'completed',
        'is_correct' => null,
        'pathway' => $result['pathway'] ?? null,
        'result' => $result,
        'feedback' => ($result['pathway'] ?? null) === 'guided'
            ? ($result['feedback'] ?? null)
            : null,
        'explanation' => $question->explanation,
    ]);
}
```

Import:

```php
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
```

- [ ] **Step 4: Make skip idempotent for completed Perspective Feedback**

At the beginning of `skip`, after creating progress, add:

```php
if ($question->question_type === 'perspective_feedback' && $progress->status === 'completed') {
    return $this->perspectiveFeedbackResponse($question, $progress);
}
```

Keep the existing `correct` guard and neutral skip persistence unchanged.

- [ ] **Step 5: Recognize `completed` in lesson and module completion**

Change the two resolved-status checks in `LessonController` and `LearnerModuleCompletionService` to:

```php
['correct', 'completed', 'skipped']
```

Extend `ModuleInteractionCompletionTest` with a Perspective Feedback progress row using:

```php
'status' => 'completed',
'latest_answer' => ['pathway' => 'own', 'perspective_text' => 'My reflection'],
'is_correct' => null,
```

and assert the checkpoint topic contributes to completion.

- [ ] **Step 6: Run learner backend, completion, and existing evaluator tests**

Run:

```powershell
php artisan test tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php tests/Feature/Learner/InteractiveCheckpointFlowTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php tests/Unit/Services/Learning/QuestionEvaluatorTest.php
```

Expected: PASS. Existing checkpoint types must retain their original correctness behavior.

- [ ] **Step 7: Commit the learner backend slice**

```powershell
git add app/Http/Controllers/Learner/InteractiveCheckpointController.php app/Http/Controllers/Learner/LessonController.php app/Services/LearnerModuleCompletionService.php tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php
git commit -m "feat(checkpoints): store neutral perspective responses"
```

---

### Task 5: Render the accessible learner pathways and complete regression verification

**Files:**
- Modify: `resources/js/interactive-checkpoint.js`
- Modify: `resources/views/learner/lessons/partials/interactive-checkpoint.blade.php`
- Modify: `tests/JavaScript/interactive-checkpoint.test.mjs`
- Modify: `tests/Feature/Learner/InteractiveCheckpointRenderingTest.php`
- Modify: `tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php`

**Interfaces:**
- Consumes: `completed` submission payloads and stored answer snapshots from Task 4.
- Produces: Accessible guided/written learner interactions, neutral feedback rendering, reload restoration, and final regression evidence.

- [ ] **Step 1: Write failing JavaScript tests for pathways and completed state**

Add:

```javascript
test('perspective feedback submits exactly one guided pathway', async () => {
    let submittedBody;
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        questionId: 17,
        submitUrl: '/submit',
        skipUrl: '/skip',
        csrf: 'token',
        perspectiveCharacterLimit: 1000,
    }, async (_url, options) => {
        submittedBody = JSON.parse(options.body);
        return {
            ok: true,
            json: async () => ({
                status: 'completed',
                is_correct: null,
                pathway: 'guided',
                result: { pathway: 'guided', option_id: 4, option_text: 'Listen', feedback: 'Listening helps.' },
                feedback: 'Listening helps.',
                explanation: 'Respect autonomy.',
            }),
        };
    });

    checkpoint.choosePerspectivePathway('guided');
    checkpoint.answer.option_id = 4;
    await checkpoint.submit();

    assert.deepEqual(submittedBody, {
        answer: { pathway: 'guided', option_id: 4, perspective_text: '' },
    });
    assert.equal(checkpoint.state, 'completed');
    assert.equal(checkpoint.isCorrect, null);
    assert.equal(checkpoint.showContinue(), true);
    assert.equal(checkpoint.feedback, 'Listening helps.');
});

test('written perspective counts remaining characters and survives request errors', async () => {
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        submitUrl: '/submit',
        skipUrl: '/skip',
        csrf: 'token',
        perspectiveCharacterLimit: 20,
    }, async () => ({
        ok: false,
        json: async () => ({ message: 'Unable to save the checkpoint.' }),
    }));

    checkpoint.choosePerspectivePathway('own');
    checkpoint.answer.perspective_text = 'My own view';
    assert.equal(checkpoint.remainingPerspectiveCharacters(), 9);

    await checkpoint.submit();

    assert.equal(checkpoint.answer.perspective_text, 'My own view');
    assert.equal(checkpoint.state, 'error');
});

test('stored completed perspective is restored without correctness', () => {
    const checkpoint = createInteractiveCheckpoint({
        type: 'perspective_feedback',
        initialStatus: 'completed',
        initialResult: { pathway: 'own', perspective_text: 'Stored reflection' },
        initialExplanation: 'Consider the impact.',
    });

    assert.equal(checkpoint.state, 'completed');
    assert.equal(checkpoint.answer.perspective_text, 'Stored reflection');
    assert.equal(checkpoint.isCorrect, null);
    assert.equal(checkpoint.showSkip(), false);
});
```

- [ ] **Step 2: Run JavaScript tests and verify failure**

Run:

```powershell
node --test tests/JavaScript/interactive-checkpoint.test.mjs
```

Expected: FAIL because `completed`, pathway state, and neutral feedback are unsupported.

- [ ] **Step 3: Extend the checkpoint component without changing existing type behavior**

Update `emptyCheckpointAnswer`:

```javascript
if (type === 'perspective_feedback') {
    return { pathway: null, option_id: null, perspective_text: '' };
}
```

Accept `completed` as an initial/resolved state and initialize neutral result data:

```javascript
const initialStatus = ['correct', 'incorrect', 'completed', 'skipped'].includes(config.initialStatus)
    ? config.initialStatus
    : 'ready';
const initialAnswer = config.type === 'perspective_feedback' && config.initialResult
    ? { ...emptyCheckpointAnswer(config.type), ...config.initialResult }
    : emptyCheckpointAnswer(config.type, config.blankCount);
```

Use these component fields and methods:

```javascript
answer: initialAnswer,
state: initialStatus,
isCorrect: initialStatus === 'correct' ? true : null,
explanation: ['correct', 'completed'].includes(initialStatus)
    ? config.initialExplanation || null
    : null,
feedback: initialStatus === 'completed' ? config.initialFeedback || null : null,
result: initialStatus === 'completed' ? config.initialResult || null : null,
perspectiveCharacterLimit: Number(config.perspectiveCharacterLimit || 1000),

choosePerspectivePathway(pathway) {
    if (!['guided', 'own'].includes(pathway) || this.state === 'completed') return;
    this.answer.pathway = pathway;
    if (pathway === 'guided') this.answer.perspective_text = '';
    if (pathway === 'own') this.answer.option_id = null;
},

remainingPerspectiveCharacters() {
    return this.perspectiveCharacterLimit - Array.from(this.answer.perspective_text || '').length;
},

showSkip() {
    return ['ready', 'incorrect', 'error'].includes(this.state);
},

showContinue() {
    return ['correct', 'completed', 'skipped'].includes(this.state);
},
```

After a successful submit, assign:

```javascript
this.result = data.result || null;
this.feedback = data.feedback || null;
this.explanation = ['correct', 'completed'].includes(data.status) ? data.explanation : null;
if (data.result && config.type === 'perspective_feedback') {
    this.answer = { ...emptyCheckpointAnswer(config.type), ...data.result };
}
if (['correct', 'completed', 'skipped'].includes(data.status)) this.claimForward();
```

Apply the same resolved-status list to skip handling. Keep `retry()` unchanged for existing incorrect answers; Perspective Feedback request errors remain editable without calling retry.

- [ ] **Step 4: Pass stored neutral state into the learner partial**

At the top of `interactive-checkpoint.blade.php`, change resolved checks to include `completed` and add:

```php
$isPerspectiveFeedback = $question->question_type === 'perspective_feedback';
$initialResult = $progress?->latest_answer;
$initialFeedback = $progress?->status === 'completed'
    ? ($initialResult['feedback'] ?? null)
    : null;
```

Pass to `interactiveCheckpoint`:

```php
'initialResult' => $initialResult,
'initialFeedback' => $initialFeedback,
'initialExplanation' => in_array($progress?->status, ['correct', 'completed'], true)
    ? $question->explanation
    : null,
'perspectiveCharacterLimit' => $question->perspective_character_limit,
```

- [ ] **Step 5: Render the two accessible neutral pathways**

Before the existing question-type branches, add a Perspective Feedback branch that renders:

```blade
@if($isPerspectiveFeedback)
    @if($question->context_description)
        <p class="mt-3 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $question->context_description }}</p>
    @endif

    <div x-show="state !== 'completed'">
        @if($question->allow_own_perspective)
            <fieldset class="mt-5">
                <legend class="text-sm font-semibold text-gray-900 dark:text-white">How would you like to respond?</legend>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="min-h-11 rounded-xl border bg-white p-4 dark:bg-gray-900" :class="answer.pathway === 'guided' ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                        <input type="radio" name="perspective_pathway_{{ $question->id }}" value="guided" :checked="answer.pathway === 'guided'" @change="choosePerspectivePathway('guided')">
                        <span class="ml-2 font-semibold">Choose a Response</span>
                    </label>
                    <label class="min-h-11 rounded-xl border bg-white p-4 dark:bg-gray-900" :class="answer.pathway === 'own' ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                        <input type="radio" name="perspective_pathway_{{ $question->id }}" value="own" :checked="answer.pathway === 'own'" @change="choosePerspectivePathway('own')">
                        <span class="ml-2 font-semibold">Share Your Perspective</span>
                    </label>
                </div>
            </fieldset>
        @else
            <span x-init="choosePerspectivePathway('guided')"></span>
        @endif

        <fieldset x-show="answer.pathway === 'guided'" class="mt-5">
            <legend class="text-sm font-semibold text-gray-900 dark:text-white">Choose a Response</legend>
            <div class="mt-3 space-y-3">
                @foreach($question->options as $option)
                    <label class="flex min-h-11 items-center gap-3 rounded-xl border bg-white p-3 dark:bg-gray-900" :class="Number(answer.option_id) === {{ $option->id }} ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                        <input type="radio" name="perspective_option_{{ $question->id }}" value="{{ $option->id }}" x-model.number="answer.option_id">
                        <span>{{ $option->option_text }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        @if($question->allow_own_perspective)
            <section x-show="answer.pathway === 'own'" class="mt-5" aria-labelledby="perspective_prompt_{{ $question->id }}">
                <h4 id="perspective_prompt_{{ $question->id }}" class="text-sm font-semibold text-gray-900 dark:text-white">{{ $question->perspective_prompt }}</h4>
                @if($question->reflection_guide)
                    <aside class="mt-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900" aria-label="Reflection Guide">
                        <p class="font-semibold">Reflection Guide</p>
                        <p class="mt-1 whitespace-pre-line">{{ $question->reflection_guide }}</p>
                    </aside>
                @endif
                <label for="perspective_text_{{ $question->id }}" class="mt-4 block text-sm font-semibold text-gray-700 dark:text-gray-200">Your Perspective</label>
                <textarea id="perspective_text_{{ $question->id }}" x-model="answer.perspective_text" rows="5" :aria-describedby="`perspective_count_{{ $question->id }}`" class="mt-2 w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></textarea>
                <p id="perspective_count_{{ $question->id }}" class="mt-1 text-xs text-gray-500" aria-live="polite"><span x-text="remainingPerspectiveCharacters()"></span> characters remaining</p>
            </section>
        @endif
    </div>

    <section x-show="state === 'completed'" class="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-950" aria-live="polite">
        <p class="font-semibold" x-text="result?.pathway === 'guided' ? 'Your Response' : 'Your Perspective'"></p>
        <p class="mt-2 whitespace-pre-line text-sm" x-text="result?.pathway === 'guided' ? result?.option_text : result?.perspective_text"></p>
        <div x-show="result?.pathway === 'guided' && feedback" class="mt-4">
            <p class="font-semibold">Feedback</p>
            <p class="mt-1 whitespace-pre-line text-sm" x-text="feedback"></p>
        </div>
        <div x-show="explanation" class="mt-4">
            <p class="font-semibold">Why This Matters</p>
            <p class="mt-1 whitespace-pre-line text-sm" x-text="explanation"></p>
        </div>
    </section>
@elseif(in_array($question->question_type, ['multiple_choice', 'true_false']))
```

Close the existing conditional chain normally. For Perspective Feedback, use “Submit Response” or “Submit Perspective” based on `answer.pathway`; retain existing “Check Answer” for all other types. Suppress the red/green correctness result panel for Perspective Feedback and render only the neutral panel above.

- [ ] **Step 6: Add rendering and formal-quiz regression assertions**

In `InteractiveCheckpointRenderingTest`, assert the enabled form contains:

```php
->assertSee('Perspective Feedback')
->assertSee('Choose a Response')
->assertSee('Share Your Perspective')
->assertSee('Reflection Guide')
->assertSee('characters remaining')
->assertDontSee('Correct Answer');
```

Add this completed-progress rendering case:

```php
public function test_completed_written_perspective_renders_neutral_saved_state(): void
{
    [$learner, $topic, $question] = $this->betweenCheckpointFixture();
    $question->options()->delete();
    $question->update([
        'question_text' => '<p>Perspective scenario</p>',
        'question_type' => 'perspective_feedback',
        'points' => 0,
        'allow_own_perspective' => true,
        'perspective_prompt' => 'Share your perspective.',
        'perspective_character_limit' => 1000,
        'reflection_guide' => 'Consider boundaries.',
        'explanation' => 'Respect matters.',
    ]);
    $question->options()->createMany([
        ['option_text' => 'Listen', 'feedback' => 'Listening helps.', 'is_correct' => false, 'order' => 0],
        ['option_text' => 'Ignore it', 'feedback' => 'Ignoring can leave concerns unsupported.', 'is_correct' => false, 'order' => 1],
    ]);
    InteractiveCheckpointProgress::create([
        'user_id' => $learner->id,
        'lesson_topic_id' => $topic->id,
        'quiz_question_id' => $question->id,
        'status' => 'completed',
        'latest_answer' => ['pathway' => 'own', 'perspective_text' => 'Stored reflection'],
        'is_correct' => null,
        'attempt_count' => 1,
        'answered_at' => now(),
        'completed_at' => now(),
    ]);

    $this->actingAs($learner)
        ->get(route('learner.lessons.show', ['lesson' => $topic->lesson, 'topic' => 0]))
        ->assertOk()
        ->assertSee('Your Perspective')
        ->assertSee('Why This Matters')
        ->assertSee('Stored reflection')
        ->assertDontSee('Correct')
        ->assertDontSee('Incorrect');
}
```

In `InteractiveCheckpointQuizRegressionTest`, assert formal quiz authoring rejects `question_type = perspective_feedback` with a `question_type` validation error and that the existing six types still evaluate normally.

- [ ] **Step 7: Run all focused Perspective Feedback and checkpoint regressions**

Run:

```powershell
node --test tests/JavaScript/question-authoring.test.mjs tests/JavaScript/interactive-checkpoint.test.mjs
php artisan test tests/Unit/Services/Learning/QuestionAuthoringServiceTest.php tests/Unit/Services/Learning/QuestionEvaluatorTest.php
php artisan test tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php tests/Feature/Instructor/QuizQuestionAuthoringRegressionTest.php
php artisan test tests/Feature/Learner/InteractiveCheckpointSchemaTest.php tests/Feature/Learner/InteractiveCheckpointFlowTest.php tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php tests/Feature/Learner/InteractiveCheckpointRenderingTest.php tests/Feature/Learner/InteractiveCheckpointProgressIsolationTest.php tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php
pnpm.cmd build
```

Expected: every command exits successfully; the feature tests confirm neutral completion, stable feedback association, authorization, both placements, and unchanged legacy grading.

- [ ] **Step 8: Run formatting and final repository checks**

Run:

```powershell
vendor/bin/pint --test app/Models/QuizQuestion.php app/Models/QuizOption.php app/Services/Learning/QuestionAuthoringService.php app/Http/Controllers/Instructor/TopicController.php app/Http/Controllers/Learner/InteractiveCheckpointController.php app/Http/Controllers/Learner/LessonController.php app/Services/LearnerModuleCompletionService.php tests/Feature/Learner/PerspectiveFeedbackCheckpointFlowTest.php
git diff --check
git status --short
```

Expected: Pint and whitespace checks pass. `git status --short` lists only intended Perspective Feedback changes plus the user’s pre-existing untracked audio files.

- [ ] **Step 9: Commit the learner UI and regression slice**

```powershell
git add resources/js/interactive-checkpoint.js resources/views/learner/lessons/partials/interactive-checkpoint.blade.php tests/JavaScript/interactive-checkpoint.test.mjs tests/Feature/Learner/InteractiveCheckpointRenderingTest.php tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php
git commit -m "feat(checkpoints): render perspective feedback"
```

- [ ] **Step 10: Perform a final verification from the committed tree**

Run:

```powershell
git status --short
git log -5 --oneline
```

Expected: no uncommitted Perspective Feedback files remain. The unrelated untracked audio files remain untouched, and the recent log contains the schema, server authoring, authoring UI, learner backend, and learner UI commits.
