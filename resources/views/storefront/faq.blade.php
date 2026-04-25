@extends('layouts.storefront')

@section('title', 'Steam Gift Card Bangladesh FAQ | How to Buy with bKash — Steam Store BD')
@section('meta_description', 'Common questions about buying Steam gift cards in Bangladesh. How to pay with bKash, instant delivery, genuine codes, refund policy, and how to redeem on Steam.')
@section('meta_keywords', 'steam gift card bangladesh faq, steam wallet bkash, how to buy steam card bd, steam gift card kena kivabe bangladesh, steam code redeem bangladesh')

@push('schema')
@php
$_faqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => [
        ['@type'=>'Question','name'=>'What is Steam Store BD?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Steam Store BD is a trusted digital marketplace for Steam gift cards in Bangladesh. We offer instant delivery of authentic Steam gift card codes via email, with secure bKash payment.']],
        ['@type'=>'Question','name'=>'How do I buy a Steam gift card in Bangladesh?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Select your desired Steam gift card denomination on Steam Store BD, add it to cart, proceed to checkout, and pay securely with bKash. Your code is delivered instantly to your email.']],
        ['@type'=>'Question','name'=>'How do I pay for my order?','acceptedAnswer'=>['@type'=>'Answer','text'=>"We accept payment via bKash Tokenized Checkout. Simply enter your details, click 'Pay with bKash', and complete payment in the bKash app. Safe and instant."]],
        ['@type'=>'Question','name'=>'How quickly will I receive my Steam gift card code?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Immediately! As soon as your bKash payment is confirmed, your code is displayed on the screen and emailed to your registered email address within minutes.']],
        ['@type'=>'Question','name'=>'Are the Steam gift card codes genuine?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. All gift card codes sold on Steam Store BD are 100% authentic Steam codes. They are valid worldwide and can be redeemed directly on Steam.']],
        ['@type'=>'Question','name'=>'What Steam gift card denominations are available in Bangladesh?','acceptedAnswer'=>['@type'=>'Answer','text'=>'We offer Steam gift cards in various USD denominations ($5, $10, $20, $50, $100) priced in BDT, as well as BDT denominations for local purchases.']],
        ['@type'=>'Question','name'=>'Can I buy multiple Steam cards at once?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes, you can add up to 5 of the same denomination in a single order.']],
        ['@type'=>'Question','name'=>'My Steam code does not work. What should I do?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Please contact us with your order number and a screenshot of the error message on Steam. We guarantee all our codes and will resolve the issue promptly.']],
        ['@type'=>'Question','name'=>'Do you offer refunds on Steam gift cards?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Since gift card codes are digital products delivered instantly, we generally cannot offer refunds once a code has been revealed. However, if a code is defective, we will replace it.']],
        ['@type'=>'Question','name'=>'How do I redeem a Steam gift card in Bangladesh?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Open Steam, go to Account Details, click Redeem a Gift Card, enter your code and click Continue. The funds are added instantly to your Steam Wallet and can be used worldwide.']],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($_faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-white mb-4">Frequently Asked Questions</h1>
        <p class="text-gray-400">Everything you need to know about Steam Store BD</p>
    </div>

    <div x-data="{ open: null }" class="space-y-3">
        @foreach([
            ['q' => 'What is Steam Store BD?', 'a' => 'Steam Store BD is a trusted digital marketplace for Steam gift cards in Bangladesh. We offer instant delivery of authentic Steam gift card codes via email, with secure bKash payment.'],
            ['q' => 'How do I pay for my order?', 'a' => 'We accept payment via bKash Tokenized Checkout. Simply enter your details, click "Pay with bKash", and complete payment in the bKash app. Safe and instant.'],
            ['q' => 'How quickly will I receive my gift card code?', 'a' => 'Immediately! As soon as your bKash payment is confirmed, your code is displayed on the screen and emailed to your registered email address within minutes.'],
            ['q' => 'Are the gift card codes genuine?', 'a' => 'Yes. All gift card codes sold on Steam Store BD are 100% authentic Steam codes. They are valid worldwide and can be redeemed directly on Steam.'],
            ['q' => 'What denominations do you offer?', 'a' => 'We offer Steam gift cards in various USD denominations ($5, $10, $20, $50, $100) priced in BDT, as well as BDT denominations for local purchases.'],
            ['q' => 'Can I buy multiple cards at once?', 'a' => 'Yes, you can add up to 5 of the same denomination in a single order.'],
            ['q' => 'I entered the wrong email. What do I do?', 'a' => 'Contact us immediately via our contact page or WhatsApp. Provide your order number and the correct email address, and we\'ll resend your codes.'],
            ['q' => 'My code doesn\'t work. What should I do?', 'a' => 'Please contact us with your order number and a screenshot of the error message on Steam. We guarantee all our codes and will resolve the issue promptly.'],
            ['q' => 'Do you offer refunds?', 'a' => 'Since gift card codes are digital products delivered instantly, we generally cannot offer refunds once a code has been revealed. However, if a code is defective, we will replace it.'],
            ['q' => 'Is Steam Store BD affiliated with Valve Corporation?', 'a' => 'No. Steam Store BD is an independent reseller and is not affiliated with, endorsed by, or connected to Valve Corporation. Steam and the Steam logo are trademarks of Valve Corporation.'],
        ] as $idx => $faq)
        <div class="bg-gray-900 rounded-2xl border border-gray-700/50 overflow-hidden hover:border-brand-500/30 transition-colors">
            <button @click="open = open === {{ $idx }} ? null : {{ $idx }}"
                    class="w-full flex items-center justify-between p-5 text-left group">
                <span class="font-semibold text-white group-hover:text-brand-400 transition-colors">{{ $faq['q'] }}</span>
                <svg class="w-5 h-5 text-brand-400 flex-shrink-0 ml-4 transition-transform duration-200" :class="open === {{ $idx }} ? 'rotate-180' : ''"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="open === {{ $idx }}" x-cloak class="px-5 pb-5 text-gray-400 text-sm leading-relaxed">
                {{ $faq['a'] }}
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-12">
        <p class="text-gray-400">Still have questions?</p>
        <a href="{{ route('contact') }}" class="btn-steam font-semibold px-8 py-3 rounded-xl inline-block mt-4 transition-all hover:shadow-steam-glow">
            Contact Us
        </a>
    </div>
</div>
@endsection
