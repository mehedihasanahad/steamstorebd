<?php

namespace App\Support\Campaigns;

/**
 * Getting finished HTML into a campaign body.
 *
 * The body field is a WYSIWYG, and a WYSIWYG treats pasted markup as text:
 * paste <p>Hi</p> into it and it stores &lt;p&gt;Hi&lt;/p&gt; wrapped in a
 * paragraph of its own. Everything downstream then does its job perfectly on
 * content that was already wrong — the message renders, the send reports
 * success, and the recipient reads the tags. Nothing reports a failure because
 * nothing failed; the body was broken before it was ever saved.
 *
 * So there are two ways in. The source field is the honest one: what is pasted
 * there is the body, untouched. The repair below is for the other way, where
 * somebody pasted into the editor anyway. It is deliberately narrow — it acts
 * only when the real markup is the wrapper an editor puts around pasted text,
 * never when someone has actually formatted something, because silently
 * rewriting a body that was fine is the worse failure of the two.
 */
final class BodyInput
{
    /** Tags a WYSIWYG adds by itself when plain text is pasted into it. */
    private const WRAPPERS = ['p', 'br', 'div', 'span'];

    /** Tags worth recognising as markup someone meant to write. */
    private const MARKUP = 'p|br|h[1-6]|ul|ol|li|strong|em|b|i|a|div|blockquote';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function apply(array $data): array
    {
        $source = trim((string) ($data['html_source'] ?? ''));

        unset($data['html_source']);

        if ($source !== '') {
            $data['body'] = $source;

            return $data;
        }

        if (array_key_exists('body', $data)) {
            $data['body'] = self::repair((string) $data['body']);
        }

        return $data;
    }

    /** Markup that was pasted as text, put back as markup. */
    public static function repair(string $body): string
    {
        if (! self::looksEscaped($body) || ! self::onlyWrappers($body)) {
            return $body;
        }

        // strip_tags first: the only real tags here are the wrapper the editor
        // added, and they are not part of what was pasted.
        $decoded = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // The wrapper usually opens with &nbsp;, which decodes to U+00A0 and
        // survives an ordinary trim — leaving the body starting on a stray
        // space that the editor then shows as an empty first line.
        $decoded = (string) preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $decoded);

        // Only when it genuinely recovered markup. A body that decodes to
        // prose was prose, and keeping it is the safer of the two mistakes.
        return preg_match('/<(?:' . self::MARKUP . ')\b[^>]*>/i', $decoded) === 1
            ? $decoded
            : $body;
    }

    /** Markup sitting in the body as visible text rather than as tags. */
    private static function looksEscaped(string $body): bool
    {
        return preg_match('/&lt;\s*\/?\s*(?:' . self::MARKUP . ')\b[^&]*&gt;/i', $body) === 1;
    }

    /** True when nothing in the body was formatted by hand. */
    private static function onlyWrappers(string $body): bool
    {
        preg_match_all('/<\s*\/?\s*([a-z][a-z0-9]*)/i', $body, $matches);

        $tags = array_unique(array_map('strtolower', $matches[1]));

        return array_diff($tags, self::WRAPPERS) === [];
    }
}
