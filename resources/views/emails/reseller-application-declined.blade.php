<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update on your reseller application</title>
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

    <div class="info-box info-neutral">
        <h2>Application Update</h2>
        <p>Hi {{ $application->name }}, thank you for your interest in the {{ site_setting('site_name', 'Steam Store BD') }} reseller program.<br>
        After review, we are not able to approve your application at this time.</p>
    </div>

    {{-- The reason is the whole point of this email, so it carries inline
         colours as well as the class — it stays readable even in a client
         that strips the <style> block completely. --}}
    <div class="reason-box" style="background-color:#2A1114; border:1px solid #7F2426; border-radius:12px; padding:18px 20px; margin-bottom:20px;">
        <div class="reason-label" style="color:#FCA5A5; font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:8px;">Reason</div>
        <div class="reason-text" style="color:#F1F5F9; font-size:15px; line-height:1.8;">{{ $application->decline_reason }}</div>
    </div>

    <div class="steps">
        <h4>You are welcome to apply again</h4>
        <ul>
            <li>Address the point above and submit a new application any time</li>
            <li>If you think this was a mistake, reply to this email or contact our support</li>
            <li>You can keep buying from us as a regular customer — nothing changes there</li>
        </ul>
    </div>

    <div class="cta">
        <a href="{{ route('reseller') }}">Apply Again</a>
    </div>

    <div class="footer">
        <p>Reference: <strong style="color:#9ca3af;">{{ $application->application_number }}</strong></p>
        <p style="margin-top: 8px;">Questions? <a href="{{ route('contact') }}">Contact our support</a></p>
        <p style="margin-top: 8px;">{{ site_setting('site_name', 'Steam Store BD') }} — <a href="{{ route('home') }}">{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'steamstorebd.com' }}</a></p>
    </div>
</div>
</td>
</tr>
</table>
</body>
</html>
