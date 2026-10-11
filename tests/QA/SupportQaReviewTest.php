<?php

namespace Tests\QA;

use App\Enums\HelpArticleStatus;
use App\Enums\PlatformFeedbackStatus;
use App\Enums\TestimonialStatus;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\PlatformFeedback;
use App\Models\Testimonial;
use App\Models\User;
use App\Notifications\PlatformFeedbackUpdatedNotification;
use App\Services\Support\PlatformFeedbackInsights;
use App\Services\Support\PlatformFeedbackSubmissionService;
use Database\Seeders\HelpCenterSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

/** Review-only tests: failures express unmet approved behavior; no application fixes. */
class SupportQaReviewTest extends TestCase
{
    use ConnectorTestHelpers;

    private function person(string $role = 'learner', int $age = 25, ?string $account = null): User
    {
        $account ??= ($role === 'learner' ? 'learner-adult' : $role);
        $role = $role === 'parent' ? 'learner' : $role;
        $user = User::factory()->create(['role' => $role, 'birthdate' => now()->subYears($age), 'age' => $age,
            'account_type' => $account, 'status' => 'active']);
        $user->assignRole($role);
        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['type' => 'general', 'subject' => 'QA platform feedback', 'description' => 'Synthetic feedback for QA.', 'affected_path' => '/help?q=private#section'], $overrides);
    }

    private function guide(array $attributes = []): HelpArticle
    {
        $article = HelpArticle::factory()->create(array_replace(['title' => 'QA dashboard guide', 'status' => HelpArticleStatus::Published, 'published_at' => now()], $attributes));
        $article->sections()->create(['heading' => 'Open your dashboard', 'body' => 'Use the dashboard link. <script>window.qaXss=1</script>', 'sort_order' => 0]);
        return $article;
    }

    private function consented(User $user, array $overrides = []): PlatformFeedback
    {
        return app(PlatformFeedbackSubmissionService::class)->submit($user, $this->payload(array_replace([
            'testimonial_consent' => true, 'testimonial_display_name' => 'QA Public Name',
        ], $overrides)), null, 'QA/1.0');
    }

    private function capture(string $name, $response): void
    {
        file_put_contents(base_path('docs/qa/2026-09-08-support/evidence/'.$name.'.html'), $response->getContent());
        file_put_contents(base_path('docs/qa/2026-09-08-support/evidence/render-status.jsonl'), json_encode(['page' => $name, 'status' => $response->status()]).PHP_EOL, FILE_APPEND);
        $this->assertContains($response->status(), [200, 500], 'Capture records actual server status; this is an evidence-generation test, not a functional pass.');
    }

    public static function roles(): array
    {
        return ['adult' => ['learner', 25, 'learner-adult'], 'teen' => ['learner', 16, 'learner-teen'],
            'child' => ['learner', 10, 'learner-child'], 'guardian' => ['parent', 35, 'parent'],
            'instructor' => ['instructor', 35, 'instructor'], 'admin' => ['admin', 40, 'admin']];
    }

    #[DataProvider('roles')]
    public function test_qa01_role_help_visibility_and_feedback_submission(string $role, int $age, string $account): void
    {
        $user = $this->person($role, $age, $account);
        $own = $this->guide(['audiences' => [$role]]);
        $restricted = $this->guide(['audiences' => [$role === 'admin' ? 'instructor' : 'admin'], 'title' => 'QA restricted article']);
        $this->actingAs($user)->get(route('help.show', $own->slug))->assertOk();
        $this->get(route('help.show', $restricted->slug))->assertNotFound();
        $this->get(route('feedback.create'))->assertOk();
        $this->post(route('feedback.store'), $this->payload())->assertRedirect();
        $item = PlatformFeedback::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($user->role, $item->user_role);
        $this->assertSame('/help', $item->affected_path);
        $this->assertFalse($item->testimonial_consent);
        $this->get(route('feedback.show', $item))->assertOk()->assertSee($item->reference_number);
    }

    public function test_qa02_guest_feedback_and_admin_routes_require_login(): void
    {
        $feedback = PlatformFeedback::factory()->create();
        foreach (['feedback.create', 'feedback.index', 'admin.feedback.index', 'admin.help.articles.index', 'admin.testimonials.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
        $this->post(route('feedback.store'), $this->payload())->assertRedirect(route('login'));
        $this->get(route('feedback.show', $feedback))->assertRedirect(route('login'));
        $this->get(route('feedback.attachment.show', $feedback))->assertRedirect(route('login'));
        $this->delete(route('feedback.testimonial-consent.destroy', $feedback))->assertRedirect(route('login'));
        $this->assertDatabaseCount('platform_feedback', 1);
    }

    public function test_qa03_feedback_idor_attachment_and_withdrawal_boundaries(): void
    {
        Storage::fake('local');
        $owner = $this->person();
        $feedback = $this->consented($owner);
        Storage::disk('local')->put('qa/image.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aNnsAAAAASUVORK5CYII='));
        $feedback->update(['attachment_path' => 'qa/image.png', 'internal_note' => 'QA PRIVATE ADMIN NOTE']);
        foreach (['learner', 'parent', 'instructor'] as $role) {
            $other = $this->person($role);
            $this->actingAs($other)->get(route('feedback.show', $feedback))->assertForbidden();
            $this->get(route('feedback.attachment.show', $feedback))->assertForbidden();
            $this->delete(route('feedback.testimonial-consent.destroy', $feedback))->assertForbidden();
            $this->get(route('feedback.index'))->assertDontSee($feedback->reference_number);
        }
        foreach ([$owner, $this->person('admin')] as $allowed) {
            $this->assertTrue(app(\App\Policies\PlatformFeedbackPolicy::class)->view($allowed, $feedback));
            $this->actingAs($allowed);
            $this->get(route('feedback.attachment.show', $feedback))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->assertTrue($feedback->fresh()->testimonial_consent);
        $this->get(route('feedback.show', 99999999))->assertNotFound();
        $this->put(route('feedback.show', $feedback), ['subject' => 'changed'])->assertStatus(405);
    }

    public function test_qa04_non_admin_cannot_mutate_support_resources(): void
    {
        $article = $this->guide(); $category = $article->category; $feedback = $this->consented($this->person());
        foreach (['learner', 'parent', 'instructor'] as $role) {
            $this->actingAs($this->person($role));
            $this->post(route('admin.help.articles.publish', $article))->assertForbidden();
            $this->put(route('admin.help.articles.update', $article), [])->assertForbidden();
            $this->post(route('admin.help.categories.deactivate', $category))->assertForbidden();
            $this->put(route('admin.feedback.update', $feedback), ['status' => 'resolved'])->assertForbidden();
            $this->post(route('admin.testimonials.publish', $feedback->testimonial))->assertForbidden();
            $this->post(route('admin.testimonials.withdraw', $feedback->testimonial))->assertForbidden();
        }
        $this->assertSame(TestimonialStatus::Draft, $feedback->testimonial->fresh()->status);
    }

    public function test_qa05_search_variants_no_results_injection_and_escaped_html(): void
    {
        $article = $this->guide(['keywords' => ['navigation'], 'summary' => 'QA locate your account']);
        $this->guide(['title' => 'QA dashboard restricted', 'audiences' => ['admin']]);
        foreach (['dashboard', 'dash', 'DASHBOARD', '  dashboard  ', 'navigation', 'locate', 'Use the dashboard'] as $q) {
            $response = $this->get(route('help.index', ['q' => $q]))->assertOk();
            $this->assertContains($article->id, $response->viewData('articles')->pluck('id')->all());
            $response->assertDontSee('QA dashboard restricted');
        }
        foreach (['no-match-qa', "' OR 1=1 --", '%', '_'] as $q) {
            $this->get(route('help.index', ['q' => $q]))->assertOk()->assertViewHas('articles', fn ($a) => $a->total() === 0);
        }
        $this->get(route('help.show', $article->slug))->assertSee('<script>window.qaXss=1</script>')->assertDontSee('<script>window.qaXss=1</script>', false);
        $this->get(route('help.index', ['q' => '<script>qaXss()</script>']))->assertDontSee('<script>qaXss()</script>', false);
        $this->get('/help/not-a-real-guide')->assertNotFound();
    }

    public function test_qa06_category_audience_is_filtered(): void
    {
        HelpCategory::factory()->create(['name' => 'QA ADMIN ONLY CATEGORY', 'audiences' => ['admin']]);
        $this->get(route('help.index'))->assertOk()->assertDontSee('QA ADMIN ONLY CATEGORY');
    }

    public function test_qa07_seeded_category_link_finds_its_guide(): void
    {
        $this->seed(HelpCenterSeeder::class);
        $response = $this->get(route('help.index', ['q' => 'Getting Started']))->assertOk();
        $this->assertGreaterThan(0, $response->viewData('articles')->total(), 'Clicking the seeded Getting Started category returns zero guides.');
    }

    public function test_qa08_article_exposes_voting_and_feedback_actions(): void
    {
        $article = $this->guide();
        $response = $this->actingAs($this->person())->get(route('help.show', $article->slug))->assertOk();
        $response->assertSee(route('help.helpfulness.update', $article), false);
        $response->assertSee(route('feedback.create'), false);
    }

    public function test_qa09_votes_update_one_row_and_invalid_votes_do_not_write(): void
    {
        $article = $this->guide(); $this->actingAs($this->person());
        foreach ([true, true, false] as $vote) $this->put(route('help.helpfulness.update', $article), ['is_helpful' => $vote])->assertRedirect();
        $this->assertDatabaseCount('help_article_votes', 1);
        $this->assertFalse($article->votes()->first()->is_helpful);
        $this->put(route('help.helpfulness.update', $article), ['is_helpful' => 'invalid'])->assertSessionHasErrors('is_helpful');
        $this->put(route('help.helpfulness.update', 99999999), ['is_helpful' => true])->assertNotFound();
        $this->assertDatabaseCount('help_article_votes', 1);
    }

    private function articlePayload(HelpArticle $article, array $extra = []): array
    {
        return array_replace(['help_category_id' => $article->help_category_id, 'title' => $article->title, 'slug' => $article->slug,
            'summary' => $article->summary, 'keywords' => ['qa'], 'audiences' => ['all'], 'status' => 'published', 'sort_order' => 0,
            'sections' => [['heading' => 'Updated', 'body' => 'Updated plain text']]], $extra);
    }

    public function test_qa10_edit_preserves_existing_screenshot(): void
    {
        Storage::fake('public'); $article = $this->guide();
        $path = 'help/articles/'.$article->id.'/existing.png'; Storage::disk('public')->put($path, 'qa');
        $article->sections()->first()->update(['image_path' => $path, 'image_alt_text' => 'QA screenshot']);
        $this->actingAs($this->person('admin'))->put(route('admin.help.articles.update', $article), $this->articlePayload($article, [
            'sections' => [['body' => 'Updated text', 'existing_image_path' => $path, 'image_alt_text' => 'QA screenshot']],
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($path, $article->fresh()->sections()->first()->image_path);
    }

    public function test_qa11_replacement_removes_old_public_file(): void
    {
        Storage::fake('public'); $article = $this->guide();
        $path = 'help/articles/'.$article->id.'/old.png'; Storage::disk('public')->put($path, 'qa');
        $article->sections()->first()->update(['image_path' => $path]);
        $this->actingAs($this->person('admin'))->put(route('admin.help.articles.update', $article), $this->articlePayload($article, [
            'sections' => [['body' => 'Text', 'image' => UploadedFile::fake()->image('new.png'), 'image_alt_text' => 'New screenshot']],
        ]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_qa12_edit_preserves_original_author(): void
    {
        $article = $this->guide(); $creator = $article->created_by;
        $this->actingAs($this->person('admin'))->put(route('admin.help.articles.update', $article), $this->articlePayload($article))->assertSessionHasNoErrors();
        $this->assertSame($creator, $article->fresh()->created_by);
    }

    public function test_qa13_unchecked_category_checkbox_can_deactivate(): void
    {
        $category = HelpCategory::factory()->create();
        $this->actingAs($this->person('admin'))->put(route('admin.help.categories.update', $category), [
            'name' => $category->name, 'slug' => $category->slug, 'audiences' => ['all'], 'sort_order' => 0,
        ])->assertSessionHasNoErrors();
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_qa14_help_validation_and_preview_lifecycle(): void
    {
        Storage::fake('public'); $article = $this->guide(['status' => HelpArticleStatus::Draft, 'published_at' => null]);
        $this->actingAs($this->person('admin'));
        $this->get(route('admin.help.articles.preview', $article))->assertOk();
        $this->assertSame(HelpArticleStatus::Draft, $article->fresh()->status);
        $this->put(route('admin.help.articles.update', $article), $this->articlePayload($article, ['sections' => [['body' => 'x', 'image' => UploadedFile::fake()->image('qa.png')]]]))->assertSessionHasErrors('sections.0.image_alt_text');
        $this->put(route('admin.help.articles.update', $article), $this->articlePayload($article, ['audiences' => ['all', 'admin']]))->assertSessionHasErrors('audiences.0');
        $this->assertSame(HelpArticleStatus::Draft, $article->fresh()->status);
        $this->post(route('admin.help.articles.publish', $article))->assertRedirect();
        $this->get(route('help.show', $article->slug))->assertOk();
        $this->post(route('admin.help.articles.archive', $article))->assertRedirect();
        $this->get(route('help.show', $article->slug))->assertNotFound();
    }

    public function test_qa15_admin_article_filters_are_honored(): void
    {
        $draft = $this->guide(['status' => 'draft']); $this->guide();
        $response = $this->actingAs($this->person('admin'))->get(route('admin.help.articles.index', ['status' => 'draft']))->assertOk();
        $this->assertSame([$draft->id], $response->viewData('articles')->pluck('id')->all());
    }

    public static function files(): array { return ['png' => ['png'], 'jpeg' => ['jpeg'], 'webp' => ['webp'], 'text' => ['text'], 'svg' => ['svg'], 'oversize' => ['oversize'], 'renamed-php' => ['renamed-php']]; }

    #[DataProvider('files')]
    public function test_qa16_file_validation_and_private_storage(string $kind): void
    {
        Storage::fake('local'); Storage::fake('public'); $this->actingAs($this->person());
        $valid = in_array($kind, ['png', 'jpeg', 'webp'], true);
        $file = match ($kind) {
            'text' => UploadedFile::fake()->createWithContent('notes.txt', 'ordinary text'),
            'svg' => UploadedFile::fake()->createWithContent('script.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'oversize' => UploadedFile::fake()->image('large.png')->size(5121),
            'renamed-php' => UploadedFile::fake()->createWithContent('photo.png', '<?php echo "qa"; ?>'),
            default => UploadedFile::fake()->image('image.'.$kind),
        };
        if (in_array($kind, ['text', 'svg', 'renamed-php'], true)) {
            $sourceFile = $file; // Retain the temporary stream while the real upload is validated.
            $file = new UploadedFile($file->getPathname(), $file->getClientOriginalName(), null, null, true);
        }
        $response = $this->post(route('feedback.store'), $this->payload(['attachment' => $file]));
        if ($valid) {
            $response->assertSessionHasNoErrors()->assertRedirect(); $feedback = PlatformFeedback::firstOrFail();
            Storage::disk('local')->assertExists($feedback->attachment_path); Storage::disk('public')->assertMissing($feedback->attachment_path);
            $this->get(route('feedback.attachment.show', $feedback))->assertOk();
        } else {
            $response->assertSessionHasErrors('attachment'); $this->assertDatabaseCount('platform_feedback', 0);
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_qa17_valid_image_with_disallowed_extension_is_rejected(): void
    {
        Storage::fake('local');
        $png = UploadedFile::fake()->image('good.png');
        $renamed = UploadedFile::fake()->createWithContent('payload.php', file_get_contents($png->getPathname()));
        $sourceFile = $renamed;
        $renamed = new UploadedFile($renamed->getPathname(), 'payload.php', null, null, true);
        $this->actingAs($this->person())->post(route('feedback.store'), $this->payload(['attachment' => $renamed]))->assertSessionHasErrors('attachment');
        $this->assertDatabaseCount('platform_feedback', 0);
    }

    public function test_qa18_storage_failure_does_not_create_feedback(): void
    {
        $file = \Mockery::mock(UploadedFile::class);
        $file->shouldReceive('store')->once()->andReturn(false);
        try { app(PlatformFeedbackSubmissionService::class)->submit($this->person(), $this->payload(), $file, 'QA'); } catch (\Throwable $e) { /* failure may be surfaced */ }
        $this->assertDatabaseCount('platform_feedback', 0);
    }

    public function test_qa19_database_failure_cleans_private_attachment(): void
    {
        Storage::fake('local'); $user = $this->person(); $user->forceDelete();
        $thrown = false;
        try { app(PlatformFeedbackSubmissionService::class)->submit($user, $this->payload(), UploadedFile::fake()->image('qa.png'), 'QA'); } catch (\Throwable $e) { $thrown = true; }
        $this->assertTrue($thrown); $this->assertDatabaseCount('platform_feedback', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_qa20_feedback_validation_mass_assignment_and_paths(): void
    {
        $user = $this->person(); $this->actingAs($user);
        foreach ([['type' => 'fake'], ['subject' => ''], ['description' => ''], ['rating' => 0], ['rating' => 6], ['affected_path' => 'https://outside.invalid/a']] as $bad) {
            $this->post(route('feedback.store'), $this->payload($bad))->assertSessionHasErrors(array_key_first($bad));
        }
        $this->assertDatabaseCount('platform_feedback', 0);
        $this->post(route('feedback.store'), $this->payload(['user_id' => 999, 'user_role' => 'admin', 'status' => 'resolved', 'attachment_path' => '../../.env', 'internal_note' => 'injected']))->assertSessionHasNoErrors();
        $feedback = PlatformFeedback::firstOrFail();
        $this->assertSame($user->id, $feedback->user_id); $this->assertSame('learner', $feedback->user_role);
        $this->assertSame(PlatformFeedbackStatus::New, $feedback->status); $this->assertNull($feedback->attachment_path); $this->assertNull($feedback->internal_note);
    }

    public function test_qa21_submission_routes_have_rate_limits(): void
    {
        foreach (['feedback.store', 'connector.feedback.store', 'help.helpfulness.update'] as $name) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertTrue(collect($middleware)->contains(fn ($item) => str_contains($item, 'throttle')), "$name has no throttle middleware: ".implode(', ', $middleware));
        }
    }

    #[DataProvider('roles')]
    public function test_qa22_consent_form_and_crafted_request_age_rules(string $role, int $age, string $account): void
    {
        $user = $this->person($role, $age, $account); $this->actingAs($user);
        $response = $this->get(route('feedback.create'))->assertOk();
        if ($age < 18) $response->assertDontSee('name="testimonial_consent"', false); else $response->assertSee('name="testimonial_consent"', false);
        $this->post(route('feedback.store'), $this->payload(['testimonial_consent' => '1', 'testimonial_display_name' => 'QA', 'testimonial_show_role' => '1', 'testimonial_show_profile_image' => '1']))->assertSessionHasNoErrors();
        $feedback = PlatformFeedback::firstOrFail();
        $this->assertSame($age >= 18, $feedback->testimonial_consent);
        $this->assertDatabaseCount('testimonials', $age >= 18 ? 1 : 0);
    }

    public function test_qa23_unknown_age_and_missing_display_name_fail_closed(): void
    {
        $user = $this->person(); $user->update(['birthdate' => null, 'age' => null]);
        $this->assertFalse($this->consented($user)->testimonial_consent);
        $this->actingAs($this->person())->post(route('feedback.store'), $this->payload(['testimonial_consent' => '1']))->assertSessionHasErrors('testimonial_display_name');
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_qa24_publication_rechecks_adulthood(): void
    {
        $user = $this->person(); $feedback = $this->consented($user);
        $user->update(['birthdate' => now()->subYears(16), 'age' => 16, 'account_type' => 'learner-teen']);
        $this->actingAs($this->person('admin'))->post(route('admin.testimonials.publish', $feedback->testimonial));
        $this->assertNotSame(TestimonialStatus::Published, $feedback->testimonial->fresh()->status, 'Ineligible minor testimonial was published.');
    }

    public function test_qa25_publication_rechecks_withdrawn_consent(): void
    {
        $user = $this->person(); $feedback = $this->consented($user); $item = $feedback->testimonial;
        $this->actingAs($user)->delete(route('feedback.testimonial-consent.destroy', $feedback))->assertRedirect();
        $this->actingAs($this->person('admin'))->post(route('admin.testimonials.publish', $item));
        $this->assertSame(TestimonialStatus::Withdrawn, $item->fresh()->status);
    }

    public static function lostEligibility(): array { return ['minor' => ['minor'], 'soft-deleted' => ['deleted'], 'disabled' => ['inactive']]; }

    #[DataProvider('lostEligibility')]
    public function test_qa26_public_page_excludes_lost_eligibility(string $state): void
    {
        $user = $this->person(); $feedback = $this->consented($user); $item = $feedback->testimonial;
        $this->actingAs($this->person('admin'))->post(route('admin.testimonials.publish', $item))->assertRedirect();
        if ($state === 'minor') $user->update(['birthdate' => now()->subYears(12), 'account_type' => 'learner-child', 'age' => 12]);
        elseif ($state === 'deleted') $user->delete(); else $user->update(['status' => 'inactive']);
        Auth::forgetGuards(); $this->get(route('home'))->assertOk()->assertDontSee('QA Public Name');
    }

    public function test_qa27_publish_withdraw_and_public_privacy(): void
    {
        $user = $this->person(); $feedback = $this->consented($user, ['description' => '<script>window.qaXss=2</script> useful platform']);
        $feedback->update(['internal_note' => 'QA-SECRET-NOTE', 'attachment_path' => 'qa-secret-file.png']);
        $item = $feedback->testimonial; $admin = $this->person('admin');
        $this->get(route('home'))->assertOk()->assertDontSee('QA Public Name');
        $this->actingAs($admin)->post(route('admin.testimonials.publish', $item))->assertRedirect();
        Auth::forgetGuards();
        $this->get(route('home'))->assertOk()->assertSee('QA Public Name')->assertDontSee($user->email)->assertDontSee('QA-SECRET-NOTE')->assertDontSee('qa-secret-file.png')->assertDontSee('<script>window.qaXss=2</script>', false);
        $this->actingAs($admin)->post(route('admin.testimonials.withdraw', $item))->assertRedirect();
        Auth::forgetGuards(); $this->get(route('home'))->assertDontSee('QA Public Name');
        $this->actingAs($admin)->post(route('admin.testimonials.publish', $item));
        $this->actingAs($user)->delete(route('feedback.testimonial-consent.destroy', $feedback))->assertRedirect();
        $this->assertFalse($feedback->fresh()->testimonial_consent); $this->assertNotNull($feedback->fresh()->testimonial_consent_withdrawn_at);
        $this->assertSame(TestimonialStatus::Withdrawn, $item->fresh()->status); $this->assertNull($item->fresh()->published_at);
        Auth::forgetGuards(); $this->get(route('home'))->assertDontSee('QA Public Name');
    }

    public function test_qa28_connector_get_membership_and_detail_binding(): void
    {
        $this->seedCaviteAddress(); $owner = $this->person(); $other = $this->person(); $connector = $this->createVerifiedConnector($owner);
        $article = $this->guide(); $feedback = $this->consented($owner);
        $this->actingAs($other)->get(route('connector.help.index', $connector))->assertForbidden();
        $this->get(route('connector.feedback.index', $connector))->assertForbidden();
        $this->actingAs($owner)->get(route('connector.help.index', $connector))->assertOk();
        $this->get(route('connector.feedback.index', $connector))->assertOk();
        $this->get(route('connector.help.show', ['connector' => $connector, 'helpArticle' => $article->slug]))->assertOk();
        $this->get(route('connector.feedback.show', ['connector' => $connector, 'platformFeedback' => $feedback]))->assertOk();
    }

    public function test_qa29_connector_post_requires_membership(): void
    {
        $this->seedCaviteAddress(); $connector = $this->createVerifiedConnector($this->person());
        $this->actingAs($this->person())->post(route('connector.feedback.store', $connector), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('platform_feedback', 0);
    }

    public function test_qa30_connector_navigation_preserves_context(): void
    {
        $this->seedCaviteAddress(); $owner = $this->person(); $connector = $this->createVerifiedConnector($owner); $this->guide();
        $this->actingAs($owner)->get(route('connector.feedback.create', $connector))->assertOk()->assertSee('action="'.route('connector.feedback.store', $connector).'"', false);
        $this->get(route('connector.help.index', $connector))->assertSee('action="'.route('connector.help.index', $connector).'"', false);
    }

    public function test_qa31_connector_guide_audience_uses_workspace_context(): void
    {
        $this->seedCaviteAddress(); $owner = $this->person(); $connector = $this->createVerifiedConnector($owner);
        $article = $this->guide(['audiences' => ['connector']]);
        $response = $this->actingAs($owner)->get(route('connector.help.index', $connector))->assertOk();
        $this->assertContains($article->id, $response->viewData('articles')->pluck('id')->all());
    }

    public function test_qa32_admin_filters_statistics_and_notifications(): void
    {
        Notification::fake(); $owner = $this->person(); $admin = $this->person('admin');
        $one = PlatformFeedback::factory()->for($owner)->create(['subject' => 'QA bug', 'type' => 'bug_report', 'status' => 'new', 'rating' => 5, 'created_at' => '2026-08-03 10:00:00']);
        PlatformFeedback::factory()->create(['type' => 'general', 'status' => 'resolved', 'rating' => 3]);
        PlatformFeedback::factory()->create(['status' => 'archived', 'rating' => null]);
        $response = $this->actingAs($admin)->get(route('admin.feedback.index', ['type' => 'bug_report', 'status' => 'new', 'rating' => 5, 'from' => '2026-08-01', 'to' => '2026-08-31', 'search' => 'QA bug']))->assertOk();
        $this->assertSame([$one->id], $response->viewData('feedback')->pluck('id')->all());
        $stats = app(PlatformFeedbackInsights::class)->summary();
        $this->assertSame(3, $stats['total']); $this->assertSame(1, $stats['unresolved']); $this->assertSame(4.0, $stats['average_rating']);
        $this->assertSame(1, $stats['monthly']['2026-08']['total']);
        $this->put(route('admin.feedback.update', $one), ['status' => 'resolved', 'staff_response' => 'QA response', 'internal_note' => 'QA private', 'subject' => 'hacked'])->assertSessionHasNoErrors();
        $this->assertSame('QA bug', $one->fresh()->subject); $this->assertNotNull($one->fresh()->resolved_at);
        Notification::assertSentToTimes($owner, PlatformFeedbackUpdatedNotification::class, 1);
        Notification::assertNotSentTo($admin, PlatformFeedbackUpdatedNotification::class);
        $this->put(route('admin.feedback.update', $one), ['status' => 'resolved', 'staff_response' => 'QA response', 'internal_note' => 'QA private changed']);
        Notification::assertSentToTimes($owner, PlatformFeedbackUpdatedNotification::class, 1);
        $this->put(route('admin.feedback.update', $one), ['status' => 'fake'])->assertSessionHasErrors('status');
        $this->put(route('admin.feedback.update', $one), ['status' => 'planned'])->assertSessionHasNoErrors();
        $this->assertNull($one->fresh()->resolved_at);
    }

    public function test_qa33_saving_new_feedback_does_not_mark_reviewed(): void
    {
        $feedback = PlatformFeedback::factory()->create();
        $this->actingAs($this->person('admin'))->put(route('admin.feedback.update', $feedback), ['status' => 'new', 'internal_note' => 'private note'])->assertSessionHasNoErrors();
        $this->assertNull($feedback->fresh()->reviewed_at); $this->assertNull($feedback->fresh()->reviewed_by);
    }

    public function test_qa34_admin_attachment_and_filter_controls_are_visible(): void
    {
        $feedback = PlatformFeedback::factory()->create(['attachment_path' => 'qa.png']); $this->actingAs($this->person('admin'));
        $this->get(route('admin.feedback.show', $feedback))->assertOk()->assertSee(route('feedback.attachment.show', $feedback), false);
        $this->get(route('admin.feedback.index'))->assertSee('name="search"', false);
    }

    public function test_qa35_seeder_does_not_create_known_password_privileged_identity(): void
    {
        $this->seed(HelpCenterSeeder::class);
        $editor = User::where('email', 'help-center@consciousconnections.local')->firstOrFail();
        $danger = Hash::check('password', $editor->password) && $editor->role === 'admin' && $editor->status === 'active';
        $this->assertFalse($danger, 'Seeder creates active role=admin identity with known password; policy checks the role column.');
    }

    public function test_qa36_seeded_identity_must_not_read_private_attachment_after_login(): void
    {
        Storage::fake('local'); Storage::disk('local')->put('qa-private.png', 'QA PRIVATE CONTENT');
        $this->seed(HelpCenterSeeder::class); $private = PlatformFeedback::factory()->create(['description' => 'QA PRIVATE CONTENT', 'attachment_path' => 'qa-private.png']);
        $this->post(route('login'), ['email' => 'help-center@consciousconnections.local', 'password' => 'password']);
        $response = $this->get(route('feedback.attachment.show', $private));
        $this->assertContains($response->getStatusCode(), [302, 403], 'Known seeder credentials log in and read another user attachment via role-column policy; status='.$response->getStatusCode());
    }

    public function test_qa37_capture_rendered_pages_for_browser_review(): void
    {
        // Synthetic HTML artifacts for browser layout/keyboard checks; no live DB fixtures persist.
        $this->withVite();
        \Illuminate\Support\Facades\Vite::useHotFile(storage_path('framework/qa-unused-hot-file'));
        $article = $this->guide(['title' => 'QA Help Center navigation guide']);
        $this->capture('guest-help', $this->get(route('help.index')));
        $this->capture('guest-guide', $this->get(route('help.show', $article->slug)));
        $adult = $this->person(); $feedback = $this->consented($adult);
        $this->actingAs($adult); $this->capture('learner-form', $this->get(route('feedback.create')));
        $this->capture('learner-history', $this->get(route('feedback.index')));
        $this->capture('learner-detail', $this->get(route('feedback.show', $feedback)));
        $this->actingAs($this->person('learner', 16, 'learner-teen')); $this->capture('teen-form', $this->get(route('feedback.create')));
        $this->actingAs($this->person('parent')); $this->capture('guardian-form', $this->get(route('feedback.create')));
        $this->actingAs($this->person('instructor')); $this->capture('instructor-help', $this->get(route('help.index')));
        $this->seedCaviteAddress(); $connector = $this->createVerifiedConnector($adult); $this->actingAs($adult);
        $this->capture('connector-form', $this->get(route('connector.feedback.create', $connector)));
        $this->actingAs($this->person('admin'));
        $this->capture('admin-articles', $this->get(route('admin.help.articles.index')));
        $this->capture('admin-article-form', $this->get(route('admin.help.articles.edit', $article)));
        $this->capture('admin-category-form', $this->get(route('admin.help.categories.edit', $article->category)));
        $this->capture('admin-inbox', $this->get(route('admin.feedback.index')));
        $this->capture('admin-feedback', $this->get(route('admin.feedback.show', $feedback)));
        $this->capture('admin-testimonials', $this->get(route('admin.testimonials.index')));
        $this->post(route('admin.testimonials.publish', $feedback->testimonial));
        Auth::forgetGuards(); $this->capture('landing-testimonials', $this->get(route('home')));
    }

    public function test_qa38_unique_constraints_and_cascade(): void
    {
        $feedback = $this->consented($this->person()); $testimonialId = $feedback->testimonial->id;
        $thrown = false;
        try { $feedback->testimonial()->create(['user_id' => $feedback->user_id, 'display_name' => 'Duplicate', 'quotation' => 'duplicate']); } catch (\Illuminate\Database\QueryException $e) { $thrown = true; }
        $this->assertTrue($thrown); $feedback->delete();
        $this->assertDatabaseMissing('testimonials', ['id' => $testimonialId]);
    }

    public function test_qa39_connector_feedback_detail_accepts_correct_parameters(): void
    {
        $this->seedCaviteAddress(); $owner = $this->person(); $connector = $this->createVerifiedConnector($owner); $feedback = $this->consented($owner);
        $this->actingAs($owner)->get(route('connector.feedback.show', ['connector' => $connector, 'platformFeedback' => $feedback]))->assertOk();
    }

    public function test_qa40_parent_account_receives_parent_guides(): void
    {
        $guardian = $this->person('parent', 35); $article = $this->guide(['audiences' => ['parent']]);
        $response = $this->actingAs($guardian)->get(route('help.index'))->assertOk();
        $this->assertContains($article->id, $response->viewData('articles')->pluck('id')->all(), 'Real guardians have role=learner, account_type=parent.');
    }

    public function test_qa41_plaintext_keyword_lines_are_searchable_individually(): void
    {
        $article = $this->guide();
        $this->actingAs($this->person('admin'))->put(route('admin.help.articles.update', $article), $this->articlePayload($article, ['keywords' => ["kryptonium\nzirconium"]]))->assertSessionHasNoErrors();
        Auth::forgetGuards(); $response = $this->get(route('help.index', ['q' => 'zirconium']))->assertOk();
        $this->assertContains($article->id, $response->viewData('articles')->pluck('id')->all());
    }

    public function test_qa42_csrf_is_enforced_with_test_bypass_disabled(): void
    {
        $user = $this->person(); $this->actingAs($user); $previous = $this->app['env'];
        try {
            $this->app['env'] = 'production';
            $this->post(route('feedback.store'), $this->payload())->assertStatus(419);
        } finally { $this->app['env'] = $previous; }
        $this->assertDatabaseCount('platform_feedback', 0);
    }

    public function test_qa43_truncated_image_fails_decoded_image_validation(): void
    {
        Storage::fake('local'); $png = UploadedFile::fake()->image('valid.png');
        $fake = UploadedFile::fake()->createWithContent('broken.png', substr(file_get_contents($png->getPathname()), 0, 24));
        $file = new UploadedFile($fake->getPathname(), 'broken.png', null, null, true);
        $this->assertFalse(@imagecreatefromstring(file_get_contents($file->getPathname())));
        $this->actingAs($this->person())->post(route('feedback.store'), $this->payload(['attachment' => $file]))->assertSessionHasErrors('attachment');
    }

    public function test_qa44_real_database_notification_is_private_and_recipient_scoped(): void
    {
        $owner = $this->person(); $admin = $this->person('admin'); $feedback = PlatformFeedback::factory()->for($owner)->create();
        $this->actingAs($admin)->put(route('admin.feedback.update', $feedback), ['status' => 'reviewed', 'internal_note' => 'QA SECRET INTERNAL'])->assertSessionHasNoErrors();
        $notification = $owner->notifications()->firstOrFail();
        $this->assertSame(1, $owner->notifications()->count()); $this->assertSame(0, $admin->notifications()->count());
        $this->assertSame(route('feedback.show', $feedback), $notification->data['url']);
        $this->assertStringNotContainsString('QA SECRET INTERNAL', json_encode($notification->data));
    }

    public function test_qa45_duplicate_feedback_requests_do_not_create_duplicate_records(): void
    {
        $this->actingAs($this->person());
        $this->post(route('feedback.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('feedback.store'), $this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('platform_feedback', 1);
    }

    public function test_qa46_blank_optional_keyword_control_can_save_article(): void
    {
        $article = $this->guide();
        $this->actingAs($this->person('admin'))->put(route('admin.help.articles.update', $article), $this->articlePayload($article, ['keywords' => ['']]))->assertSessionHasNoErrors();
    }

    public function test_qa47_publication_requires_source_review(): void
    {
        $feedback = $this->consented($this->person());
        $this->assertSame(PlatformFeedbackStatus::New, $feedback->status);
        $this->actingAs($this->person('admin'))->post(route('admin.testimonials.publish', $feedback->testimonial));
        $this->assertNotSame(TestimonialStatus::Published, $feedback->testimonial->fresh()->status);
    }
    public function test_qa48_repeat_seed_preserves_counts_and_all_bodies_are_real_guidance(): void
    {
        $this->seed(HelpCenterSeeder::class); $this->seed(HelpCenterSeeder::class);
        $this->assertDatabaseCount('help_categories', 13); $this->assertDatabaseCount('help_articles', 13); $this->assertDatabaseCount('help_article_sections', 39);
        $body = HelpArticle::where('slug', 'navigate-conscious-connections')->firstOrFail()->sections()->first()->body;
        $this->assertStringNotContainsString('Open the Sign in area from the platform navigation, then follow the on-screen instructions.', $body, 'Seeded text must explain actual steps, not repeat the heading template.');
    }
}
