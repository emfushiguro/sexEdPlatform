<?php

namespace Database\Seeders;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Getting Started', 'getting-started', 'Start with the basics.', 'rocket', ['all']],
            ['Account and Profile', 'account-and-profile', 'Manage account and profile details.', 'account', ['all']],
            ['Learning and Modules', 'learning-and-modules', 'Find modules and track learning.', 'book', ['learner', 'parent']],
            ['Quizzes and Certificates', 'quizzes-and-certificates', 'Take quizzes and find certificates.', 'quiz', ['learner', 'parent']],
            ['Seminars', 'seminars', 'Find and join learning events.', 'seminar', ['all']],
            ['Community Hub', 'community-hub', 'Understand the moderated community space.', 'community', ['learner', 'connector', 'admin']],
            ['Parent and Guardian Support', 'parent-and-guardian-support', 'Manage parent and dependent workflows.', 'guardian', ['parent']],
            ['Instructor Tools', 'instructor-tools', 'Create and manage teaching content.', 'instructor', ['instructor', 'admin']],
            ['Connector Tools', 'connector-tools', 'Use your organization workspace.', 'connector', ['connector', 'admin']],
            ['Payments and Subscriptions', 'payments-and-subscriptions', 'Review plans, payments, and receipts.', 'payment', ['all']],
            ['Privacy and Safety', 'privacy-and-safety', 'Find reporting and safety guidance.', 'shield', ['all']],
            ['Accessibility', 'accessibility', 'Use language and accessibility controls.', 'accessibility', ['all']],
            ['Troubleshooting', 'troubleshooting', 'Fix common sign-in and navigation problems.', 'tools', ['all']],
        ];

        $articles = [
            ['getting-started', 'Navigate Conscious Connections', ['all'], ['Sign in', 'Open your dashboard', 'Use the main navigation']],
            ['account-and-profile', 'Update Your Profile', ['learner', 'parent', 'instructor', 'admin'], ['Open profile settings', 'Review your information', 'Save changes']],
            ['learning-and-modules', 'Find and Start a Learning Module', ['learner', 'parent'], ['Browse modules', 'Review module details', 'Enroll and begin']],
            ['quizzes-and-certificates', 'Take a Quiz and View Your Results', ['learner', 'parent'], ['Open the lesson quiz', 'Submit answers', 'Review results and certificates']],
            ['seminars', 'Find and Join a Seminar', ['learner', 'parent', 'instructor', 'connector'], ['Browse seminars', 'Review event details', 'Register or attend']],
            ['community-hub', 'Use Community Hub Safely', ['learner', 'connector', 'admin'], ['Confirm access', 'Read and participate', 'Report unsafe content']],
            ['parent-and-guardian-support', 'Review a Dependent Enrollment Request', ['parent'], ['Open My Children', 'Review the request', 'Approve or reject']],
            ['instructor-tools', 'Submit a Module for Review', ['instructor', 'admin'], ['Check module readiness', 'Submit for review', 'Track the decision']],
            ['connector-tools', 'Open Your Connector Workspace', ['connector', 'admin'], ['Choose a connector', 'Review workspace status', 'Open members, seminars, or community']],
            ['payments-and-subscriptions', 'Review Subscription and Payment Status', ['learner', 'parent', 'instructor', 'connector', 'admin'], ['Open subscriptions', 'Review payment status', 'Find receipts or next actions']],
            ['privacy-and-safety', 'Report Unsafe or Inappropriate Content', ['all'], ['Use the content report action', 'Describe the concern', 'Follow status updates']],
            ['accessibility', 'Use Language and Accessibility Controls', ['all'], ['Find available controls', 'Change language or playback', 'Send an accessibility issue']],
            ['troubleshooting', 'Fix Common Sign-in and Navigation Problems', ['all'], ['Confirm account and connection', 'Refresh safely', 'Ask for platform help']],
        ];

        DB::transaction(function () use ($categories, $articles): void {
            $categoryModels = [];
            foreach ($categories as $order => [$name, $slug, $description, $icon, $audiences]) {
                $categoryModels[$slug] = HelpCategory::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'description' => $description,
                        'icon_key' => $icon,
                        'audiences' => $audiences,
                        'sort_order' => $order,
                        'is_active' => true,
                    ],
                );
            }

            foreach ($articles as $order => [$categorySlug, $title, $audiences, $headings]) {
                $article = HelpArticle::query()->updateOrCreate(
                    ['slug' => str($title)->slug()->toString()],
                    [
                        'help_category_id' => $categoryModels[$categorySlug]->id,
                        'created_by' => null,
                        'updated_by' => null,
                        'title' => $title,
                        'summary' => 'Follow these steps to '.$this->summaryFor($title).'.',
                        'keywords' => array_values(array_filter([$categorySlug, 'help', 'guide'])),
                        'audiences' => $audiences,
                        'status' => HelpArticleStatus::Published,
                        'sort_order' => $order,
                        'published_at' => now(),
                    ],
                );

                $article->sections()->delete();
                foreach ($headings as $sectionOrder => $heading) {
                    $article->sections()->create([
                        'heading' => $heading,
                        'body' => $this->bodyFor($title, $heading),
                        'sort_order' => $sectionOrder,
                    ]);
                }
            }
        });
    }

    private function summaryFor(string $title): string
    {
        return match ($title) {
            'Navigate Conscious Connections' => 'find the right place to begin',
            'Update Your Profile' => 'keep your account information current',
            'Find and Start a Learning Module' => 'begin a module',
            'Take a Quiz and View Your Results' => 'complete a quiz and understand your results',
            'Find and Join a Seminar' => 'join a seminar',
            'Use Community Hub Safely' => 'participate in the moderated Community Hub',
            'Review a Dependent Enrollment Request' => 'review a dependent enrollment request',
            'Submit a Module for Review' => 'send a module to review',
            'Open Your Connector Workspace' => 'open your connector workspace',
            'Review Subscription and Payment Status' => 'check subscriptions and payments',
            'Report Unsafe or Inappropriate Content' => 'report a concern through the correct action',
            'Use Language and Accessibility Controls' => 'find the available accessibility controls',
            default => 'resolve common platform problems',
        };
    }

    private function bodyFor(string $title, string $heading): string
    {
        return match ([$title, $heading]) {
            ['Navigate Conscious Connections', 'Sign in'] => 'Select Sign in from the landing page and use the account details you already registered. If authentication fails, use the available recovery action rather than creating a second account.',
            ['Navigate Conscious Connections', 'Open your dashboard'] => 'After sign-in, the dashboard is your starting point for learning, seminars, Community Hub, and account tasks. Choose the card or navigation item that matches your next step.',
            ['Navigate Conscious Connections', 'Use the main navigation'] => 'Use the labeled navigation links in the shell for your role. The Help Center stays available from the support links when you need a guide without leaving your current task.',
            ['Update Your Profile', 'Open profile settings'] => 'Open your profile menu and choose Settings or Profile. Review the fields shown for your account before making changes.',
            ['Update Your Profile', 'Review your information'] => 'Check your name, contact details, language, and notification choices. Keep information current so platform messages reach the right place.',
            ['Update Your Profile', 'Save changes'] => 'Select Save after reviewing your edits. If a field shows an error, correct that field and submit again; your other values remain in the form.',
            ['Find and Start a Learning Module', 'Browse modules'] => 'Open Learning from the dashboard and browse the modules available to your account. Use the module title and summary to choose a suitable starting point.',
            ['Find and Start a Learning Module', 'Review module details'] => 'Read the module overview, expected activities, and enrollment status before starting. A module may require an approved enrollment before its lessons open.',
            ['Find and Start a Learning Module', 'Enroll and begin'] => 'Choose Enroll when the module is available, then open its first lesson. Return to the learning dashboard to continue where you left off.',
            ['Take a Quiz and View Your Results', 'Open the lesson quiz'] => 'Open the quiz from the lesson activity list and review the instructions before answering. Complete each required item shown on the page.',
            ['Take a Quiz and View Your Results', 'Submit answers'] => 'Review your answers, then select Submit once. Wait for the confirmation before navigating away so the attempt can be recorded.',
            ['Take a Quiz and View Your Results', 'Review results and certificates'] => 'Use the results screen to see your score and any available feedback. Certificates appear in the areas provided by the learning module after its requirements are met.',
            ['Find and Join a Seminar', 'Browse seminars'] => 'Open Seminars to see upcoming events and filter the list by the information provided on each card. Select an event to read its details.',
            ['Find and Join a Seminar', 'Review event details'] => 'Check the schedule, description, and participation requirements on the seminar detail page. Confirm that the event fits your availability before registering.',
            ['Find and Join a Seminar', 'Register or attend'] => 'Use the registration action when it is available. For an event you already joined, return to its detail page for the latest attendance instructions.',
            ['Use Community Hub Safely', 'Confirm access'] => 'Open Community Hub from your role shell. Access is limited to the connector-managed spaces available to your account.',
            ['Use Community Hub Safely', 'Read and participate'] => 'Read the space guidelines before posting. Keep replies respectful, avoid sharing private information, and use one upvote when a post is useful.',
            ['Use Community Hub Safely', 'Report unsafe content'] => 'Use the report action on the post or reply and describe the concern clearly. Help & Support tickets are for product assistance, not urgent safety reports.',
            default => "Open the {$heading} area from the platform navigation and follow the instructions shown for your account. If the option is unavailable, use the related guide or submit a private Help & Support ticket with a clear description.",
        };
    }
}
