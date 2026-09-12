<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Message</title>
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
    </div>

    <div class="info-box info-pending">
        <h2>✉️ New Contact Message</h2>
        <p><strong style="color:#fff;">{{ $contactMessage->name }}</strong> sent a message through the contact form.<br>
        Reply to this email to answer them directly.</p>
    </div>

    <div class="section">
        <h3>👤 Sender</h3>
        <div class="detail-row">
            <span class="label">Name</span>
            <span class="value">{{ $contactMessage->name }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Email</span>
            <span class="value">{{ $contactMessage->email }}</span>
        </div>
        <div class="detail-row">
            <span class="label">Received</span>
            <span class="value">{{ $contactMessage->created_at->format('d M Y, h:i A') }}</span>
        </div>
        @if($contactMessage->ip_address)
        <div class="detail-row">
            <span class="label">IP Address</span>
            <span class="value" style="font-family:monospace;">{{ $contactMessage->ip_address }}</span>
        </div>
        @endif
    </div>

    <div class="section">
        <h3>💬 Message</h3>
        <p style="margin:0; color:#CBD5E1; line-height:1.6; white-space:pre-line;">{{ $contactMessage->message }}</p>
    </div>

    <div class="footer">
        <p>Mark this message as read under Contact Messages in the admin panel.</p>
        <p style="margin-top: 8px;">{{ site_setting('site_name', 'Steam Store BD') }} — <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
    </div>
</div>
</td>
</tr>
</table>
</body>
</html>
