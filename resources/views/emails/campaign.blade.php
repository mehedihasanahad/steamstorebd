<x-email.layout
    :title="$campaign->subject"
    :preheader="$campaign->preheader ?: ''"
    eyebrow="Gift cards, top-ups, keys and subscriptions"
>

    {{-- The body is admin-authored HTML with its styles already inlined, so it
         is echoed raw rather than escaped. --}}
    <x-email.panel>
        {!! $bodyHtml !!}
    </x-email.panel>

    @if($campaign->cta_label && $campaign->cta_url)
    <x-email.panel align="center">
        <x-email.button :url="$campaign->cta_url">{{ $campaign->cta_label }}</x-email.button>
    </x-email.panel>
    @endif

    <x-slot:footer>
        <p style="margin:0;">Need help? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

    <x-slot:disclaimer>
        You are receiving this because you have ordered from {{ site_setting('site_name', 'Steam Store BD') }}.
        <x-email.link :url="$unsubscribeUrl" tone="muted">Unsubscribe from offers</x-email.link> —
        your order confirmations and codes are not affected.
    </x-slot:disclaimer>

</x-email.layout>
