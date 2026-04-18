<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleRevision;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ModuleLessonQuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@sexed.platform')->first()
            ?? User::where('role', 'instructor')->first();
        $admin = User::where('email', 'admin@sexed.platform')->first()
            ?? User::where('role', 'admin')->first();

        $moduleThumbnails = $this->samplePublicFiles('modules');
        $lessonImages = $this->samplePublicFiles('lesson-images');
        $quizImages = $this->samplePublicFiles('quiz-images');

        $modules = [
            [
                'title' => 'Growing Safely: Body Boundaries and Trusted Adults',
                'description' => 'A realistic module for late childhood to early adolescence focused on body boundaries, safe communication, and help-seeking habits.',
                'min_age' => 5,
                'max_age' => 12,
                'duration_minutes' => 40,
                'certificate_pass_score' => 75,
                'is_premium' => false,
                'access_type' => 'free',
                'order' => 109,
                'thumbnail_index' => 2,
                'lessons' => [
                    [
                        'title' => 'My Body, My Boundaries',
                        'description' => 'Understand personal space, safe touch, unsafe touch, and respectful communication.',
                        'duration' => 20,
                        'topic_title' => 'Reading: Personal Boundaries in Daily Life',
                        'topic_html' => '<p>Every child has the right to feel safe and respected.</p><ul><li>Your body belongs to you.</li><li>You can say no to touch that makes you uncomfortable.</li><li>Safe adults listen when you share concerns.</li><li>Healthy friendships respect personal space and privacy.</li></ul><p>When unsure, pause and talk to a trusted adult.</p>',
                        'image_pool_index' => 12,
                        'image_count' => 2,
                        'image_captions' => [
                            'Personal boundaries help children feel safe and respected.',
                            'Trusted adults can provide guidance when children feel unsure.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Personal Boundaries',
                            'description' => 'Check understanding of body safety and boundary-setting basics.',
                            'passing_score' => 70,
                            'questions' => [
                                [
                                    'question_text' => 'It is okay to tell a trusted adult when something makes you feel unsafe.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which action best protects personal boundaries?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Keeping uncomfortable experiences secret forever', 'correct' => false],
                                        ['text' => 'Saying no and seeking help from a trusted adult', 'correct' => true],
                                        ['text' => 'Ignoring your feelings when something feels wrong', 'correct' => false],
                                        ['text' => 'Letting others decide what feels safe for you', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Trusted Support and Speaking Up',
                        'description' => 'Practice identifying safe adults and using clear help-seeking steps.',
                        'duration' => 20,
                        'topic_title' => 'Guide: How to Ask for Help',
                        'topic_html' => '<p>Asking for help is a strong and healthy choice.</p><ul><li>Identify trusted adults at home, school, and community health centers.</li><li>Use simple, clear words about what happened.</li><li>If one adult does not listen, tell another trusted adult.</li><li>Keep emergency contacts accessible.</li></ul>',
                        'image_pool_index' => 14,
                        'image_count' => 2,
                        'image_captions' => [
                            'Support networks help children report concerns safely.',
                            'Clear communication is a key part of child safety.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Asking for Help',
                            'description' => 'Assess practical knowledge of help-seeking and support pathways.',
                            'passing_score' => 70,
                            'questions' => [
                                [
                                    'question_text' => 'If one trusted adult does not listen, it is okay to tell another trusted adult.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which is the best help-seeking step when a child feels unsafe?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Stay silent to avoid trouble', 'correct' => false],
                                        ['text' => 'Tell a trusted adult and explain clearly what happened', 'correct' => true],
                                        ['text' => 'Handle the problem alone no matter what', 'correct' => false],
                                        ['text' => 'Share private details publicly online first', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Teen Wellness Essentials: Consent, Boundaries, and Safety',
                'description' => 'A realistic, teen-centered module for Philippine learners covering consent, healthy boundaries, online safety, and trusted support systems.',
                'min_age' => 10,
                'max_age' => 17,
                'duration_minutes' => 50,
                'certificate_pass_score' => 80,
                'is_premium' => false,
                'access_type' => 'free',
                'order' => 110,
                'thumbnail_index' => 0,
                'lessons' => [
                    [
                        'title' => 'Understanding Consent in Real-Life Situations',
                        'description' => 'Learn how consent works in daily interactions, dating contexts, and group pressure situations.',
                        'duration' => 25,
                        'topic_title' => 'Scenario Reading: Consent and Respect',
                        'topic_html' => '<p><strong>Consent</strong> means a clear, voluntary, and informed "yes." It can be withdrawn at any time.</p><p>In school and social settings, consent and boundaries appear in many forms: physical touch, sharing private information, and posting photos online.</p><ul><li>Check in before touching or hugging someone.</li><li>Respect silence or hesitation as a sign to stop and clarify.</li><li>Avoid pressure statements like "If you care about me, you will do this."</li><li>Ask support from trusted adults, guidance counselors, or barangay health centers when you feel unsafe.</li></ul>',
                        'image_pool_index' => 0,
                        'image_count' => 2,
                        'image_captions' => [
                            'Healthy relationships begin with clear communication and respect.',
                            'You can pause, ask questions, or say no at any time.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Consent in Action',
                            'description' => 'Evaluate decision-making in realistic consent and boundary scenarios.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'A person can withdraw consent at any time, even if they agreed earlier.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Your classmate avoids being tagged in a group photo. What is the most respectful response?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Post the photo anyway because everyone else agreed', 'correct' => false],
                                        ['text' => 'Ask privately and respect their choice', 'correct' => true],
                                        ['text' => 'Tease them until they agree', 'correct' => false],
                                        ['text' => 'Send the photo to a different chat without asking', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which two actions support safer boundaries online?',
                                    'question_type' => 'multiple_select',
                                    'points' => 2,
                                    'options' => [
                                        ['text' => 'Share personal photos only when pressured by peers', 'correct' => false],
                                        ['text' => 'Use privacy settings and limit who can contact you', 'correct' => true],
                                        ['text' => 'Talk to a trusted adult when someone sends threatening messages', 'correct' => true],
                                        ['text' => 'Ignore repeated harassment and keep quiet', 'correct' => false],
                                    ],
                                    'image_pool_index' => 0,
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Digital Safety, Help-Seeking, and Peer Pressure',
                        'description' => 'Build practical skills for handling unsafe chats, online pressure, and emotional stress.',
                        'duration' => 25,
                        'topic_title' => 'Practical Guide: Safe Online Behavior',
                        'topic_html' => '<p>Digital spaces can affect mental health and safety. Protecting yourself online includes strong privacy habits and healthy boundaries.</p><ul><li>Never share private images when pressured.</li><li>Save evidence (screenshots) and block/report abusive accounts.</li><li>Reach out to trusted adults, counselors, or local health services.</li><li>Practice emotional regulation: breathe, pause, and ask for support.</li></ul><p>Getting help is a strength, not a weakness.</p>',
                        'image_pool_index' => 3,
                        'image_count' => 2,
                        'image_captions' => [
                            'Use account privacy settings and block unsafe contacts early.',
                            'Support from family and counselors improves safety outcomes.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Online Boundaries and Help-Seeking',
                            'description' => 'Check your readiness to respond to online pressure and unsafe behavior.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'If someone threatens to leak your private messages, the best first step is to keep evidence and seek help.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                    'image_pool_index' => 1,
                                ],
                                [
                                    'question_text' => 'Which action is the safest response to repeated unwanted online messages?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Reply aggressively to stop them', 'correct' => false],
                                        ['text' => 'Block/report account and inform a trusted adult', 'correct' => true],
                                        ['text' => 'Share your password to prove trust', 'correct' => false],
                                        ['text' => 'Meet them alone to resolve the issue quickly', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which support options are appropriate for Filipino teens needing health guidance?',
                                    'question_type' => 'multiple_select',
                                    'points' => 2,
                                    'options' => [
                                        ['text' => 'School guidance office', 'correct' => true],
                                        ['text' => 'Barangay or city health center', 'correct' => true],
                                        ['text' => 'Anonymous rumor pages', 'correct' => false],
                                        ['text' => 'Unverified social media advice only', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Puberty and Self-Care: Practical Health Skills',
                'description' => 'A realistic lesson set on puberty changes, personal hygiene, emotional self-care, and when to seek professional help.',
                'min_age' => 11,
                'max_age' => 16,
                'duration_minutes' => 45,
                'certificate_pass_score' => 80,
                'is_premium' => false,
                'access_type' => 'free',
                'order' => 111,
                'thumbnail_index' => 1,
                'lessons' => [
                    [
                        'title' => 'Body Changes and Daily Hygiene',
                        'description' => 'Understand common puberty changes and healthy routines for confidence and wellbeing.',
                        'duration' => 20,
                        'topic_title' => 'Reading: Puberty Changes with Confidence',
                        'topic_html' => '<p>Puberty is different for everyone. Timelines vary, and that is normal.</p><ul><li>Shower and change clothes regularly.</li><li>Use clean menstrual products and track cycle patterns.</li><li>Maintain oral hygiene and balanced nutrition.</li><li>Discuss concerns early with a trusted adult or health professional.</li></ul>',
                        'image_pool_index' => 6,
                        'image_count' => 2,
                        'image_captions' => [
                            'Daily hygiene routines support physical comfort and confidence.',
                            'Changes happen at different ages and rates for everyone.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Puberty and Hygiene Basics',
                            'description' => 'Assess understanding of healthy puberty and hygiene practices.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'Everyone experiences puberty changes at exactly the same age.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => false],
                                        ['text' => 'False', 'correct' => true],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which habit best supports menstrual hygiene?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Avoid changing pads or napkins for long periods', 'correct' => false],
                                        ['text' => 'Change menstrual products regularly and wash hands', 'correct' => true],
                                        ['text' => 'Use any unclean cloth repeatedly without washing', 'correct' => false],
                                        ['text' => 'Skip hydration during menstruation', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Emotional Health and Support Networks',
                        'description' => 'Recognize stress signs and identify safe ways to ask for support.',
                        'duration' => 25,
                        'topic_title' => 'Guide: Stress, Mood, and Support',
                        'topic_html' => '<p>Puberty can bring stronger emotions. Mood changes are common, but ongoing distress should be addressed with support.</p><ul><li>Use healthy coping habits: sleep, movement, journaling, and trusted conversations.</li><li>Reduce misinformation by using verified health sources.</li><li>Seek help early from counselors, family, or health centers when anxiety or sadness feels overwhelming.</li></ul>',
                        'image_pool_index' => 9,
                        'image_count' => 2,
                        'image_captions' => [
                            'Healthy routines can improve mood regulation over time.',
                            'Trusted support networks are protective factors for teens.',
                        ],
                        'quiz' => [
                            'title' => 'Quiz: Emotional Self-Care',
                            'description' => 'Measure practical understanding of emotional wellbeing and support-seeking.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'Talking to a trusted adult when stress feels overwhelming is a healthy coping step.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which habit is most likely to improve emotional wellbeing?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Regular sleep and balanced routines', 'correct' => true],
                                        ['text' => 'Skipping meals during stressful days', 'correct' => false],
                                        ['text' => 'Keeping serious worries completely secret', 'correct' => false],
                                        ['text' => 'Using harmful online challenges for distraction', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $moduleCount = 0;
        $lessonCount = 0;
        $quizCount = 0;

        foreach ($modules as $moduleIndex => $moduleData) {
            $resolvedLessons = $this->buildLessonsForModule($moduleData, $moduleIndex, 10);
            $resolvedDurationMinutes = array_sum(array_map(
                fn (array $lesson): int => (int) ($lesson['duration'] ?? 0),
                $resolvedLessons
            ));

            $moduleThumbnail = $this->pickOne($moduleThumbnails, $moduleData['thumbnail_index'] ?? $moduleIndex);

            $module = Module::updateOrCreate(
                ['title' => $moduleData['title']],
                [
                    'description' => $moduleData['description'],
                    'thumbnail' => $moduleThumbnail,
                    'min_age' => $moduleData['min_age'],
                    'max_age' => $moduleData['max_age'],
                    'age_specific_content' => null,
                    'order' => $moduleData['order'],
                    'duration_minutes' => max((int) ($moduleData['duration_minutes'] ?? 0), $resolvedDurationMinutes),
                    'is_published' => true,
                    'is_premium' => $moduleData['is_premium'],
                    'access_type' => $moduleData['access_type'],
                    'price_amount' => null,
                    'price_currency' => 'PHP',
                    'enrollment_limit' => null,
                    'enrollment_mode' => 'auto',
                    'content_owner_type' => 'instructor',
                    'published_revision_id' => null,
                    'published_by_admin_id' => $admin?->id,
                    'current_review_status' => 'approved',
                    'certificate_pass_score' => $moduleData['certificate_pass_score'],
                    'created_by' => $instructor?->id,
                ]
            );

            $moduleCount++;

            foreach ($resolvedLessons as $lessonIndex => $lessonData) {
                $lesson = Lesson::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'title' => $lessonData['title'],
                    ],
                    [
                        'description' => $lessonData['description'],
                        'order' => $lessonIndex + 1,
                        'duration' => $lessonData['duration'],
                        'is_published' => true,
                        'text_content' => strip_tags($lessonData['topic_html']),
                    ]
                );

                $lessonCount++;

                $topicAttachments = $this->buildImageAttachments(
                    $this->pickMany(
                        $lessonImages,
                        (int) ($lessonData['image_pool_index'] ?? $lessonIndex),
                        (int) ($lessonData['image_count'] ?? 2),
                    ),
                    $lessonData['image_captions'] ?? [],
                );

                LessonTopic::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'title' => $lessonData['topic_title'],
                        'type' => 'text',
                    ],
                    [
                        'text_content' => $lessonData['topic_html'],
                        'image_attachments' => $topicAttachments ?: null,
                        'slideshow_data' => $topicAttachments ? [
                            'enabled' => true,
                            'gallery_mode' => 'grid',
                            'slideshow_mode' => 'slide',
                            'auto_play' => false,
                            'show_thumbnails' => true,
                            'allow_toggle' => true,
                        ] : null,
                        'duration' => max(1, $lessonData['duration'] - 5),
                        'is_prerequisite' => true,
                        'order' => 1,
                    ]
                );

                $quiz = Quiz::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'title' => $lessonData['quiz']['title'],
                    ],
                    [
                        'module_id' => $module->id,
                        'description' => $lessonData['quiz']['description'],
                        'passing_score' => $lessonData['quiz']['passing_score'],
                        'time_limit' => null,
                        'attempt_limit' => 3,
                        'is_active' => true,
                    ]
                );

                $quizCount++;

                LessonTopic::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'title' => $lessonData['quiz']['title'],
                        'type' => 'quiz',
                    ],
                    [
                        'quiz_id' => $quiz->id,
                        'duration' => 5,
                        'is_prerequisite' => false,
                        'order' => 2,
                    ]
                );

                $quizQuestions = array_map(function (array $questionData) use ($quizImages): array {
                    if (isset($questionData['image_pool_index'])) {
                        $questionData['image_path'] = $this->pickOne($quizImages, (int) $questionData['image_pool_index']);
                        unset($questionData['image_pool_index']);
                    }

                    return $questionData;
                }, $lessonData['quiz']['questions']);

                $this->syncQuizQuestions($quiz, $quizQuestions);
            }

            $this->syncApprovedPublishedRevision($module, $admin, $instructor);
        }

        if ($this->command) {
            $this->command->info("Seeded {$moduleCount} modules, {$lessonCount} lessons, and {$quizCount} quizzes.");
        }
    }

    /**
     * @param array<string, mixed> $moduleData
     * @return array<int, array<string, mixed>>
     */
    private function buildLessonsForModule(array $moduleData, int $moduleIndex, int $targetCount = 10): array
    {
        $lessons = $moduleData['lessons'] ?? [];
        $targetCount = max(1, $targetCount);

        for ($i = count($lessons); $i < $targetCount; $i++) {
            $topicNumber = $i + 1;
            $lessons[] = [
                'title' => "Lesson {$topicNumber}: Core Safety Skill {$topicNumber}",
                'description' => "Age-appropriate practice lesson {$topicNumber} focused on healthy boundaries, communication, and support-seeking.",
                'duration' => 15,
                'topic_title' => "Topic {$topicNumber}: Practical Safety Checkpoint",
                'topic_html' => $this->buildGeneratedTopicHtml((string) $moduleData['title'], $topicNumber),
                'image_pool_index' => ($moduleIndex * 20) + $topicNumber,
                'image_count' => 2,
                'image_captions' => [
                    "Topic {$topicNumber} visual scenario for {$moduleData['title']}.",
                    "Learner reflection prompt for topic {$topicNumber}.",
                ],
                'quiz' => [
                    'title' => "Quiz {$topicNumber}: Topic {$topicNumber} Check",
                    'description' => "Quiz {$topicNumber} verifies understanding of topic {$topicNumber} for this module.",
                    'passing_score' => (int) ($moduleData['certificate_pass_score'] ?? 75),
                    'questions' => $this->buildGeneratedQuizQuestions(
                        $topicNumber,
                        (string) $moduleData['title'],
                        ($moduleIndex * 100) + ($topicNumber * 10)
                    ),
                ],
            ];
        }

        return array_slice($lessons, 0, $targetCount);
    }

    private function buildGeneratedTopicHtml(string $moduleTitle, int $topicNumber): string
    {
        return "<p><strong>Topic {$topicNumber}</strong> in <strong>{$moduleTitle}</strong> reinforces respectful behavior, safety awareness, and help-seeking confidence.</p>"
            . '<ul>'
            . '<li>Recognize body and communication boundaries in everyday situations.</li>'
            . '<li>Use clear words to ask for consent, support, or personal space.</li>'
            . '<li>Identify trusted adults and local support channels when safety is at risk.</li>'
            . '<li>Practice decision-making that protects wellbeing online and offline.</li>'
            . '</ul>'
            . "<p>Reflection task: write one safe action you can apply after Topic {$topicNumber}.</p>";
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildGeneratedQuizQuestions(int $topicNumber, string $moduleTitle, int $seed): array
    {
        return [
            [
                'question_text' => "Topic {$topicNumber}: Respecting boundaries helps keep people safe in {$moduleTitle} scenarios.",
                'question_type' => 'true_false',
                'points' => 1,
                'image_pool_index' => $seed,
                'options' => [
                    ['text' => 'True', 'correct' => true],
                    ['text' => 'False', 'correct' => false],
                ],
            ],
            [
                'question_text' => "For Topic {$topicNumber}, which action is the healthiest first response when a learner feels unsafe?",
                'question_type' => 'multiple_choice',
                'points' => 1,
                'image_pool_index' => $seed + 1,
                'options' => [
                    ['text' => 'Stay silent and hide the concern', 'correct' => false],
                    ['text' => 'Talk to a trusted adult and explain clearly', 'correct' => true],
                    ['text' => 'Share private details publicly online', 'correct' => false],
                    ['text' => 'Follow peer pressure to avoid conflict', 'correct' => false],
                ],
            ],
            [
                'question_text' => "Select the two safety-support steps emphasized in Topic {$topicNumber}.",
                'question_type' => 'multiple_select',
                'points' => 2,
                'image_pool_index' => $seed + 2,
                'options' => [
                    ['text' => 'Set boundaries and communicate them calmly', 'correct' => true],
                    ['text' => 'Keep repeated harmful behavior secret forever', 'correct' => false],
                    ['text' => 'Reach out to trusted support channels', 'correct' => true],
                    ['text' => 'Ignore warning signs to avoid attention', 'correct' => false],
                ],
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     */
    private function syncQuizQuestions(Quiz $quiz, array $questions): void
    {
        $keptQuestionIds = [];

        foreach ($questions as $questionIndex => $questionData) {
            $question = QuizQuestion::updateOrCreate(
                [
                    'quiz_id' => $quiz->id,
                    'order' => $questionIndex + 1,
                ],
                [
                    'question_text' => $questionData['question_text'],
                    'question_type' => $questionData['question_type'],
                    'points' => $questionData['points'] ?? 1,
                    'acceptable_answers' => null,
                    'case_sensitive' => false,
                    'word_bank' => null,
                    'image_path' => $this->normalizePublicPath($questionData['image_path'] ?? null),
                ]
            );

            $keptQuestionIds[] = $question->id;

            $keptOptionIds = [];

            foreach ($questionData['options'] as $optionIndex => $optionData) {
                $option = QuizOption::updateOrCreate(
                    [
                        'quiz_question_id' => $question->id,
                        'order' => $optionIndex + 1,
                    ],
                    [
                        'option_text' => $optionData['text'],
                        'is_correct' => (bool) $optionData['correct'],
                    ]
                );

                $keptOptionIds[] = $option->id;
            }

            QuizOption::where('quiz_question_id', $question->id)
                ->whereNotIn('id', $keptOptionIds)
                ->delete();
        }

        QuizQuestion::where('quiz_id', $quiz->id)
            ->whereNotIn('id', $keptQuestionIds)
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    private function samplePublicFiles(string $directory): array
    {
        if (!Storage::disk('public')->exists($directory)) {
            return [];
        }

        return collect(Storage::disk('public')->files($directory))
            ->filter(function (string $path): bool {
                return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $path);
            })
            ->sort()
            ->values()
            ->all();
    }

    private function pickOne(array $pool, int $index): ?string
    {
        if ($pool === []) {
            return null;
        }

        return $this->normalizePublicPath($pool[$index % count($pool)]);
    }

    /**
     * @return array<int, string>
     */
    private function pickMany(array $pool, int $startIndex, int $count): array
    {
        if ($pool === [] || $count <= 0) {
            return [];
        }

        $picked = [];
        $total = count($pool);

        for ($i = 0; $i < $count; $i++) {
            $picked[] = $this->normalizePublicPath($pool[($startIndex + $i) % $total]);
        }

        return array_values(array_unique(array_filter($picked)));
    }

    /**
     * @param array<int, string> $paths
     * @param array<int, string> $captions
     * @return array<int, array<string, string|null>>
     */
    private function buildImageAttachments(array $paths, array $captions = []): array
    {
        $attachments = [];

        foreach ($paths as $index => $path) {
            $attachments[] = [
                'path' => $path,
                'caption' => $captions[$index] ?? null,
                'original_name' => basename($path),
            ];
        }

        return $attachments;
    }

    private function normalizePublicPath(?string $path): ?string                                                                                                
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim($path, '/');

        if (str_starts_with($normalized, 'storage/')) {
            return substr($normalized, 8);
        }

        return $normalized;
    }

    private function syncApprovedPublishedRevision(Module $module, ?User $admin, ?User $instructor): void
    {
        $module->loadMissing([
            'lessons.topics',
            'quizzes.questions.options',
            'finalQuiz.questions.options',
        ]);

        $snapshot = $this->buildSnapshotPayload($module);

        $revision = ModuleRevision::updateOrCreate(
            [
                'module_id' => $module->id,
                'revision_number' => 1,
            ],
            [
                'snapshot_payload' => $snapshot,
                'submitted_by' => $instructor?->id ?? $admin?->id,
                'status' => 'approved',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $admin?->id,
                'review_feedback' => 'Auto-approved by ModuleLessonQuizSeeder.',
            ]
        );

        $module->forceFill([
            'is_published' => true,
            'content_owner_type' => 'instructor',
            'published_revision_id' => $revision->id,
            'published_by_admin_id' => $admin?->id,
            'current_review_status' => 'approved',
        ])->save();
    }

    private function buildSnapshotPayload(Module $module): array
    {
        return [
            'module' => $module->only([
                'id',
                'title',
                'description',
                'thumbnail',
                'min_age',
                'max_age',
                'age_specific_content',
                'order',
                'duration_minutes',
                'is_published',
                'is_premium',
                'access_type',
                'price_amount',
                'price_currency',
                'enrollment_limit',
                'enrollment_mode',
                'final_quiz_id',
                'certificate_pass_score',
                'created_by',
                'content_owner_type',
            ]),
            'lessons' => $module->lessons->map(fn (Lesson $lesson) => [
                'attributes' => $lesson->only([
                    'id',
                    'module_id',
                    'title',
                    'description',
                    'order',
                    'duration',
                    'is_published',
                    'text_content',
                ]),
                'topics' => $lesson->topics->map(fn (LessonTopic $topic) => $topic->only([
                    'id',
                    'lesson_id',
                    'title',
                    'type',
                    'video_provider',
                    'video_id',
                    'video_file_path',
                    'text_content',
                    'file_path',
                    'quiz_id',
                    'interactive_config',
                    'image_attachments',
                    'slideshow_data',
                    'duration',
                    'is_prerequisite',
                    'order',
                ]))->values()->all(),
            ])->values()->all(),
            'quizzes' => $module->quizzes->map(fn (Quiz $quiz) => [
                'attributes' => $quiz->only([
                    'id',
                    'module_id',
                    'lesson_id',
                    'title',
                    'description',
                    'passing_score',
                    'time_limit',
                    'attempt_limit',
                    'is_active',
                ]),
                'questions' => $quiz->questions->map(fn (QuizQuestion $question) => [
                    'attributes' => $question->only([
                        'id',
                        'quiz_id',
                        'question_text',
                        'question_type',
                        'points',
                        'order',
                        'acceptable_answers',
                        'case_sensitive',
                        'word_bank',
                        'image_path',
                    ]),
                    'options' => $question->options->map(fn (QuizOption $option) => $option->only([
                        'id',
                        'quiz_question_id',
                        'option_text',
                        'is_correct',
                        'order',
                    ]))->values()->all(),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}                                           
