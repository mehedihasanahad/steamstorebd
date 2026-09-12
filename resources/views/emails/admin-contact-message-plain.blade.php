NEW CONTACT MESSAGE

{{ $contactMessage->name }} sent a message through the contact form.
Reply to this email to answer them directly.

SENDER
------
Name      : {{ $contactMessage->name }}
Email     : {{ $contactMessage->email }}
Received  : {{ $contactMessage->created_at->format('d M Y, h:i A') }}
@if($contactMessage->ip_address)
IP Address: {{ $contactMessage->ip_address }}
@endif

MESSAGE
-------
{{ $contactMessage->message }}

Mark this message as read under Contact Messages in the admin panel.
