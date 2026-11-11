<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LearningPath;
use App\Models\Module;
use App\Models\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalLearningPathDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local') || config('database.default') !== 'mysql'
            || config('database.connections.mysql.host') !== '127.0.0.1'
            || config('database.connections.mysql.database') !== 'cc_db') {
            throw new RuntimeException('Demo seeding is restricted to the local cc_db database.');
        }

        $titles = ['[Demo Journey] Understanding boundaries', '[Demo Journey] Respectful communication', '[Demo Journey] Finding support', '[Demo Journey] Everyday wellbeing'];
        $pathTitle = '[Demo Journey] Building healthy connections';
        if (LearningPath::where('title', $pathTitle)->exists() || Module::withTrashed()->whereIn('title', $titles)->exists()) {
            $this->command?->warn('Demo content already exists; existing data was left untouched.');

            return;
        }

        DB::transaction(function () use ($titles, $pathTitle): void {
            $modules = [];
            foreach ($titles as $index => $title) {
                $module = Module::create([
                    'title' => $title, 'description' => 'Local demonstration content for testing the learning journey.',
                    'min_age' => 5, 'max_age' => 100, 'order' => 9000 + $index,
                    'duration_minutes' => 5, 'is_published' => true, 'is_premium' => false,
                    'access_type' => 'free', 'enrollment_mode' => 'auto', 'content_owner_type' => 'admin',
                    'certificate_pass_score' => 70,
                ]);
                $module->syncLearnerCategories(['kids', 'teens', 'adults']);
                $lesson = $module->lessons()->create([
                    'title' => 'A short introduction', 'description' => 'Read and reflect on this example.',
                    'order' => 1, 'duration' => 5, 'is_published' => true,
                ]);
                $lesson->topics()->create([
                    'title' => 'Learning together', 'type' => 'text', 'order' => 1, 'duration' => 5,
                    'text_content' => '<p>Respect, clear communication, and trusted support help people build healthy connections.</p>',
                ]);
                $quiz = Quiz::create(['module_id' => $module->id, 'title' => 'Journey check', 'passing_score' => 70, 'is_active' => true]);
                $question = $quiz->questions()->create([
                    'question_text' => 'Which action supports a healthy connection?', 'question_type' => 'multiple_choice', 'points' => 1, 'order' => 1,
                ]);
                $question->options()->createMany([
                    ['option_text' => 'Listening and respecting boundaries', 'is_correct' => true, 'order' => 1],
                    ['option_text' => 'Ignoring someone’s wishes', 'is_correct' => false, 'order' => 2],
                ]);
                $module->update(['final_quiz_id' => $quiz->id]);
                $modules[] = $module;
            }

            $path = LearningPath::create([
                'title' => $pathTitle, 'description' => 'A short local demonstration journey through healthy connections.', 'status' => LearningPath::STATUS_PUBLISHED,
            ]);
            foreach (['kids', 'teens', 'adults'] as $category) {
                $path->learnerCategories()->create(['category' => $category]);
            }
            foreach (array_slice($modules, 0, 3) as $index => $module) {
                $path->pathModules()->create(['module_id' => $module->id, 'position' => $index + 1]);
            }
        });

        $this->command?->info('Created one demo path, three path modules, and one standalone module.');
    }
}
