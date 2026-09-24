@include('emails.partials.plain-header', ['eyebrow' => 'Reseller Program', 'heading' => 'Application update — ' . $application->application_number])

Hi {{ $application->name }},

Thank you for your interest in the {{ site_setting('site_name', 'Steam Store BD') }} reseller program.
After review, we are not able to approve your application at this time.

REASON
------
{{ $application->decline_reason }}

YOU ARE WELCOME TO APPLY AGAIN
------------------------------
- Address the point above and submit a new application any time
- If you think this was a mistake, reply to this email or contact our support
- You can keep buying from us as a regular customer — nothing changes there

Apply again: {{ route('reseller') }}

@include('emails.partials.plain-footer', ['disclaimer' => 'Reference: ' . $application->application_number])
