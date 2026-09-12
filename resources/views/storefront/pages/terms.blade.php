@extends('storefront.pages.layout')

@section('title', 'Terms of Service — Steam Store BD')
@section('meta_description', 'The terms that apply when you buy digital gift cards from Steam Store BD, including accounts, payments, delivery, referrals and refunds.')
@section('heading', 'Terms of Service')
@section('updated', '12 September 2026')

@section('page_content')
<p>These terms apply when you use this website or buy from Steam Store BD ("we", "us"). By creating an account or placing an order, you agree to them.</p>

<h2>1. Who we are</h2>
<p>Steam Store BD is an independent reseller of digital gift cards based in Bangladesh. We are not affiliated with Valve Corporation, Google LLC, Apple Inc. or any other brand whose cards we sell.</p>

<h2>2. Your account</h2>
<ul>
    <li>You need an account to check out. The information you give us must be accurate.</li>
    <li>You are responsible for keeping your password safe and for orders placed from your account.</li>
    <li>We may suspend accounts involved in fraud or misuse of the store.</li>
</ul>

<h2>3. Products</h2>
<ul>
    <li>We sell digital codes. Each code is also subject to the terms of the brand that issued it.</li>
    <li>Before you buy, check the brand, amount, currency and any region restrictions shown on the product page.</li>
    <li>Prices are in Bangladeshi Taka (BDT) and may change. You pay the price shown when you place your order.</li>
</ul>

<h2>4. Payment</h2>
<p>You can pay with the methods shown at checkout. Orders paid through bKash online checkout are confirmed automatically. Orders paid by send money are delivered after we verify the transaction ID you submit.</p>

<h2>5. Delivery</h2>
<p>Codes are shown on screen, emailed to the address on your order and kept under My Orders in your account. Please make sure your email address is correct. If a code is revealed to someone else because of an address you entered, we can't replace it.</p>

<h2>6. Refunds and replacements</h2>
<p>Refunds and replacements follow our <a href="{{ route('refund-policy') }}">Refund &amp; Replacement Policy</a>.</p>

<h2>7. Referral program and wallet</h2>
<ul>
    <li>Referral discounts and wallet rewards follow the rules shown in your account when the order is placed.</li>
    <li>We may change or end the referral program. Credit you have already earned stays in your wallet.</li>
    <li>Credit earned through fake accounts, self-referrals or other abuse may be removed.</li>
</ul>

<h2>8. Reseller program</h2>
<p>Wholesale pricing and other reseller benefits are available only to approved resellers, on the terms we agree with each reseller.</p>

<h2>9. What you may not do</h2>
<ul>
    <li>Pay with stolen accounts or payment methods, or with money you aren't authorised to use.</li>
    <li>Try to exploit pricing errors, bugs or security weaknesses.</li>
    <li>Scrape the site automatically or interfere with how it works.</li>
</ul>

<h2>10. Liability</h2>
<p>To the extent the law allows, our liability for any order is limited to the amount you paid for that order. We aren't responsible for problems caused by the platforms that issue the codes, such as outages or account suspensions.</p>

<h2>11. Governing law</h2>
<p>These terms are governed by the laws of Bangladesh.</p>

<h2>12. Changes</h2>
<p>We may update these terms. The version on this page, with the date at the top, is the one that applies.</p>

<h2>13. Contact</h2>
<p>Questions about these terms can be sent through the <a href="{{ route('contact') }}">contact page</a>.</p>
@endsection
