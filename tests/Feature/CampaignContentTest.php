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

use App\Support\Campaigns\BodyInput;
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

describe('getting finished HTML into the body', function () {
    it('takes the source field as the body, exactly as pasted', function () {
        $html = '<p>Hi {{ first_name }},</p><h3>What is discounted</h3><ul><li><strong>Steam</strong> — 10% off</li></ul>';

        $data = BodyInput::apply(['body' => '<p>whatever was in the editor</p>', 'html_source' => $html]);

        expect($data['body'])->toBe($html)
            ->and($data)->not->toHaveKey('html_source');
    });

    it('leaves the editor alone when the source field is empty', function () {
        $data = BodyInput::apply(['body' => '<p>Typed with the toolbar</p>', 'html_source' => '   ']);

        expect($data['body'])->toBe('<p>Typed with the toolbar</p>')
            ->and($data)->not->toHaveKey('html_source');
    });

    it('puts back markup that the editor stored as text', function () {
        // What a WYSIWYG actually saves when HTML source is pasted into it.
        $pasted = '<p>&nbsp;</p><p>&lt;p&gt;Hi Rahim,&lt;/p&gt;<br><br>'
            . '&lt;h3&gt;What is discounted&lt;/h3&gt;</p>';

        $body = BodyInput::repair($pasted);

        expect($body)->toContain('<p>Hi Rahim,</p>')
            ->and($body)->toContain('<h3>What is discounted</h3>')
            ->and($body)->not->toContain('&lt;');
    });

    it('renders as prose once repaired, not as tags', function () {
        $pasted = '<p>&lt;p&gt;Hi Rahim,&lt;/p&gt;&lt;ul&gt;&lt;li&gt;Steam&lt;/li&gt;&lt;/ul&gt;</p>';

        expect(EmailRichText::toText(BodyInput::repair($pasted)))
            ->toBe("Hi Rahim,\n\n- Steam");
    });

    it('never touches a body somebody actually formatted', function () {
        // Real headings and lists mean the editor was used properly. An
        // escaped fragment here is content, and rewriting it would be worse
        // than leaving it.
        $formatted = '<h3>Heading</h3><ul><li>Use &lt;p&gt; to open a paragraph</li></ul>';

        expect(BodyInput::repair($formatted))->toBe($formatted);
    });

    it('leaves plain prose exactly where it is', function () {
        $prose = '<p>Nothing here was pasted from anywhere.</p>';

        expect(BodyInput::repair($prose))->toBe($prose);
    });
});
