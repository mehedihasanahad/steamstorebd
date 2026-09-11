<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Reseller Application</title>
    @include('emails.partials.reseller-styles')
</head>
<body style="margin:0; padding:0; background-color:#030711;">
<table role="presentation" class="email-bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#030711" style="background-color:#030711;">
<tr>
<td align="center" style="padding:32px 16px;">
<div class="container">
    <div class="header">
        <div class="logo">{{ site_setting('site_name', 'Steam Store BD') }}</div>
        <p class="tagline">Admin Notification</p>
        <div class="ref-badge">{{ $application->application_number }}</div>
    </div>

    <div class="info-box info-pending">
        <h2>🤝 New Reseller Application</h2>
        <p><strong style="color:#fff;">{{ $application->name }}</strong> has applied to join the reseller program.<br>
        Verify the applicant before approving.</p>
    </div>

    <div class="section">
        <h3>👤 Applicant Details</h3>
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
            <span class="label">Wants To Sell</span>
            <span class="value">{{ $application->giftCardTypesLabel() }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Submitted</span>
            <span class="value">{{ $application->created_at->format('d M Y, h:i A') }}</span>
        </div>
        @if($application->ip_address)
        <div class="detail-row">
            <span class="label">IP Address</span>
            <span class="value" style="font-family:monospace;">{{ $application->ip_address }}</span>
        </div>
        @endif
    </div>

    <div class="cta">
        <a href="{{ $application->whatsappLink() }}">💬 Message on WhatsApp</a>
    </div>

    <div class="steps">
        <h4>Before you approve</h4>
        <ul>
            <li>Check their selling platform actually exists and has history</li>
            <li>Ask who they buy from now and at what price</li>
            <li>Confirm expected monthly volume and which SKUs move</li>
            <li>Start them on a small first order — never best pricing on day one</li>
        </ul>
    </div>

    <div class="footer">
        <p>Approve or decline this application from the admin panel.</p>
        <p style="margin-top: 8px;">{{ site_setting('site_name', 'Steam Store BD') }} — <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
    </div>
</div>
</td>
</tr>
</table>
</body>
</html>
