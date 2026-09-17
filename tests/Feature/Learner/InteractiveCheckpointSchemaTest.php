<?php

namespace Tests\Feature\Learner;

use App\Models\InteractiveCheckpointProgress;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InteractiveCheckpointSchemaTest extends TestCase
{
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

    public function test_checkpoint_question_can_belong_to_lesson_topic_without_formal_quiz(): void
    {
        $module = Module::factory()->create(['is_published' => true]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'is_published' => true]);
        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'interactive_checkpoint',
            'interactive_config' => ['placement' => 'between_topics'],
        ]);

        $question = QuizQuestion::create([
            'quiz_id' => null,
            'checkpoint_topic_id' => $topic->id,
            'question_text' => 'Consent can be withdrawn at any time.',
            'question_type' => 'true_false',
            'points' => 1,
            'order' => 1,
            'explanation' => 'Consent must remain freely given.',
        ]);

        $progress = InteractiveCheckpointProgress::create([
            'user_id' => User::factory()->create(['role' => 'learner'])->id,
            'lesson_topic_id' => $topic->id,
            'quiz_question_id' => $question->id,
            'status' => 'skipped',
            'attempt_count' => 0,
            'completed_at' => now(),
        ]);

        $this->assertTrue($question->is($topic->checkpointQuestion));
        $this->assertSame('skipped', $progress->status);
        $this->assertCount(0, QuizQuestion::formalQuiz()->get());
        $this->assertCount(1, QuizQuestion::checkpoint()->get());
    }

    public function test_instructional_scope_excludes_between_topic_checkpoints(): void
    {
        $lesson = Lesson::factory()->create();
        LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'text', 'order' => 1]);
        LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'interactive_checkpoint', 'order' => 2]);

        $this->assertSame(1, $lesson->topics()->instructional()->count());
    }
}
