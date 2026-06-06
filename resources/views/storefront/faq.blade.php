@extends('layouts.storefront')

@section('title', 'FAQ — Gift Cards Bangladesh | How to Buy with bKash & Nagad — Steam Store BD')
@section('meta_description', 'Frequently asked questions about buying digital gift cards in Bangladesh. How to pay with bKash or Nagad, instant delivery, genuine codes, refund policy, and how to redeem your gift card.')
@section('meta_keywords', 'gift card bangladesh faq, buy gift card bkash, gift card nagad bangladesh, how to buy gift card bd, gift card redeem bangladesh, steam google play gift card faq bd')

@push('schema')
@php
$_faqSchema = [
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => [
        ['@type'=>'Question','name'=>'What is Steam Store BD?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Steam Store BD is a trusted digital gift card marketplace in Bangladesh. We sell Steam, Google Play, App Store, and other popular gift cards with instant code delivery via email. Payment is accepted through bKash and Nagad.']],
        ['@type'=>'Question','name'=>'What gift cards do you sell?','acceptedAnswer'=>['@type'=>'Answer','text'=>'We sell a wide range of digital gift cards including Steam Wallet gift cards, Google Play gift cards, Apple App Store gift cards, and more. New brands are added regularly.']],
        ['@type'=>'Question','name'=>'How do I buy a gift card in Bangladesh?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Browse our products, select your desired gift card and denomination, add it to cart, and proceed to checkout. Pay securely with bKash or Nagad — your code is delivered instantly to your email.']],
        ['@type'=>'Question','name'=>'Which payment methods do you accept?','acceptedAnswer'=>['@type'=>'Answer','text'=>'We accept bKash (tokenized checkout & send money), Nagad (send money), and Rocket (send money). All transactions are secure and encrypted.']],
        ['@type'=>'Question','name'=>'How quickly will I receive my gift card code?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Instantly! As soon as your payment is confirmed, your code is displayed on screen and emailed to your registered email address within minutes.']],
        ['@type'=>'Question','name'=>'Are the gift card codes genuine?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes. All gift card codes on Steam Store BD are 100% authentic and sourced directly. They are valid and ready to redeem immediately upon receipt.']],
        ['@type'=>'Question','name'=>'Can I buy multiple gift cards at once?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Yes, you can adjust quantity before checkout or add multiple products to your cart and pay in one transaction.']],
        ['@type'=>'Question','name'=>'I entered the wrong email address. What do I do?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Contact us immediately via the Contact page or WhatsApp with your order number and the correct email address. We will resend your codes as quickly as possible.']],
        ['@type'=>'Question','name'=>'My code does not work. What should I do?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Contact us with your order number and a screenshot of the error. We guarantee all our codes and will replace any defective code promptly.']],
        ['@type'=>'Question','name'=>'Do you offer refunds?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Since gift card codes are digital products delivered instantly, refunds are generally not available once a code is revealed. However, if a code is defective, we will replace it at no cost.']],
        ['@type'=>'Question','name'=>'Is Steam Store BD an official brand partner?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Steam Store BD is an independent reseller and is not officially affiliated with Valve Corporation, Google, Apple, or any other brand whose gift cards we sell. All brand names and logos are trademarks of their respective owners.']],
    ],
];
@endphp
<script type="application/ld+json">{!! json_encode($_faqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
<style>
.faq-item { border: 1.5px solid #E2EAF8; transition: border-color 0.2s, box-shadow 0.2s; }
.faq-item.is-open { border-color: #BFDBFE; box-shadow: 0 4px 20px rgba(37,99,235,0.10); }
</style>
@endpush

@section('content')

{{-- Page header --}}
<section style="background:linear-gradient(180deg,#040D1A 0%,#091525 100%); position:relative; overflow:hidden; padding:36px 0 40px;">
    <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 70% 50%,rgba(37,99,235,0.15) 0%,transparent 60%);pointer-events:none;"></div>
    <div class="grid-bg" style="position:absolute;inset:0;opacity:0.10;"></div>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-widest mb-4"
             style="background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);color:#7CB3F5;">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Help Centre
        </div>
        <h1 class="text-2xl md:text-3xl font-black text-white mb-2" style="letter-spacing:-0.02em;">Frequently Asked Questions</h1>
        <p class="text-sm" style="color:#557AA0;">Everything you need to know about buying gift cards at Steam Store BD</p>
    </div>
</section>

<div style="background:#F0F4FF; min-height:60vh; padding:40px 0 64px;">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    <div x-data="{ open: null }" class="space-y-3">
        @foreach([
            [
                'q' => 'What is Steam Store BD?',
                'a' => 'Steam Store BD is a trusted digital gift card marketplace in Bangladesh. We sell Steam, Google Play, App Store, and other popular gift cards with instant code delivery via email. Payment is accepted through bKash and Nagad.',
            ],
            [
                'q' => 'What gift cards do you sell?',
                'a' => 'We sell a wide range of digital gift cards including Steam Wallet gift cards, Google Play gift cards, Apple App Store gift cards, and more. New brands are added regularly — check our home page for the latest.',
            ],
            [
                'q' => 'How do I buy a gift card?',
                'a' => 'Browse our products, select your desired gift card and denomination, add it to cart, and proceed to checkout. Pay securely with bKash or Nagad — your code is delivered instantly to your email.',
            ],
            [
                'q' => 'Which payment methods do you accept?',
                'a' => 'We accept bKash (tokenized checkout & send money), Nagad (send money), and Rocket (send money). All transactions are secure and encrypted.',
            ],
            [
                'q' => 'How quickly will I receive my code?',
                'a' => 'Instantly! As soon as your payment is confirmed, your code is displayed on screen and emailed to your registered email address within minutes.',
            ],
            [
                'q' => 'Are the gift card codes genuine?',
                'a' => 'Yes. All codes on Steam Store BD are 100% authentic and sourced directly. They are valid and ready to redeem immediately upon receipt.',
            ],
            [
                'q' => 'Can I buy multiple gift cards at once?',
                'a' => 'Yes. You can adjust the quantity on the product page or add multiple different products to your cart and pay in a single transaction.',
            ],
            [
                'q' => 'I entered the wrong email address. What do I do?',
                'a' => 'Contact us immediately via the Contact page or WhatsApp with your order number and the correct email address. We will resend your codes as quickly as possible.',
            ],
            [
                'q' => 'My code does not work. What should I do?',
                'a' => 'Contact us with your order number and a screenshot of the error message. We guarantee all our codes and will replace any defective code promptly at no extra cost.',
            ],
            [
                'q' => 'Do you offer refunds?',
                'a' => 'Since gift card codes are digital products delivered instantly, refunds are generally not available once a code is revealed. However, if a code is defective, we will replace it immediately.',
            ],
            [
                'q' => 'Is Steam Store BD an official brand partner?',
                'a' => 'Steam Store BD is an independent reseller and is not officially affiliated with Valve Corporation, Google, Apple, or any other brand whose gift cards we sell. All brand names and logos are trademarks of their respective owners.',
            ],
        ] as $idx => $faq)
        <div class="faq-item bg-white rounded-2xl overflow-hidden" :class="open === {{ $idx }} ? 'is-open' : ''">
            <button @click="open = open === {{ $idx }} ? null : {{ $idx }}"
                    class="w-full flex items-center justify-between px-5 py-4 text-left group">
                <span class="font-semibold text-sm pr-4 group-hover:text-blue-600 transition-colors" style="color:#071428;">{{ $faq['q'] }}</span>
                <div class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center transition-colors"
                     :style="open === {{ $idx }} ? 'background:#2563EB;' : 'background:#EEF4FF;'">
                    <svg class="w-3.5 h-3.5 transition-transform duration-200"
                         :class="open === {{ $idx }} ? 'rotate-180' : ''"
                         :style="open === {{ $idx }} ? 'color:#fff;' : 'color:#2563EB;'"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>
            <div x-show="open === {{ $idx }}" x-cloak
                 class="px-5 pb-4 text-sm leading-relaxed text-gray-500"
                 style="border-top:1px solid #EEF4FF;">
                {{ $faq['a'] }}
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-10 text-center bg-white rounded-2xl p-8" style="border:1.5px solid #E2EAF8; box-shadow:0 2px 16px rgba(37,99,235,0.07);">
        <div class="w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-3"
             style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </div>
        <h3 class="font-black text-base mb-1" style="color:#071428;">Still have questions?</h3>
        <p class="text-gray-500 text-sm mb-4">Our support team is ready to help you.</p>
        <a href="{{ route('contact') }}"
           class="inline-flex items-center gap-2 px-7 py-2.5 rounded-xl font-semibold text-white text-sm transition-all hover:shadow-lg hover:opacity-90"
           style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
            Contact Support
        </a>
    </div>

</div>
</div>
@endsection
