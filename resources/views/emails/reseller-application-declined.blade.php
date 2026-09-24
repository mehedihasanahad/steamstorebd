<x-email.layout
    title="Update on your reseller application"
    preheader="An update on your reseller application."
    eyebrow="Reseller Program"
    :badge="$application->application_number"
>

    <x-email.banner tone="neutral" heading="Application update">
        Hi {{ $application->name }}, thank you for your interest in the {{ site_setting('site_name', 'Steam Store BD') }} reseller program.<br>
        After review, we are not able to approve your application at this time.
    </x-email.banner>

    {{-- The reason is the whole point of this email. Every colour on the page is
         already inline, so it survives a client that keeps no <style> at all. --}}
    <x-email.banner tone="danger" heading="Reason">
        <x-email.text tone="hi" align="center">{{ $application->decline_reason }}</x-email.text>
    </x-email.banner>

    <x-email.list title="You are welcome to apply again" :ordered="false">
        <li>Address the point above and submit a new application any time</li>
        <li>If you think this was a mistake, reply to this email or contact our support</li>
        <li>You can keep buying from us as a regular customer — nothing changes there</li>
    </x-email.list>

    <x-email.panel align="center">
        <x-email.text size="caption" tone="low" align="center" bottom="16px">There is no waiting period — apply again whenever you are ready.</x-email.text>
        <x-email.button :url="route('reseller')">Apply again</x-email.button>
    </x-email.panel>

    <x-slot:footer>
        <p style="margin:0;">Reference: <x-email.em tone="mid">{{ $application->application_number }}</x-email.em></p>
        <p style="margin:8px 0 0;">Questions? <x-email.link :url="route('contact')">Contact our support</x-email.link></p>
    </x-slot:footer>

</x-email.layout>
