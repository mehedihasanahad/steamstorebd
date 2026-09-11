{{ site_setting('site_name', 'Steam Store BD') }} — RESELLER PROGRAM
Application {{ $application->application_number }}

CONGRATULATIONS, {{ $application->name }}!

Your reseller application has been APPROVED.
Welcome to the {{ site_setting('site_name', 'Steam Store BD') }} reseller network.

WHAT YOU GET
------------
@foreach($benefits as $benefit)
- {{ $benefit['title'] }}{{ $benefit['description'] !== '' ? ': ' . $benefit['description'] : '' }}
@endforeach

GETTING STARTED
---------------
1. Our team will contact you on WhatsApp ({{ $application->whatsapp_number }}) with your reseller price list
2. Send us your order and payment, and we confirm your stock
3. Your codes are delivered to this email — priority queue, ahead of regular orders

Your reseller ID is {{ $application->application_number }} — mention it when you contact us.

Questions? {{ route('contact') }}
{{ site_setting('site_name', 'Steam Store BD') }} — {{ route('home') }}
