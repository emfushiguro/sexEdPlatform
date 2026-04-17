<?php

namespace Tests\Feature\Learner;

use App\Http\Middleware\EnsureProfileCompleted;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerTranslatorApiEntitlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProfileCompleted::class);
    }

    public function test_text_translation_endpoints_require_premium_translator_entitlement(): void
    {
        ['learner' => $learner, 'topic' => $topic] = $this->createEnrolledLearnerWithTopic();

        $this->actingAs($learner)
            ->postJson(route('learner.topics.translate', $topic), [
                'target_language' => 'tl',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PREMIUM_TRANSLATOR_REQUIRED')
            ->assertJsonPath('feature_key', SubscriptionFeatureKeys::TEXT_TRANSLATOR);

        $this->actingAs($learner)
            ->postJson(route('learner.translator.translate'), [
                'text' => 'Hello world',
                'target_language' => 'tl',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PREMIUM_TRANSLATOR_REQUIRED')
            ->assertJsonPath('feature_key', SubscriptionFeatureKeys::TEXT_TRANSLATOR);

        $this->actingAs($learner)
            ->postJson(route('learner.translator.page'), [
                'texts' => ['Translate this sentence'],
                'target_language' => 'tl',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PREMIUM_TRANSLATOR_REQUIRED')
            ->assertJsonPath('feature_key', SubscriptionFeatureKeys::TEXT_TRANSLATOR);
    }

    public function test_voice_speech_endpoint_requires_voice_translator_entitlement(): void
    {
        ['learner' => $learner, 'topic' => $topic] = $this->createEnrolledLearnerWithTopic();

        $this->actingAs($learner)
            ->postJson(route('learner.translator.tts'), [
                'topic_id' => $topic->id,
                'text' => 'Read this text aloud.',
                'language_code' => 'en-US',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'PREMIUM_TRANSLATOR_REQUIRED')
            ->assertJsonPath('feature_key', SubscriptionFeatureKeys::VOICE_SPEECH_TRANSLATOR);
    }

    private function createEnrolledLearnerWithTopic(): array
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        $module = Module::factory()->create([
            'is_published' => true,
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
            'order' => 1,
        ]);

        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
            'order' => 1,
            'text_content' => '<p>Lesson text for translator tests.</p>',
        ]);

        ModuleEnrollment::factory()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => 'approved',
        ]);

        return compact('learner', 'module', 'lesson', 'topic');
    }
}
