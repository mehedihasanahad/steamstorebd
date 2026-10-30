@php
    $c    = \App\Support\EmailTheme::palette();
    $font = \App\Support\EmailTheme::FONT;
    $firstName = trim(explode(' ', trim($user->name))[0] ?? '');
    $greeting  = $firstName !== '' ? $firstName . ', welcome' : 'Welcome';
@endphp

<x-email.layout
    title="Confirm your email address"
    preheader="One tap confirms your address and your account is ready to order."
    eyebrow="Account setup"
>

    <x-email.banner tone="info" heading="Confirm your email">
        {{ $greeting }} to {{ site_setting('site_name', 'Steam Store BD') }}.<br>
        One tap below and your account is ready to order.
    </x-email.banner>

    <x-email.panel align="center">
        <x-email.button :url="$verificationUrl">Confirm my email address</x-email.button>
        <x-email.text size="caption" tone="low" align="center" top="16px">
            This link works once and expires in {{ $expiresInMinutes }} minutes.
        </x-email.text>
    </x-email.panel>

    <x-email.list title="Why we ask" :ordered="false">
        <li>Your gift card codes are delivered to this address</li>
        <li>It is how we reach you if a code ever needs replacing</li>
        <li>It stops anyone else ordering in your name</li>
        <x-slot:note>Until it is confirmed you can browse and build a cart — only checkout waits on this.</x-slot:note>
    </x-email.list>

    <x-email.panel title="Button not working?">
        <x-email.text size="caption" tone="low" bottom="10px">Paste this address into your browser:</x-email.text>
        <div style="font-family:{!! $font !!}; font-size:13px; line-height:1.6; color:{{ $c['ink-mid'] }}; word-break:break-all;">{{ $verificationUrl }}</div>
    </x-email.panel>

    <x-slot:footer>
        <p style="margin:0;">Did not create an account? Ignore this email — nothing happens until the link is opened.</p>
        <p style="margin:8px 0 0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

</x-email.layout>
