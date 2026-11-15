<?php

namespace Tests\Unit;

use App\Support\SafeUserHtml;
use PHPUnit\Framework\TestCase;

class SafeUserHtmlTest extends TestCase
{
    public function test_user_markup_keeps_formatting_but_never_attributes_or_active_elements(): void
    {
        $safe = SafeUserHtml::clean('<p onclick="alert(1)">Hello <strong>world</strong><a href="javascript:alert(1)">link</a><script>alert(1)</script><svg onload="alert(1)"></svg></p>');
        $this->assertStringContainsString('<strong>world</strong>', $safe);
        foreach (['onclick', 'javascript:', '<script', '<svg', 'alert(1)'] as $bad) {
            $this->assertStringNotContainsString($bad, $safe);
        }
        $this->assertSame($safe, SafeUserHtml::clean($safe));
    }

    public function test_legacy_model_values_are_sanitized_when_read(): void
    {
        foreach ([[\App\Models\ModuleFeedback::class, 'review_html'], [\App\Models\ModuleFeedback::class, 'instructor_reply_html'], [\App\Models\InstructorFeedback::class, 'review_html'], [\App\Models\ContentReport::class, 'details_html']] as [$class,$field]) {
            $model = new $class;
            $model->setRawAttributes([$field => '<p onmouseover="alert(1)">saved</p>']);
            $this->assertSame('<p>saved</p>', $model->{$field});
        }
    }

    public function test_encoded_text_and_malformed_markup_stay_inert(): void
    {
        foreach ([
            '<IMG SRC=x onerror=alert(1)><p style="background:url(javascript:alert(1))">Text',
            '<math><mtext><table><mglyph><style><!--</style><img title="--><img src=x onerror=alert(1)>">',
            '<textarea></textarea><iframe srcdoc="<script>alert(1)</script>"></iframe>',
            '&lt;img src=x onerror=alert(1)&gt;',
            '<p><a href="&#106;avascript:alert(1)" target="_blank">Text</a></p>',
        ] as $payload) {
            $safe = SafeUserHtml::clean($payload);
            $fragment = (new \Masterminds\HTML5)->loadHTMLFragment($safe);
            $walk = function ($node) use (&$walk): void {
                if ($node instanceof \DOMElement) {
                    $this->assertSame(0, $node->attributes->length);
                    $this->assertContains($node->localName, ['p', 'a', 'b', 'strong', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'br']);
                }
                foreach ($node->childNodes as $child) {
                    $walk($child);
                }
            };
            $walk($fragment);
            $this->assertSame($safe, SafeUserHtml::clean($safe));
        }
    }

    public function test_new_model_writes_and_community_report_display_are_safe(): void
    {
        $model = new \App\Models\ModuleFeedback;
        $model->review_html = '<p onclick="alert(1)">New review</p>';
        $this->assertSame('<p>New review</p>', $model->getAttributes()['review_html']);
        $report = new \App\Models\CommunityReport;
        $report->setRawAttributes(['details' => '<p onmouseover="alert(1)">Saved report</p>']);
        $this->assertSame('<p>Saved report</p>', $report->details_html);
    }
}
