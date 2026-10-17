<?php

namespace Tests\Unit\Connectors;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class ConnectorResubmissionFormMarkupTest extends TestCase
{
    public function test_replacement_files_and_metadata_share_the_multipart_submit_form(): void
    {
        $view = file_get_contents(dirname(__DIR__, 3).'/resources/views/connectors/status.blade.php');
        $html = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $html->loadHTML($view);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        $xpath = new DOMXPath($html);
        $forms = $xpath->query('//form[contains(@action, "connector.verification.resubmit")]');
        $this->assertSame(1, $forms->length);
        $form = $forms->item(0);
        $formId = $form->getAttribute('id');
        $this->assertNotEmpty($formId, 'Replacement inputs are outside the form and must have a form owner.');
        $this->assertSame('post', strtolower($form->getAttribute('method')));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertSame(1, $xpath->query('//form[@id="'.$formId.'"]')->length);

        $controls = $xpath->query('//input[starts-with(@name, "evidence[")]');
        $this->assertSame(2, $controls->length, 'Check both the file control and the metadata control template.');
        foreach ($controls as $control) {
            $this->assertSame($formId, $control->getAttribute('form'), 'The browser must include every replacement field in the submit form.');
            $this->assertFalse($control->hasAttribute('disabled'));
        }
    }
}
