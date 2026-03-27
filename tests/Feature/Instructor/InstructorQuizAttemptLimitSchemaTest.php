<?php

namespace Tests\Feature\Instructor;

use App\Models\Quiz;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InstructorQuizAttemptLimitSchemaTest extends TestCase
{
    public function test_quizzes_table_has_attempt_limit_and_quiz_model_casts_it(): void
    {
        $this->assertTrue(Schema::hasColumn('quizzes', 'attempt_limit'));

        $quiz = new Quiz;
        $this->assertArrayHasKey('attempt_limit', $quiz->getCasts());
        $this->assertSame('integer', $quiz->getCasts()['attempt_limit']);
    }
}
