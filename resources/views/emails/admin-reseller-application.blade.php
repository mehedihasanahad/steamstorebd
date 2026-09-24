<x-email.layout
    title="New reseller application"
    :preheader="$application->name . ' has applied to join the reseller program.'"
    eyebrow="Admin notification"
    :badge="$application->application_number"
>

    <x-email.banner tone="info" heading="New reseller application">
        <x-email.em>{{ $application->name }}</x-email.em> has applied to join the reseller program.<br>
        Verify the applicant before approving.
    </x-email.banner>

    <x-email.panel title="Applicant details">
        <x-email.rows>
            <x-email.row label="Name">{{ $application->name }}</x-email.row>
            <x-email.row label="Email">{{ $application->email }}</x-email.row>
            <x-email.row label="Phone">{{ $application->phone }}</x-email.row>
            <x-email.row label="WhatsApp">{{ $application->whatsapp_number }}</x-email.row>
            <x-email.row label="Selling platform">{{ $application->platformLabel() }}</x-email.row>
            <x-email.row label="Wants to sell">{{ $application->giftCardTypesLabel() }}</x-email.row>
            <x-email.row label="Submitted" :divider="(bool) $application->ip_address">{{ $application->created_at->format('d M Y, h:i A') }}</x-email.row>
            @if($application->ip_address)
            <x-email.row label="IP address" mono :divider="false">{{ $application->ip_address }}</x-email.row>
            @endif
        </x-email.rows>
    </x-email.panel>

    <x-email.panel align="center">
        <x-email.text size="caption" tone="low" align="center" bottom="16px">Verification goes faster on WhatsApp than over email.</x-email.text>
        <x-email.button :url="$application->whatsappLink()">Message on WhatsApp</x-email.button>
    </x-email.panel>

    <x-email.list title="Before you approve" :ordered="false">
        <li>Check their selling platform actually exists and has history</li>
        <li>Ask who they buy from now and at what price</li>
        <li>Confirm expected monthly volume and which SKUs move</li>
        <li>Start them on a small first order — never best pricing on day one</li>
    </x-email.list>

    <x-slot:footer>
        <p style="margin:0;">Approve or decline this application from the admin panel.</p>
    </x-slot:footer>

</x-email.layout>
