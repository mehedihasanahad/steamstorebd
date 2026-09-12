@extends('storefront.pages.layout')

@php
    $_wallets = \App\Services\PaymentMethods::walletNames();
    $_payWith = $_wallets ? \Illuminate\Support\Arr::join($_wallets, ', ', ' or ') : 'local mobile wallets';
@endphp

@section('title', 'About Us — Steam Store BD')
@section('meta_description', 'Steam Store BD is an independent gift card store in Bangladesh. See how we deliver codes, replace faulty cards and support our customers.')
@section('heading', 'About Steam Store BD')

@section('page_content')
<h2>Who we are</h2>
<p>Steam Store BD is an independent online store in Bangladesh that sells digital gift cards and wallet codes for platforms such as Steam, Google Play and the App Store. We started it so gamers and app users in Bangladesh can buy these cards in taka, paying with the mobile wallets they already use, without needing an international credit card.</p>

<h2>How buying works</h2>
<ol>
    <li>Choose a brand, then pick the card amount you need.</li>
    <li>Pay with {{ $_payWith }}.</li>
    <li>Your code appears on screen, is emailed to you, and stays available under My Orders in your account.</li>
</ol>

<h2>What you can expect from us</h2>
<ul>
    <li><strong>Genuine codes.</strong> Every code is checked before it goes into our stock.</li>
    <li><strong>Prices in BDT.</strong> The price shown on the product page is the price you pay.</li>
    <li><strong>Help when a code fails.</strong> If a code we delivered doesn't work, we replace it. Our <a href="{{ route('refund-policy') }}">Refund &amp; Replacement Policy</a> explains how.</li>
    <li><strong>Real people on support.</strong> Questions go to our team, not a bot. See the <a href="{{ route('faq') }}">FAQ</a> or <a href="{{ route('contact') }}">contact us</a>.</li>
</ul>

<h2>An independent reseller</h2>
<p>Steam Store BD is not affiliated with, endorsed by or sponsored by Valve Corporation, Google LLC, Apple Inc. or any other company whose gift cards we sell. All brand names and logos belong to their respective owners.</p>

<h2>Get in touch</h2>
<p>
    Send us a message through the <a href="{{ route('contact') }}">contact page</a>@if(site_setting('contact_email')), email <a href="mailto:{{ site_setting('contact_email') }}">{{ site_setting('contact_email') }}</a>@endif @if(site_setting('contact_whatsapp'))or reach us on WhatsApp at {{ site_setting('contact_whatsapp') }}@endif.
</p>
@endsection
