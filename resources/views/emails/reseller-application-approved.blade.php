<x-email.layout
    title="Welcome to the Reseller Program"
    preheader="Your reseller application has been approved."
    eyebrow="Reseller Program"
    :badge="$application->application_number"
>

    <x-email.banner tone="success" :heading="'Congratulations, ' . $application->name . '!'">
        Your reseller application has been <x-email.em tone="success">approved</x-email.em>.<br>
        Welcome to the {{ site_setting('site_name', 'Steam Store BD') }} reseller network.
    </x-email.banner>

    {{-- Stacked rather than tabulated: a benefit carries a description, and a
         sentence in a right-aligned value column wraps into a ragged column. --}}
    <x-email.panel title="What you get">
        @foreach($benefits as $benefit)
        <x-email.text size="body" tone="hi" :top="$loop->first ? '0' : '14px'">{{ $benefit['icon'] }} <strong>{{ $benefit['title'] }}</strong></x-email.text>
        @if($benefit['description'] !== '')
        <x-email.text size="caption" tone="low" top="4px">{{ $benefit['description'] }}</x-email.text>
        @endif
        @endforeach
    </x-email.panel>

    <x-email.list title="Getting started">
        <li>Our team will contact you on WhatsApp ({{ $application->whatsapp_number }}) with your reseller price list</li>
        <li>Send us your order and payment, and we confirm your stock</li>
        <li>Your codes are delivered to this email — priority queue, ahead of regular orders</li>
    </x-email.list>

    @if(site_setting('contact_whatsapp'))
    <x-email.panel align="center">
        <x-email.text size="caption" tone="low" align="center" bottom="16px">Your price list is one message away.</x-email.text>
        <x-email.button :url="'https://wa.me/' . preg_replace('/\D/', '', site_setting('contact_whatsapp'))">Chat with our team</x-email.button>
    </x-email.panel>
    @endif

    <x-slot:footer>
        <p style="margin:0;">Your reseller ID is <x-email.em tone="mid">{{ $application->application_number }}</x-email.em> — mention it when you contact us.</p>
        <p style="margin:8px 0 0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

</x-email.layout>
