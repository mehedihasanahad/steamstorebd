<?php

/**
 * Turning what an admin typed into something a mail client will render.
 *
 * The editor emits bare HTML with no classes. On the storefront a stylesheet
 * picks that up; in e-mail there is nothing to pick it up, so the rules have to
 * be written onto the elements — and they have to come from the palette, or the
 * campaign body would be the one part of a message that stops matching the site
 * when the theme changes.
 */

use App\Support\Campaigns\MergeTags;
use App\Support\EmailRichText;
use App\Support\EmailTheme;

describe('inlining a campaign body', function () {
    it('styles the elements the editor actually produces', function () {
        $html = EmailRichText::inline(
            '<h2>News</h2><p>Hello</p><ul><li>One</li></ul><blockquote>Quoted</blockquote>',
        );

        foreach (['<h2 style=', '<p style=', '<ul style=', '<li style=', '<blockquote style='] as $tag) {
            expect($html)->toContain($tag);
        }
    });

    it('paints it from the palette and nothing else', function () {
        $html = EmailRichText::inline('<p>Hello <strong>you</strong>, <a href="https://x.test">here</a>.</p>');

        preg_match_all('/#[0-9a-fA-F]{6}\b/', $html, $matches);

        $used    = array_unique(array_map('strtoupper', $matches[0]));
        $allowed = array_map('strtoupper', array_values(EmailTheme::palette()));

        expect($used)->not->toBeEmpty()
            ->and(array_values(array_diff($used, $allowed)))->toBe([]);
    });

    it('keeps what the author set by hand', function () {
        $html = EmailRichText::inline('<p style="text-align:center;">Centred</p>');

        expect($html)->toContain('text-align:center;');
    });

    it('does not mangle Bengali or the currency mark', function () {
        expect(EmailRichText::inline('<p>৳500 ছাড়</p>'))->toContain('৳500 ছাড়');
    });

    it('leaves an empty body empty rather than inventing markup', function () {
        expect(EmailRichText::inline(null))->toBe('')
            ->and(EmailRichText::inline('   '))->toBe('');
    });
});

describe('the plain-text half', function () {
    it('reads as prose, not as HTML with the tags pulled out', function () {
        $text = EmailRichText::toText('<h2>News</h2><p>Hello <strong>you</strong>.</p><ul><li>One</li><li>Two</li></ul>');

        expect($text)->toBe("News\n\nHello you.\n\n- One\n- Two")
            ->and($text)->not->toContain('<');
    });

    it('decodes the entities the editor leaves behind', function () {
        expect(EmailRichText::toText('<p>Ten &amp; twenty &mdash; both</p>'))
            ->toBe('Ten & twenty — both');
    });
});

describe('placeholders', function () {
    it('fills in what it knows', function () {
        $body = MergeTags::apply('<p>Hi {{ first_name }} ({{ email }})</p>', 'Rahim Uddin', 'rahim@example.com');

        expect($body)->toBe('<p>Hi Rahim (rahim@example.com)</p>');
    });

    it('accepts them written with or without spaces', function () {
        expect(MergeTags::apply('{{name}} and {{ name }}', 'Rahim', 'r@example.com'))
            ->toBe('Rahim and Rahim');
    });

    it('falls back rather than leaving a greeting hanging', function () {
        expect(MergeTags::apply('<p>Hi {{ first_name }},</p>', null, 'r@example.com'))
            ->toBe('<p>Hi there,</p>');
    });

    it('escapes a name so it cannot carry markup into the message', function () {
        $body = MergeTags::apply('<p>Hi {{ name }}</p>', '<script>alert(1)</script>', 'r@example.com');

        expect($body)->not->toContain('<script>')
            ->and($body)->toContain('&lt;script&gt;');
    });
});
