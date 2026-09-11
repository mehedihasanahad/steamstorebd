NEW RESELLER APPLICATION — {{ $application->application_number }}

{{ $application->name }} has applied to join the reseller program.
Verify the applicant before approving.

APPLICANT DETAILS
-----------------
Name            : {{ $application->name }}
Email           : {{ $application->email }}
Phone           : {{ $application->phone }}
WhatsApp        : {{ $application->whatsapp_number }}
Selling Platform: {{ $application->platformLabel() }}
Wants To Sell   : {{ $application->giftCardTypesLabel() }}
Submitted       : {{ $application->created_at->format('d M Y, h:i A') }}
@if($application->ip_address)
IP Address      : {{ $application->ip_address }}
@endif

Message on WhatsApp: {{ $application->whatsappLink() }}

BEFORE YOU APPROVE
------------------
- Check their selling platform actually exists and has history
- Ask who they buy from now and at what price
- Confirm expected monthly volume and which SKUs move
- Start them on a small first order — never best pricing on day one

Approve or decline this application from the admin panel.
