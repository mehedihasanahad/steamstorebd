@include('emails.partials.plain-header', ['eyebrow' => 'Admin notification', 'heading' => 'New contact message'])

{{ $contactMessage->name }} sent a message through the contact form.
Reply to this email to answer them directly.

SENDER
------
Name       : {{ $contactMessage->name }}
Email      : {{ $contactMessage->email }}
Received   : {{ $contactMessage->created_at->format('d M Y, h:i A') }}
@if($contactMessage->ip_address)
IP address : {{ $contactMessage->ip_address }}
@endif

MESSAGE
-------
{{ $contactMessage->message }}

@include('emails.partials.plain-footer', ['support' => false, 'disclaimer' => 'Mark this message as read under Contact Messages in the admin panel.'])
