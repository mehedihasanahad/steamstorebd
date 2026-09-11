<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the Reseller Program</title>
    @include('emails.partials.reseller-styles')
</head>
<body style="margin:0; padding:0; background-color:#030711;">
<table role="presentation" class="email-bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#030711" style="background-color:#030711;">
<tr>
<td align="center" style="padding:32px 16px;">
<div class="container">
    <div class="header">
        <div class="logo">{{ site_setting('site_name', 'Steam Store BD') }}</div>
        <p class="tagline">Reseller Program</p>
        <div class="ref-badge">{{ $application->application_number }}</div>
    </div>

    <div class="info-box info-success">
        <h2>🎉 Congratulations, {{ $application->name }}!</h2>
        <p>Your reseller application has been <strong style="color:#4ade80;">approved</strong>.<br>
        Welcome to the {{ site_setting('site_name', 'Steam Store BD') }} reseller network.</p>
    </div>

    <div class="section">
        <h3>🚀 What You Get</h3>
        @foreach($benefits as $benefit)
        <div class="detail-row">
            <span class="label">{{ $benefit['icon'] }} {{ $benefit['title'] }}</span>
        </div>
        @endforeach
    </div>

    <div class="steps">
        <h4>Getting started</h4>
        <ol>
            <li>Our team will contact you on WhatsApp ({{ $application->whatsapp_number }}) with your reseller price list</li>
            <li>Send us your order and payment, and we confirm your stock</li>
            <li>Your codes are delivered to this email — priority queue, ahead of regular orders</li>
        </ol>
    </div>

    @if(site_setting('contact_whatsapp'))
    <div class="cta">
        <a href="https://wa.me/{{ preg_replace('/\D/', '', site_setting('contact_whatsapp')) }}">💬 Chat With Our Team</a>
    </div>
    @endif

    <div class="footer">
        <p>Your reseller ID is <strong style="color:#9ca3af;">{{ $application->application_number }}</strong> — mention it when you contact us.</p>
        <p style="margin-top: 8px;">Questions? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 8px;">{{ site_setting('site_name', 'Steam Store BD') }} — <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
    </div>
</div>
</td>
</tr>
</table>
</body>
</html>
