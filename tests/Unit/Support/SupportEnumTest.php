<?php

namespace Tests\Unit\Support;

use App\Enums\HelpArticleStatus;
use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Enums\TestimonialStatus;
use PHPUnit\Framework\TestCase;

class SupportEnumTest extends TestCase
{
    public function test_support_enums_expose_stable_values_and_plain_labels(): void
    {
        $this->assertSame(['draft', 'published', 'archived'], HelpArticleStatus::values());
        $this->assertSame(['general', 'bug_report', 'feature_suggestion', 'accessibility_issue', 'help_content_issue', 'account_payment_issue'], PlatformFeedbackType::values());
        $this->assertSame(['new', 'reviewed', 'resolved', 'closed', 'withdrawn'], PlatformFeedbackStatus::values());
        $this->assertSame(['draft', 'published', 'rejected', 'withdrawn'], TestimonialStatus::values());
        $this->assertSame('Report a Problem', PlatformFeedbackType::BugReport->label());
        $this->assertSame('In Review', PlatformFeedbackStatus::Reviewed->label());
    }
}
