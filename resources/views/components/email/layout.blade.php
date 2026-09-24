@props([
    'title',
    'preheader' => '',
    'eyebrow'   => null,
    'badge'     => null,
])
@php
    /*
     | Every colour on this page is written inline and opaque. A <style> block
     | is the first thing a mail client drops, and a translucent panel over a
     | dropped background takes its own light text with it; between them that
     | is how the previous templates could render as white-on-white. The block
     | below therefore carries nothing a message depends on — only the mobile
     | width and a couple of client resets.
     */
    $c = \App\Support\EmailTheme::palette();

    $font = \App\Support\EmailTheme::FONT;
    $mono = \App\Support\EmailTheme::MONO;

    // The storefront sets the last word of the name in the accent; a one-word
    // name simply keeps all of it in the high ink.
    $brand = trim((string) site_setting('site_name', 'Steam Store BD'));
    $words = preg_split('/\s+/', $brand) ?: [$brand];
    $mark  = count($words) > 1 ? array_pop($words) : null;
    $lead  = count($words) > 0 ? implode(' ', $words) : $brand;
@endphp
<!DOCTYPE html>
<html lang="en" style="margin:0; padding:0;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- Says the design is already dark, so a client does not invert it again. --}}
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>{{ $title }}</title>
    <style>
        /* Nothing here is load-bearing — see the note at the top of this file. */
        body { margin:0 !important; padding:0 !important; width:100% !important; }
        table { border-collapse:collapse; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        /* iOS turns dates and order numbers into its own blue links otherwise. */
        a[x-apple-data-detectors] { color:inherit !important; text-decoration:none !important; font-size:inherit !important; font-family:inherit !important; font-weight:inherit !important; line-height:inherit !important; }
        @media only screen and (max-width: 620px) {
            .e-shell { width:100% !important; }
            .e-pad   { padding-left:20px !important; padding-right:20px !important; }
            .e-gutter{ padding-left:12px !important; padding-right:12px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:{{ $c['canvas'] }}; color:{{ $c['ink-mid'] }}; font-family:{!! $font !!};">

{{-- Inbox preview text: without it the client pulls the first words of the header. --}}
@if($preheader !== '')
<div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:{{ $c['canvas'] }};">{{ $preheader }}{{ str_repeat("\u{200B}\u{00A0}", 60) }}</div>
@endif

{{-- The canvas is painted by a table with a bgcolor attribute rather than by
     `body`, because clients strip body styles far more often than attributes. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="{{ $c['canvas'] }}" style="background-color:{{ $c['canvas'] }}; width:100%;">
<tr>
<td align="center" class="e-gutter" style="padding:32px 16px;">

    <table role="presentation" class="e-shell" width="600" cellpadding="0" cellspacing="0" border="0" align="center" style="width:100%; max-width:600px; text-align:left;">

        {{-- Header --}}
        <tr>
        <td class="e-pad" style="background-color:{{ $c['card'] }}; border:1px solid {{ $c['border'] }}; border-radius:10px; padding:32px 28px; text-align:center;">
            <div style="font-family:{!! $font !!}; font-size:22px; line-height:1.25; letter-spacing:-0.015em; font-weight:800; color:{{ $c['ink'] }};">
                {{ $lead }}@if($mark) <span style="color:{{ $c['accent-hover'] }};">{{ $mark }}</span>@endif
            </div>
            @if($eyebrow)
            <div style="font-family:{!! $font !!}; font-size:13px; line-height:1.5; color:{{ $c['ink-low'] }}; margin-top:6px;">{{ $eyebrow }}</div>
            @endif
            @if($badge)
            <div style="margin-top:14px;">
                <span style="display:inline-block; background-color:{{ $c['info-bg'] }}; border:1px solid {{ $c['info-border'] }}; border-radius:999px; color:{{ $c['info-ink'] }}; font-family:{!! $mono !!}; font-size:13px; font-weight:700; padding:6px 16px;">{{ $badge }}</span>
            </div>
            @endif
        </td>
        </tr>

        <tr><td style="height:16px; line-height:16px; font-size:0;">&nbsp;</td></tr>

        {{ $slot }}

        {{-- Footer --}}
        <tr>
        <td class="e-pad" style="padding:8px 28px 4px; text-align:center; font-family:{!! $font !!}; font-size:12px; line-height:1.8; color:{{ $c['ink-low'] }};">
            @isset($footer)
                {{ $footer }}
            @else
                <p style="margin:0;">Need help? <a href="{{ route('contact') }}" style="color:{{ $c['accent-hover'] }}; text-decoration:none;">Contact our support</a></p>
            @endisset
            <p style="margin:8px 0 0;">{{ $brand }} — <a href="{{ route('home') }}" style="color:{{ $c['accent-hover'] }}; text-decoration:none;">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
            @isset($disclaimer)
            <p style="margin:12px 0 0; color:{{ $c['ink-low'] }};">{{ $disclaimer }}</p>
            @endisset
        </td>
        </tr>

    </table>

</td>
</tr>
</table>
</body>
</html>
