<x-email.layout
    title="Reseller application received"
    :preheader="'We have your application — we will get back to you ' . $responseTime . '.'"
    eyebrow="Reseller Program"
    :badge="$application->application_number"
>

    <x-email.banner tone="info" heading="Application received">
        Hi {{ $application->name }}, thank you for applying to become a {{ site_setting('site_name', 'Steam Store BD') }} reseller.<br>
        Our team will review it and get back to you <x-email.em tone="info">{{ $responseTime }}</x-email.em>.
    </x-email.banner>

    <x-email.panel title="Your application">
        <x-email.rows>
            <x-email.row label="Application ID" mono>{{ $application->application_number }}</x-email.row>
            <x-email.row label="Name">{{ $application->name }}</x-email.row>
            <x-email.row label="Email">{{ $application->email }}</x-email.row>
            <x-email.row label="Phone">{{ $application->phone }}</x-email.row>
            <x-email.row label="WhatsApp">{{ $application->whatsapp_number }}</x-email.row>
            <x-email.row label="Selling platform">{{ $application->platformLabel() }}</x-email.row>
            <x-email.row label="Gift cards" :divider="false">{{ $application->giftCardTypesLabel() }}</x-email.row>
        </x-email.rows>
    </x-email.panel>

    <x-email.list title="What happens next?">
        <li>Our team reviews your application</li>
        <li>We contact you on WhatsApp to verify your business</li>
        <li>Once approved, you receive your reseller pricing and can start ordering</li>
    </x-email.list>

    <x-slot:footer>
        <p style="margin:0;">Keep this email — your application ID is <x-email.em tone="mid">{{ $application->application_number }}</x-email.em>.</p>
        <p style="margin:8px 0 0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

</x-email.layout>
