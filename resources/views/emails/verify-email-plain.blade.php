@include('emails.partials.plain-header', ['eyebrow' => 'Account setup', 'heading' => 'Confirm your email'])

@php
    $firstName = trim(explode(' ', trim($user->name))[0] ?? '');
    $greeting  = $firstName !== '' ? $firstName . ', welcome' : 'Welcome';
@endphp
{{ $greeting }} to {{ site_setting('site_name', 'Steam Store BD') }}.

Open this address to confirm your email and finish setting up your account:

{{-- Unescaped on purpose: the signed URL carries ?expires=...&signature=...,
     and an escaped & arrives in a text/plain part as a literal &amp; that
     breaks the signature. Nothing here is user input. --}}
{!! $verificationUrl !!}

The link works once and expires in {{ $expiresInMinutes }} minutes.

WHY WE ASK
----------
- Your gift card codes are delivered to this address
- It is how we reach you if a code ever needs replacing
- It stops anyone else ordering in your name

Until it is confirmed you can browse and build a cart — only checkout waits on this.

@include('emails.partials.plain-footer', ['disclaimer' => "Did not create an account? Ignore this email — nothing happens until the link is opened."])
