@extends('layouts.storefront')

@section('title', 'FAQ: Payment, Delivery & Refunds — Steam Store BD')
@section('meta_description', 'Answers about buying gift cards, game top-ups, keys and subscriptions in Bangladesh: paying with bKash or Nagad, delivery time, genuine codes, refunds and redeeming.')

@php
    // One array drives both the visible accordion and the FAQPage schema.
    // Two copies of this list would drift, and the version Google reads would
    // be the one nobody proof-reads.
    $faqs = [
        [
            'q' => 'What is Steam Store BD?',
            'a' => 'Steam Store BD is a Bangladeshi digital goods store. We sell gift cards, game top-ups, game keys and subscriptions, delivered to your e-mail and your account. Payment is through bKash, Nagad and Rocket.',
        ],
        [
            'q' => 'What do you sell?',
            'a' => 'Gift cards (Steam, Google Play, App Store and more), direct game top-ups such as PUBG UC and Free Fire diamonds, game and software keys, and subscription plans. New brands are added regularly — browse the catalog menu for the current list.',
        ],
        [
            'q' => 'How do I buy?',
            'a' => 'Pick a product, choose a denomination, and check out. Some products — top-ups in particular — also ask for your Player ID or account e-mail so we can credit the right account. Pay with bKash or Nagad and your order is delivered.',
        ],
        [
            'q' => 'Which payment methods do you accept?',
            'a' => 'bKash (tokenized checkout and send money), Nagad (send money) and Rocket (send money). All transactions are secure and encrypted.',
        ],
        [
            'q' => 'How quickly will I receive my order?',
            'a' => 'Gift cards and keys are instant: the code appears on screen and in your e-mail as soon as payment is confirmed. Top-ups and subscriptions are fulfilled by our team, and each product page shows the time frame it promises.',
        ],
        [
            'q' => 'Are the codes genuine?',
            'a' => 'Yes. Every code is sourced directly and checked before sale. They are valid and ready to redeem the moment you receive them.',
        ],
        [
            'q' => 'What is a region, and does it matter?',
            'a' => 'Some gift cards only work on an account registered in a particular country. The region is shown on the product page, and products that exist in more than one region have a region switcher in their header. If you are unsure, message us before buying.',
        ],
        [
            'q' => 'Can I buy several things at once?',
            'a' => 'Yes. Add as many products to your cart as you like and pay for them together. In the cart you can untick anything you would rather buy later; only the ticked lines are checked out.',
        ],
        [
            'q' => 'I entered the wrong e-mail address. What do I do?',
            'a' => 'Contact us straight away through the Contact page or WhatsApp with your order number and the correct address. We will resend as quickly as we can.',
        ],
        [
            'q' => 'My code does not work. What should I do?',
            'a' => 'Contact us with your order number and a screenshot of the error. We guarantee every code and will replace a defective one at no extra cost.',
        ],
        [
            'q' => 'Do you offer refunds?',
            'a' => 'Digital codes are delivered instantly, so refunds are generally not available once a code has been revealed. A defective code is replaced immediately. Our full terms are on the Refund Policy page.',
        ],
        [
            'q' => 'Is Steam Store BD an official brand partner?',
            'a' => 'No. Steam Store BD is an independent reseller and is not affiliated with Valve Corporation, Google, Apple or any other brand whose products we sell. All brand names and logos are trademarks of their respective owners.',
        ],
    ];
@endphp

@push('schema')
@php
$_faqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn (array $faq) => [
        '@type'          => 'Question',
        'name'           => $faq['q'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
    ])->all(),
];
@endphp
<script type="application/ld+json">{!! json_encode($_faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'FAQ', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Frequently asked questions</h1>
    <p class="mt-1 text-body text-ink-mid">Everything about buying, paying and redeeming at Steam Store BD.</p>

    <div x-data="{ open: 0 }" class="mt-6 space-y-2">
        @foreach($faqs as $idx => $faq)
            <div class="rounded-card border bg-surface-1 transition-colors"
                 :class="open === {{ $idx }} ? 'border-accent/45' : 'border-surface-3'">
                <h2>
                    <button type="button" @click="open = open === {{ $idx }} ? null : {{ $idx }}"
                            :aria-expanded="open === {{ $idx }} ? 'true' : 'false'"
                            class="flex w-full items-center justify-between gap-4 px-4 py-4 text-left">
                        <span class="text-body font-semibold text-ink-hi">{{ $faq['q'] }}</span>
                        <svg class="h-4 w-4 flex-shrink-0 text-ink-low transition-transform" :class="open === {{ $idx }} ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </h2>
                <div x-show="open === {{ $idx }}" x-cloak class="border-t border-surface-3 px-4 py-4 text-caption leading-relaxed text-ink-mid">
                    {{ $faq['a'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-8 rounded-card border border-surface-3 bg-surface-1 p-6 text-center">
        <p class="text-body font-semibold text-ink-hi">Still have a question?</p>
        <p class="mt-1 text-caption text-ink-low">Our support team answers every day.</p>
        <x-ui.button :href="route('contact')" class="mt-4">Contact support</x-ui.button>
    </div>
</div>

@endsection
