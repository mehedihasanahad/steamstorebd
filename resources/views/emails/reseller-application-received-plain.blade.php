{{ site_setting('site_name', 'Steam Store BD') }} — RESELLER PROGRAM
Application {{ $application->application_number }}

Hi {{ $application->name }},

Thank you for applying to become a {{ site_setting('site_name', 'Steam Store BD') }} reseller.
Our team will review your application and get back to you {{ $responseTime }}.

YOUR APPLICATION
----------------
Application ID  : {{ $application->application_number }}
Name            : {{ $application->name }}
Email           : {{ $application->email }}
Phone           : {{ $application->phone }}
WhatsApp        : {{ $application->whatsapp_number }}
Selling Platform: {{ $application->platformLabel() }}
Gift Cards      : {{ $application->giftCardTypesLabel() }}

WHAT HAPPENS NEXT
-----------------
1. Our team reviews your application
2. We contact you on WhatsApp to verify your business
3. Once approved, you receive your reseller pricing and can start ordering

Keep this email — your application ID is {{ $application->application_number }}.

Questions? {{ route('contact') }}
{{ site_setting('site_name', 'Steam Store BD') }} — {{ route('home') }}
