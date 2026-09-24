@include('emails.partials.plain-header', ['eyebrow' => 'Reseller Program', 'heading' => 'Congratulations, ' . $application->name . '!'])

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
@if(site_setting('contact_whatsapp'))

Chat with our team: https://wa.me/{{ preg_replace('/\D/', '', site_setting('contact_whatsapp')) }}
@endif

@include('emails.partials.plain-footer', ['disclaimer' => 'Your reseller ID is ' . $application->application_number . ' — mention it when you contact us.'])
