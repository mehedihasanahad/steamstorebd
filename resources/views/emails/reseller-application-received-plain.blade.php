@include('emails.partials.plain-header', ['eyebrow' => 'Reseller Program', 'heading' => 'Application received — ' . $application->application_number])

Hi {{ $application->name }},

Thank you for applying to become a {{ site_setting('site_name', 'Steam Store BD') }} reseller.
Our team will review your application and get back to you {{ $responseTime }}.

YOUR APPLICATION
----------------
Application ID   : {{ $application->application_number }}
Name             : {{ $application->name }}
Email            : {{ $application->email }}
Phone            : {{ $application->phone }}
WhatsApp         : {{ $application->whatsapp_number }}
Selling platform : {{ $application->platformLabel() }}
Gift cards       : {{ $application->giftCardTypesLabel() }}

WHAT HAPPENS NEXT
-----------------
1. Our team reviews your application
2. We contact you on WhatsApp to verify your business
3. Once approved, you receive your reseller pricing and can start ordering

@include('emails.partials.plain-footer', ['disclaimer' => 'Keep this email — your application ID is ' . $application->application_number . '.'])
