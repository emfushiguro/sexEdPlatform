<?php

namespace Tests\Feature\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\DatabaseTestCase;

class SupportSchemaTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_support_tables_have_the_required_privacy_and_lifecycle_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('help_categories', ['name', 'slug', 'audiences', 'sort_order', 'is_active']));
        $this->assertTrue(Schema::hasColumns('help_articles', ['help_category_id', 'created_by', 'title', 'slug', 'status', 'published_at']));
        $this->assertTrue(Schema::hasColumns('help_article_sections', ['help_article_id', 'body', 'image_path', 'image_alt_text', 'sort_order']));
        $this->assertTrue(Schema::hasColumns('help_article_votes', ['help_article_id', 'user_id', 'is_helpful']));
        $this->assertTrue(Schema::hasColumns('platform_feedback', ['reference_number', 'user_id', 'type', 'attachment_path', 'status', 'testimonial_consent', 'testimonial_consent_withdrawn_at', 'submission_token']));
        $this->assertTrue(Schema::hasColumns('testimonials', ['platform_feedback_id', 'submission_token', 'user_id', 'display_name', 'quotation', 'consent_given', 'show_role', 'consented_at', 'consent_withdrawn_at', 'status', 'published_at', 'withdrawn_at']));
        $this->assertTrue(Schema::hasColumns('platform_feedback_histories', ['platform_feedback_id', 'actor_id', 'from_status', 'to_status', 'internal_note', 'staff_response']));
    }
}
