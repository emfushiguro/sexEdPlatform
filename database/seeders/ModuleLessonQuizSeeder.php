<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModuleLessonQuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructor = User::where('email', 'instructor@sexed.platform')->first()
            ?? User::where('role', 'instructor')->first();

        $modules = [
            [
                'title' => 'Seeder Module: Puberty Basics',
                'description' => 'Starter module covering body changes, hygiene, and emotional awareness during puberty.',
                'min_age' => 12,
                'max_age' => 17,
                'duration_minutes' => 35,
                'certificate_pass_score' => 75,
                'is_premium' => false,
                'access_type' => 'free',
                'order' => 100,
                'lessons' => [
                    [
                        'title' => 'Physical Changes',
                        'description' => 'Common physical changes during puberty and how to respond to them.',
                        'duration' => 15,
                        'topic_title' => 'Reading: Understanding Body Changes',
                        'topic_html' => '<p>Puberty is a natural process where your body changes over time. Everyone develops at their own pace.</p>',
                        'quiz' => [
                            'title' => 'Quiz: Physical Changes',
                            'description' => 'Checks understanding of basic puberty body changes.',
                            'passing_score' => 70,
                            'questions' => [
                                [
                                    'question_text' => 'Puberty happens at exactly the same age for everyone.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => false],
                                        ['text' => 'False', 'correct' => true],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which is a healthy way to handle body changes?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Ignore hygiene routines', 'correct' => false],
                                        ['text' => 'Ask trusted adults and maintain hygiene', 'correct' => true],
                                        ['text' => 'Compare yourself harshly with friends', 'correct' => false],
                                        ['text' => 'Avoid learning about puberty', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Emotions and Mood',
                        'description' => 'Recognizing emotions and using healthy coping strategies.',
                        'duration' => 20,
                        'topic_title' => 'Reading: Emotional Changes',
                        'topic_html' => '<p>It is normal to feel stronger emotions during puberty. Healthy coping includes communication, rest, and support.</p>',
                        'quiz' => [
                            'title' => 'Quiz: Emotions and Mood',
                            'description' => 'Checks understanding of emotional health during puberty.',
                            'passing_score' => 70,
                            'questions' => [
                                [
                                    'question_text' => 'A healthy coping strategy is to talk with someone you trust.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which action best supports emotional wellbeing?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Suppress all emotions', 'correct' => false],
                                        ['text' => 'Use healthy routines and ask for help', 'correct' => true],
                                        ['text' => 'Skip sleep regularly', 'correct' => false],
                                        ['text' => 'Isolate yourself from everyone', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Seeder Module: Consent and Boundaries',
                'description' => 'Foundational module on consent, respect, and personal boundaries in everyday situations.',
                'min_age' => 15,
                'max_age' => 100,
                'duration_minutes' => 30,
                'certificate_pass_score' => 80,
                'is_premium' => false,
                'access_type' => 'free',
                'order' => 101,
                'lessons' => [
                    [
                        'title' => 'What Consent Means',
                        'description' => 'Understanding clear, informed, and voluntary agreement.',
                        'duration' => 15,
                        'topic_title' => 'Reading: Consent Essentials',
                        'topic_html' => '<p>Consent means a clear yes given freely. Silence or pressure is not consent.</p>',
                        'quiz' => [
                            'title' => 'Quiz: Consent Essentials',
                            'description' => 'Checks understanding of basic consent principles.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'Silence should always be treated as consent.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => false],
                                        ['text' => 'False', 'correct' => true],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which best describes valid consent?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Given under pressure', 'correct' => false],
                                        ['text' => 'Freely given and can be withdrawn', 'correct' => true],
                                        ['text' => 'Assumed from past interactions', 'correct' => false],
                                        ['text' => 'Required only once', 'correct' => false],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'title' => 'Respecting Boundaries',
                        'description' => 'How to communicate, set, and respect personal boundaries.',
                        'duration' => 15,
                        'topic_title' => 'Reading: Boundary Scenarios',
                        'topic_html' => '<p>Healthy relationships respect personal limits and communication. You can always say no.</p>',
                        'quiz' => [
                            'title' => 'Quiz: Respecting Boundaries',
                            'description' => 'Checks understanding of communication and respect.',
                            'passing_score' => 75,
                            'questions' => [
                                [
                                    'question_text' => 'If someone says no, the respectful response is to stop and listen.',
                                    'question_type' => 'true_false',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'True', 'correct' => true],
                                        ['text' => 'False', 'correct' => false],
                                    ],
                                ],
                                [
                                    'question_text' => 'Which behavior best respects boundaries?',
                                    'question_type' => 'multiple_choice',
                                    'points' => 1,
                                    'options' => [
                                        ['text' => 'Continuing after refusal', 'correct' => false],
                                        ['text' => 'Asking clearly and accepting answers', 'correct' => true],
                                        ['text' => 'Using guilt to change minds', 'correct' => false],
                                        ['text' => 'Ignoring nonverbal discomfort', 'correct' => false],
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

        foreach ($modules as $moduleData) {
            $module = Module::updateOrCreate(
                ['title' => $moduleData['title']],
                [
                    'description' => $moduleData['description'],
                    'thumbnail' => null,
                    'min_age' => $moduleData['min_age'],
                    'max_age' => $moduleData['max_age'],
                    'age_specific_content' => null,
                    'order' => $moduleData['order'],
                    'duration_minutes' => $moduleData['duration_minutes'],
                    'is_published' => true,
                    'is_premium' => $moduleData['is_premium'],
                    'access_type' => $moduleData['access_type'],
                    'price_amount' => null,
                    'price_currency' => 'PHP',
                    'enrollment_limit' => null,
                    'enrollment_mode' => 'auto',
                    'certificate_pass_score' => $moduleData['certificate_pass_score'],
                    'created_by' => $instructor?->id,
                ]
            );

            $moduleCount++;

            foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
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

                LessonTopic::updateOrCreate(
                    [
                        'lesson_id' => $lesson->id,
                        'title' => $lessonData['topic_title'],
                        'type' => 'text',
                    ],
                    [
                        'text_content' => $lessonData['topic_html'],
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

                $this->syncQuizQuestions($quiz, $lessonData['quiz']['questions']);
            }
        }

        if ($this->command) {
            $this->command->info("Seeded {$moduleCount} modules, {$lessonCount} lessons, and {$quizCount} quizzes.");
        }
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
                    'image_path' => null,
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
}
