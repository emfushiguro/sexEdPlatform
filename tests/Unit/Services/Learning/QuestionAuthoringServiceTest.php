<?php

namespace Tests\Unit\Services\Learning;

use App\Models\InteractiveCheckpointProgress;
use App\Models\LessonTopic;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\Learning\QuestionAuthoringService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionAuthoringServiceTest extends TestCase
{
    public function test_perspective_feedback_is_valid_only_in_checkpoint_context(): void
    {
        $request = Request::create('/', 'POST', [
            'question_text' => '<p>How would you respond?</p>',
            'question_type' => 'perspective_feedback',
            'points' => 99,
            'context_description' => 'A friend asks for support.',
            'perspective_options' => [
                ['text' => 'Listen first.', 'feedback' => 'Listening creates room for the person to explain.'],
                ['text' => 'Decide for them.', 'feedback' => 'Support differs from taking control of another person decision.'],
            ],
            'allow_own_perspective' => 1,
            'perspective_prompt' => 'What would you do?',
            'perspective_character_limit' => 1000,
            'reflection_guide' => 'Consider boundaries and possible effects.',
            'explanation' => 'Support should respect the other person agency.',
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

    public function test_creates_multiple_choice_question_with_correct_option(): void
    {
        $quiz = Quiz::factory()->create();

        $question = app(QuestionAuthoringService::class)->createQuestion([
            'question_text' => 'What does consent require?',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'options' => ['Pressure', 'Free agreement'],
            'correct_options' => [1],
            'explanation' => 'Consent must be freely given.',
        ], ['quiz_id' => $quiz->id]);

        $this->assertSame($quiz->id, $question->quiz_id);
        $this->assertSame('Consent must be freely given.', $question->explanation);
        $this->assertTrue($question->options()->where('option_text', 'Free agreement')->first()->is_correct);
    }

    public function test_creates_identification_question_with_image(): void
    {
        Storage::fake('public');
        $quiz = Quiz::factory()->create();

        $question = app(QuestionAuthoringService::class)->createQuestion([
            'question_text' => 'Identify the symbol.',
            'question_type' => 'identification',
            'points' => 1,
            'acceptable_answers' => ['consent'],
            'case_sensitive' => false,
            'image' => UploadedFile::fake()->create('symbol.png', 10, 'image/png'),
        ], ['quiz_id' => $quiz->id]);

        $this->assertSame('consent', $question->acceptable_answers);
        Storage::disk('public')->assertExists($question->image_path);
    }

    public function test_failed_database_write_removes_new_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        try {
            DB::transaction(fn () => app(QuestionAuthoringService::class)->createQuestion([
                'question_text' => 'Identify the symbol.',
                'question_type' => 'identification',
                'points' => 1,
                'acceptable_answers' => ['consent'],
                'case_sensitive' => false,
                'image' => UploadedFile::fake()->create('symbol.png', 10, 'image/png'),
            ], ['quiz_id' => -1]));
            $this->fail('Invalid owner unexpectedly persisted.');
        } catch (QueryException) {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_enclosing_transaction_rollback_removes_new_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $quiz = Quiz::factory()->create();

        try {
            DB::transaction(function () use ($quiz): void {
                app(QuestionAuthoringService::class)->createQuestion([
                    'question_text' => 'Identify the symbol.',
                    'question_type' => 'identification',
                    'points' => 1,
                    'acceptable_answers' => ['consent'],
                    'case_sensitive' => false,
                    'image' => UploadedFile::fake()->create('symbol.png', 10, 'image/png'),
                ], ['quiz_id' => $quiz->id]);

                throw new \RuntimeException('Force outer rollback.');
            });
        } catch (\RuntimeException) {
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_multiple_choice_requires_exactly_one_in_range_correct_option(): void
    {
        $service = app(QuestionAuthoringService::class);

        foreach ([[], [0, 1], [5], [0, 0]] as $correct) {
            try {
                $service->validate(Request::create('/', 'POST', [
                    'question_type' => 'multiple_choice',
                    'question_text' => '<p>Choose one.</p>',
                    'points' => 1,
                    'options' => ['First', 'Second'],
                    'correct_options' => $correct,
                ]));
                $this->fail('Invalid Multiple Choice configuration passed validation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('correct_options', $exception->errors());
            }
        }
    }

    public function test_choice_indices_must_be_real_integers_before_normalization(): void
    {
        $this->expectException(ValidationException::class);

        app(QuestionAuthoringService::class)->validate(Request::create('/', 'POST', [
            'question_type' => 'multiple_choice',
            'question_text' => '<p>Choose one.</p>',
            'points' => 1,
            'options' => ['First', 'Second'],
            'correct_options' => ['banana'],
        ]));
    }

    public function test_choice_types_require_two_non_empty_options_without_a_maximum(): void
    {
        $service = app(QuestionAuthoringService::class);

        $manyOptions = array_map(fn (int $i) => "Option {$i}", range(1, 25));
        $validated = $service->validate(Request::create('/', 'POST', [
            'question_type' => 'multiple_select',
            'question_text' => '<p>Select every valid answer.</p>',
            'points' => 1,
            'options' => $manyOptions,
            'correct_options' => [0, 24],
        ]));

        $this->assertCount(25, $validated['options']);

        $this->expectException(ValidationException::class);
        $service->validate(Request::create('/', 'POST', [
            'question_type' => 'multiple_choice',
            'question_text' => '<p>Choose one.</p>',
            'points' => 1,
            'options' => ['Only one'],
            'correct_options' => [0],
        ]));
    }

    public function test_true_false_normalizes_fixed_options_and_discards_stale_fields(): void
    {
        $validated = app(QuestionAuthoringService::class)->validate(Request::create('/', 'POST', [
            'question_type' => 'true_false',
            'question_text' => '<p>The statement is true.</p>',
            'points' => 1,
            'options' => ['Yes', 'No', 'Maybe'],
            'correct_options' => ['1'],
            'acceptable_answers' => ['stale'],
            'word_bank' => 'stale, values',
            'case_sensitive' => 1,
        ]));

        $this->assertSame(['True', 'False'], $validated['options']);
        $this->assertSame([1], $validated['correct_options']);
        $this->assertArrayNotHasKey('acceptable_answers', $validated);
        $this->assertArrayNotHasKey('word_bank', $validated);
        $this->assertArrayNotHasKey('case_sensitive', $validated);
    }

    public function test_blank_types_require_matching_ordered_answers_and_word_bank_membership(): void
    {
        $service = app(QuestionAuthoringService::class);

        $text = $service->validate(Request::create('/', 'POST', [
            'question_type' => 'fill_blank_text',
            'question_text' => 'The _____ is _____ .',
            'points' => 1,
            'acceptable_answers' => ['color|colour', 'blue'],
            'case_sensitive' => 0,
        ]));
        $this->assertSame(['color|colour', 'blue'], $text['acceptable_answers']);

        $wordBank = $service->validate(Request::create('/', 'POST', [
            'question_type' => 'fill_blank_select',
            'question_text' => '_____ follows _____.',
            'points' => 1,
            'word_bank' => ' beta, alpha, , gamma ',
            'acceptable_answers' => ['alpha', 'beta'],
        ]));
        $this->assertSame('beta, alpha, gamma', $wordBank['word_bank']);

        foreach ([
            ['question_text' => 'No marker', 'acceptable_answers' => ['answer'], 'word_bank' => null],
            ['question_text' => '_____ and _____', 'acceptable_answers' => ['one'], 'word_bank' => null],
            ['question_text' => '_____', 'acceptable_answers' => ['missing'], 'word_bank' => 'present'],
            ['question_text' => '_____', 'acceptable_answers' => ['one'], 'word_bank' => implode(',', range(1, 11))],
        ] as $invalid) {
            try {
                $service->validate(Request::create('/', 'POST', array_filter([
                    'question_type' => $invalid['word_bank'] === null ? 'fill_blank_text' : 'fill_blank_select',
                    'question_text' => $invalid['question_text'],
                    'points' => 1,
                    'acceptable_answers' => $invalid['acceptable_answers'],
                    'word_bank' => $invalid['word_bank'],
                ], fn ($value) => $value !== null)));
                $this->fail('Invalid blank configuration passed validation.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_identification_requires_meaningful_text_and_an_answer(): void
    {
        $this->expectException(ValidationException::class);

        app(QuestionAuthoringService::class)->validate(Request::create('/', 'POST', [
            'question_type' => 'identification',
            'question_text' => '<p><br></p>&nbsp;',
            'points' => 1,
            'acceptable_answers' => [''],
        ]));
    }

    public function test_text_answers_reject_reserved_or_empty_delimiters(): void
    {
        $service = app(QuestionAuthoringService::class);

        foreach ([
            ['fill_blank_text', '_____', ['alpha;beta'], null],
            ['fill_blank_text', '_____', ['alpha|'], null],
            ['identification', 'Name it.', ['alpha|beta'], null],
            ['fill_blank_select', '_____', ['alpha;beta'], 'alpha;beta'],
        ] as [$type, $text, $answers, $wordBank]) {
            try {
                $service->validate(Request::create('/', 'POST', array_filter([
                    'question_type' => $type,
                    'question_text' => $text,
                    'points' => 1,
                    'acceptable_answers' => $answers,
                    'word_bank' => $wordBank,
                ], fn ($value) => $value !== null)));
                $this->fail("Reserved delimiters passed validation for {$type}.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('acceptable_answers', $exception->errors());
            }
        }
    }

    public function test_update_to_choice_type_clears_text_state_and_deletes_identification_image(): void
    {
        Storage::fake('public');
        $author = User::factory()->create();
        $this->actingAs($author);
        $quiz = Quiz::factory()->create();
        $service = app(QuestionAuthoringService::class);
        $image = UploadedFile::fake()->create('prompt.png', 10, 'image/png');
        $createRequest = Request::create('/', 'POST', [
            'question_type' => 'identification',
            'question_text' => '<p>Name it.</p>',
            'points' => 1,
            'acceptable_answers' => ['Consent'],
            'case_sensitive' => 1,
            'explanation' => 'Helpful feedback.',
        ], [], ['image' => $image]);
        $question = $service->createQuestion($service->validate($createRequest), [
            'quiz_id' => $quiz->id,
            'order' => 1,
        ]);
        $oldPath = $question->image_path;
        Storage::disk('public')->assertExists($oldPath);

        $update = Request::create('/', 'PUT', [
            'question_type' => 'multiple_choice',
            'question_text' => '<p>Choose one.</p>',
            'points' => 1,
            'options' => ['A', 'B'],
            'correct_options' => [0],
            'acceptable_answers' => ['stale'],
            'word_bank' => 'stale, words',
            'case_sensitive' => 1,
            'explanation' => 'Helpful feedback.',
        ]);
        $question = $service->updateQuestion($question, $service->validate($update));

        $this->assertNull($question->acceptable_answers);
        $this->assertNull($question->word_bank);
        $this->assertFalse($question->case_sensitive);
        $this->assertNull($question->image_path);
        $this->assertSame('Helpful feedback.', $question->explanation);
        $this->assertCount(2, $question->options);
    }

    public function test_explicit_image_removal_clears_identification_image(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $quiz = Quiz::factory()->create();
        $service = app(QuestionAuthoringService::class);
        $question = $service->createQuestion([
            'question_text' => 'Name it.',
            'question_type' => 'identification',
            'points' => 1,
            'acceptable_answers' => ['Consent'],
            'case_sensitive' => false,
            'image' => UploadedFile::fake()->create('prompt.png', 10, 'image/png'),
        ], ['quiz_id' => $quiz->id]);

        $question = $service->updateQuestion($question, [
            'question_text' => 'Name it.',
            'question_type' => 'identification',
            'points' => 1,
            'acceptable_answers' => ['Consent'],
            'case_sensitive' => false,
            'remove_existing_image' => true,
        ]);

        $this->assertNull($question->image_path);
    }

    public function test_explanation_is_optional_and_limited_to_five_thousand_characters(): void
    {
        $service = app(QuestionAuthoringService::class);
        $valid = $service->validate(Request::create('/', 'POST', [
            'question_type' => 'true_false',
            'question_text' => '<p>Statement.</p>',
            'points' => 1,
            'correct_options' => [0],
        ]));
        $this->assertArrayNotHasKey('explanation', $valid);

        $this->expectException(ValidationException::class);
        $service->validate(Request::create('/', 'POST', [
            'question_type' => 'true_false',
            'question_text' => '<p>Statement.</p>',
            'points' => 1,
            'correct_options' => [0],
            'explanation' => str_repeat('x', 5001),
        ]));
    }
}
