<?php

namespace App\Support;

use DOMNode;
use Masterminds\HTML5;

final class SafeUserHtml
{
    public static function clean(?string $html): string
    {
        $fragment = (new HTML5(['disable_html_ns' => true]))->loadHTMLFragment($html ?? '');

        return trim(self::render($fragment));
    }

    private static function render(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        $tag = strtolower($node->nodeName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template'], true)) {
            return '';
        }
        $content = '';
        foreach ($node->childNodes as $child) {
            $content .= self::render($child);
        }
        // Rebuild a small formatting vocabulary; no user attributes or URLs reach the browser.
        if (in_array($tag, ['p', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'blockquote'], true)) {
            return '<'.$tag.'>'.$content.'</'.$tag.'>';
        }
        if ($tag === 'br') {
            return '<br>';
        }

        return $content;
    }
}
