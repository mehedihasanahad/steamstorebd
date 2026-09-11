<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Application Received</title>
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

    <div class="info-box info-pending">
        <h2>✅ Application Received</h2>
        <p>Hi {{ $application->name }}, thank you for applying to become a {{ site_setting('site_name', 'Steam Store BD') }} reseller.<br>
        Our team will review your application and get back to you <strong style="color:#fbbf24;">{{ $responseTime }}</strong>.</p>
    </div>

    <div class="section">
        <h3>📋 Your Application</h3>
        <div class="detail-row">
            <span class="label">Application ID</span>
            <span class="value" style="font-family:monospace;">{{ $application->application_number }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Name</span>
            <span class="value">{{ $application->name }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Email</span>
            <span class="value">{{ $application->email }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Phone</span>
            <span class="value">{{ $application->phone }}</span>
        </div>
        <div class="detail-row">
            <span class="label">WhatsApp</span>
            <span class="value">{{ $application->whatsapp_number }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Selling Platform</span>
            <span class="value">{{ $application->platformLabel() }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Gift Cards</span>
            <span class="value">{{ $application->giftCardTypesLabel() }}</span>
        </div>
    </div>

    <div class="steps">
        <h4>What happens next?</h4>
        <ol>
            <li>Our team reviews your application</li>
            <li>We contact you on WhatsApp to verify your business</li>
            <li>Once approved, you receive your reseller pricing and can start ordering</li>
        </ol>
    </div>

    <div class="footer">
        <p>Keep this email — your application ID is <strong style="color:#9ca3af;">{{ $application->application_number }}</strong>.</p>
        <p style="margin-top: 8px;">Questions? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 8px;">{{ site_setting('site_name', 'Steam Store BD') }} — <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
    </div>
</div>
</td>
</tr>
</table>
</body>
</html>
