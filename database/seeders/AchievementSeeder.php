<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            // Quiz Achievements
            [
                'title' => 'First Quiz Master',
                'description' => 'Complete your first quiz',
                'badge_icon' => 'quiz-beginner.svg',
                'points' => 10,
            ],
            [
                'title' => 'Quiz Enthusiast',
                'description' => 'Complete 10 quizzes',
                'badge_icon' => 'quiz-enthusiast.svg',
                'points' => 50,
            ],
            [
                'title' => 'Quiz Champion',
                'description' => 'Complete 50 quizzes',
                'badge_icon' => 'quiz-champion.svg',
                'points' => 200,
            ],
            [
                'title' => 'Perfect Score',
                'description' => 'Get a perfect score on any quiz',
                'badge_icon' => 'perfect-score.svg',
                'points' => 25,
            ],

            // Module Completion Achievements
            [
                'title' => 'Knowledge Seeker',
                'description' => 'Complete your first module',
                'badge_icon' => 'module-beginner.svg',
                'points' => 20,
            ],
            [
                'title' => 'Learning Journey',
                'description' => 'Complete 5 modules',
                'badge_icon' => 'module-journeyman.svg',
                'points' => 100,
            ],
            [
                'title' => 'Master Learner',
                'description' => 'Complete all available modules',
                'badge_icon' => 'module-master.svg',
                'points' => 500,
            ],

            // Streak Achievements
            [
                'title' => 'Dedicated Learner',
                'description' => 'Maintain a 7-day learning streak',
                'badge_icon' => 'streak-week.svg',
                'points' => 30,
            ],
            [
                'title' => 'Consistency Master',
                'description' => 'Maintain a 30-day learning streak',
                'badge_icon' => 'streak-month.svg',
                'points' => 150,
            ],
            [
                'title' => 'Unstoppable Force',
                'description' => 'Maintain a 100-day learning streak',
                'badge_icon' => 'streak-legend.svg',
                'points' => 500,
            ],

            // Certificate Achievements
            [
                'title' => 'Certified',
                'description' => 'Earn your first certificate',
                'badge_icon' => 'certificate-first.svg',
                'points' => 50,
            ],
            [
                'title' => 'Certificate Collector',
                'description' => 'Earn 5 certificates',
                'badge_icon' => 'certificate-collector.svg',
                'points' => 250,
            ],

            // Seminar Achievements
            [
                'title' => 'Seminar Attendee',
                'description' => 'Attend your first seminar',
                'badge_icon' => 'seminar-first.svg',
                'points' => 30,
            ],
            [
                'title' => 'Active Participant',
                'description' => 'Attend 5 seminars',
                'badge_icon' => 'seminar-active.svg',
                'points' => 150,
            ],

            // Consultation Achievements
            [
                'title' => 'Help Seeker',
                'description' => 'Request your first consultation',
                'badge_icon' => 'consultation-first.svg',
                'points' => 15,
            ],

            // Level Achievements
            [
                'title' => 'Rising Star',
                'description' => 'Reach Level 5',
                'badge_icon' => 'level-5.svg',
                'points' => 100,
            ],
            [
                'title' => 'Expert',
                'description' => 'Reach Level 10',
                'badge_icon' => 'level-10.svg',
                'points' => 200,
            ],
            [
                'title' => 'Legend',
                'description' => 'Reach Level 20',
                'badge_icon' => 'level-20.svg',
                'points' => 500,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }

        $this->command->info('Achievements created successfully!');
    }
}
