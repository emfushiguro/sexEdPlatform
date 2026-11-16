<?php

namespace Tests\Feature\Learner;

// Filesystem checks must not bootstrap Laravel or refresh and seed a database.
use PHPUnit\Framework\TestCase;

class LearningFeedbackAssetTest extends TestCase
{
    private const FILES = [
        'activity-completed.svg',
        'correct-answer.svg',
        'incorrect-answer.svg',
        'informational.svg',
        'lesson-completed.svg',
        'try-again.svg',
        'warning.svg',
    ];

    public function test_learning_feedback_uses_exactly_seven_safe_local_svgs(): void
    {
        $directory = dirname(__DIR__, 3).'/public/assets/illustrations/learning-feedback';
        $actual = collect(glob($directory.'/*.svg') ?: [])->map('basename')->sort()->values()->all();
        $expected = collect(self::FILES)->sort()->values()->all();

        $this->assertSame($expected, $actual);

        foreach (self::FILES as $filename) {
            $contents = file_get_contents($directory.'/'.$filename);
            $this->assertNotFalse($contents, $filename);
            $this->assertNotSame('', trim($contents), $filename);
            $this->assertMatchesRegularExpression('/<svg\b/i', $contents, $filename);
            $this->assertMatchesRegularExpression('/\bviewBox\s*=/i', $contents, $filename);
            $this->assertDoesNotMatchRegularExpression('/<(script|foreignObject)\b/i', $contents, $filename);
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $contents, $filename);
            $this->assertDoesNotMatchRegularExpression('/javascript\s*:/i', $contents, $filename);
            $this->assertDoesNotMatchRegularExpression('/(?:href|src)\s*=\s*["\']\s*(?:https?:|\/\/|data:)/i', $contents, $filename);
        }
    }

    public function test_error_page_illustrations_are_not_learning_feedback_assets(): void
    {
        $directory = dirname(__DIR__, 3).'/public/assets/illustrations/learning-feedback';
        $this->assertFileDoesNotExist($directory.'/error-page.svg');
        $this->assertFileDoesNotExist($directory.'/itsnotyouitsus.svg');
    }
}
