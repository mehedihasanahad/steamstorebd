<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

/**
 * Inlines the styles for admin-authored rich text.
 *
 * The editor emits bare HTML with no classes, which the storefront styles with
 * element selectors in one stylesheet. E-mail has no stylesheet to lean on, so
 * the same rules are written onto the elements themselves here — the same
 * rules, deliberately: a campaign should read like the site it came from.
 *
 * Colours and fonts come from EmailTheme, so this file declares no literal of
 * its own and a palette change reaches campaign bodies with everything else.
 */
final class EmailRichText
{
    public static function inline(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;

        // The prefix is what keeps Bengali and the currency mark intact;
        // loadHTML assumes ISO-8859-1 without it.
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8" ?><div id="rich-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // NOIMPLIED means no html/body is invented, so the wrapper div is the
        // document element. getElementById would need a DTD to find it.
        $root = $document->documentElement;

        if (! $root instanceof DOMElement) {
            return $html;
        }

        self::style($root);

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return trim($out);
    }

    /**
     * The same body as plain text, for the alternative part. Block elements
     * become line breaks and list items keep a marker, so the text half reads
     * as something written rather than as HTML with the tags pulled out.
     */
    public static function toText(?string $html): string
    {
        $html = (string) $html;

        if (trim($html) === '') {
            return '';
        }

        $text = preg_replace('/<li[^>]*>/i', "\n- ", $html);
        $text = preg_replace('/<br\s*\/?>/i', "\n", (string) $text);
        $text = preg_replace('/<\/(p|h[1-6]|div|ul|ol|blockquote|tr)>/i', "\n\n", (string) $text);
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trailing spaces first, so a line that is only whitespace counts as
        // blank when the runs below are collapsed.
        $text = preg_replace('/[ \t]+/', ' ', (string) $text);
        $text = implode("\n", array_map('trim', explode("\n", (string) $text)));
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim((string) $text);
    }

    private static function style(DOMElement $element): void
    {
        foreach ($element->getElementsByTagName('*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $declarations = self::rulesFor(strtolower($node->nodeName));

            if ($declarations === null) {
                continue;
            }

            // Anything the author set by hand wins; this only fills the gap
            // left by the missing stylesheet.
            $existing = trim((string) $node->getAttribute('style'));
            $node->setAttribute('style', $existing === '' ? $declarations : $declarations . ' ' . $existing);
        }
    }

    private static function rulesFor(string $tag): ?string
    {
        $c    = EmailTheme::palette();
        $font = EmailTheme::FONT;

        $body = "font-family:{$font}; font-size:15px; line-height:1.7; color:{$c['ink-mid']};";

        $heading = fn (string $size, string $top) => "font-family:{$font}; font-size:{$size}; line-height:1.3; font-weight:700; color:{$c['ink']}; margin:{$top} 0 8px;";

        return match ($tag) {
            'p'  => "margin:0 0 14px; {$body}",
            'h1' => $heading('22px', '20px'),
            'h2' => $heading('19px', '20px'),
            'h3' => $heading('17px', '18px'),
            'h4', 'h5', 'h6' => $heading('15px', '16px'),

            'ul', 'ol' => "margin:0 0 14px; padding:0 0 0 22px; {$body}",
            'li'       => "margin:0 0 6px; {$body}",

            'a' => "color:{$c['accent-hover']}; text-decoration:underline;",

            'strong', 'b' => "font-weight:700; color:{$c['ink']};",
            'em', 'i'     => 'font-style:italic;',
            'u'           => 'text-decoration:underline;',

            'blockquote' => "margin:0 0 14px; padding:12px 16px; border-left:3px solid {$c['accent']}; "
                . "background-color:{$c['raised']}; border-radius:0 8px 8px 0; {$body}",

            'hr' => "border:0; border-top:1px solid {$c['border']}; margin:20px 0;",

            'img' => 'max-width:100%; height:auto; display:block;',

            default => null,
        };
    }
}
