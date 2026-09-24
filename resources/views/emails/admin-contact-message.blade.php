<x-email.layout
    title="New contact message"
    :preheader="$contactMessage->name . ' sent a message through the contact form.'"
    eyebrow="Admin notification"
>

    <x-email.banner tone="info" heading="New contact message">
        <x-email.em>{{ $contactMessage->name }}</x-email.em> sent a message through the contact form.<br>
        Reply to this email to answer them directly.
    </x-email.banner>

    <x-email.panel title="Sender">
        <x-email.rows>
            <x-email.row label="Name">{{ $contactMessage->name }}</x-email.row>
            <x-email.row label="Email">{{ $contactMessage->email }}</x-email.row>
            <x-email.row label="Received" :divider="(bool) $contactMessage->ip_address">{{ $contactMessage->created_at->format('d M Y, h:i A') }}</x-email.row>
            @if($contactMessage->ip_address)
            <x-email.row label="IP address" mono :divider="false">{{ $contactMessage->ip_address }}</x-email.row>
            @endif
        </x-email.rows>
    </x-email.panel>

    <x-email.panel title="Message">
        {{-- pre-line so the sender's own paragraphs survive the trip. --}}
        <x-email.text tone="hi" lines="pre-line">{{ $contactMessage->message }}</x-email.text>
    </x-email.panel>

    <x-slot:footer>
        <p style="margin:0;">Mark this message as read under Contact Messages in the admin panel.</p>
    </x-slot:footer>

</x-email.layout>
