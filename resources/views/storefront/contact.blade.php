@extends('layouts.storefront')

@section('title', 'Contact Us — Steam Store BD')
@section('meta_description', 'Contact Steam Store BD about an order, a code or a top-up. Message us on WhatsApp or by e-mail and get a reply within 5–10 minutes.')

@php
    // The on-site contact form is switched off, so this page hands every
    // message to a channel we already staff. The product chat settings win
    // when they are configured; the general contact settings are the fallback
    // for a store that has not filled those in.
    $chat = \App\Services\ChatLinkBuilder::fromSettings();

    $email = site_setting('contact_email');

    $whatsappDigits = $chat->whatsappEnabled()
        ? $chat->whatsappNumber()
        : (string) preg_replace('/\D/', '', (string) site_setting('contact_whatsapp', ''));

    $whatsappUrl     = $whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits : null;
    $whatsappDisplay = site_setting('contact_whatsapp') ?: '+' . $whatsappDigits;

    $messengerUrl = $chat->messengerEnabled() ? $chat->messengerUrl() : null;
@endphp

@section('content')

<div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Contact us', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Get in touch</h1>
    <p class="mt-1 max-w-2xl text-body text-ink-mid">A question about an order, a code or a top-up? Message us and we will come straight back.</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <x-ui.badge tone="success">Reply in 5–10 minutes</x-ui.badge>
        <x-ui.badge tone="accent">Every day</x-ui.badge>
    </div>

    <div class="mt-6 grid grid-cols-12 gap-5">

        {{-- The three things most messages turn out to be about, answered
             before the channels so nobody has to wait for a reply they could
             have had immediately. --}}
        <aside class="col-span-12 space-y-3 lg:col-span-5" aria-label="Before you write">
            @foreach([
                ['Checking an order', 'Status, codes and delivery progress are all on your order page.', 'Track my order', route('orders.lookup')],
                ['Redeeming a code', 'Step-by-step for gift cards, keys, top-ups and subscriptions.', 'Redemption guide', route('how-to-redeem')],
                ['Payments and refunds', 'What we accept, how long things take, and when we replace a code.', 'Read the FAQ', route('faq')],
            ] as [$title, $body, $cta, $url])
                <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                    <p class="text-body font-semibold text-ink-hi">{{ $title }}</p>
                    <p class="mt-1 text-caption leading-relaxed text-ink-mid">{{ $body }}</p>
                    <a href="{{ $url }}" class="mt-2 inline-flex items-center gap-1 text-caption font-semibold text-accent-hover hover:underline">
                        {{ $cta }}
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @endforeach
        </aside>

        <div class="col-span-12 lg:col-span-7">
            <div class="rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6">

                <h2 class="text-lede font-bold text-ink-hi">Message our support team</h2>
                <p class="mt-1 text-caption text-ink-low">Include your order number if you have one — it saves a round trip.</p>

                <div class="mt-4 space-y-2">
                    @if($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                           class="flex min-h-[44px] items-center gap-3 rounded-control border border-whatsapp px-4 py-2.5 transition-colors hover:bg-whatsapp/10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="flex-shrink-0 text-whatsapp">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            <span class="min-w-0">
                                <span class="block text-body font-semibold text-ink-hi">WhatsApp</span>
                                <span class="block truncate text-caption text-ink-mid">{{ $whatsappDisplay }}</span>
                            </span>
                        </a>
                    @endif

                    @if($messengerUrl)
                        <a href="{{ $messengerUrl }}" target="_blank" rel="noopener noreferrer"
                           class="flex min-h-[44px] items-center gap-3 rounded-control border border-messenger px-4 py-2.5 transition-colors hover:bg-messenger/10">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="flex-shrink-0 text-messenger">
                                <path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.465 3.442.465 6.627 0 12-4.975 12-11.112S18.627 0 12 0zm1.194 14.963l-3.055-3.26-5.963 3.26L10.426 8.4l3.129 3.26 5.889-3.26-6.25 6.563z"/>
                            </svg>
                            <span class="min-w-0">
                                <span class="block text-body font-semibold text-ink-hi">Messenger</span>
                                <span class="block truncate text-caption text-ink-mid">Chat with us on Facebook</span>
                            </span>
                        </a>
                    @endif

                    @if($email)
                        <a href="mailto:{{ $email }}"
                           class="flex min-h-[44px] items-center gap-3 rounded-control border border-surface-3 bg-surface-2 px-4 py-2.5 transition-colors hover:border-accent/60 hover:bg-surface-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-ink-mid" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="min-w-0">
                                <span class="block text-body font-semibold text-ink-hi">E-mail</span>
                                <span class="block truncate text-caption text-ink-mid">{{ $email }}</span>
                            </span>
                        </a>
                    @endif

                    @if(! $whatsappUrl && ! $messengerUrl && ! $email)
                        <p class="text-caption text-ink-mid">
                            Our support channels are being updated. In the meantime the
                            <a href="{{ route('faq') }}" class="text-accent-hover hover:underline">FAQ</a>
                            covers the questions we are asked most.
                        </p>
                    @endif
                </div>

                <p class="mt-4 text-meta text-ink-low">
                    By messaging us you agree to our
                    <a href="{{ route('faq') }}" class="underline underline-offset-2 hover:text-ink-mid">FAQ and policies</a>.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection
