@extends('storefront.pages.layout')

@section('title', 'Privacy Policy — Steam Store BD')
@section('meta_description', 'What personal information Steam Store BD collects when you buy gift cards, how we use it, who we share it with and the choices you have.')
@section('heading', 'Privacy Policy')
@section('updated', '12 September 2026')

@section('page_content')
<p>This policy explains what information Steam Store BD ("we", "us") collects when you use this website, how we use it and the choices you have.</p>

<h2>Information we collect</h2>
<ul>
    <li><strong>Account details:</strong> your name, email address, password (stored encrypted) and phone number if you add one. If you sign in with Google, we receive your name, email address and Google account ID.</li>
    <li><strong>Order details:</strong> the cards you buy, amounts, the email address used for delivery, your payment method and, for send-money payments, the transaction ID you submit.</li>
    <li><strong>Messages and applications:</strong> what you send through the contact form or the reseller application, together with the IP address it was sent from.</li>
    <li><strong>Usage data:</strong> your IP address, browser type and the pages you visit, collected through cookies and Google Analytics.</li>
</ul>

<h2>How we use it</h2>
<ul>
    <li>To process your orders and deliver your codes.</li>
    <li>To send order confirmations and code delivery emails.</li>
    <li>To answer support requests.</li>
    <li>To run the referral program and your wallet balance.</li>
    <li>To detect fraud and misuse of the store.</li>
    <li>To understand how the site is used and improve it.</li>
</ul>

<h2>Payments</h2>
<p>Online payments are processed by the payment provider you choose, such as bKash. We never receive or store your wallet PIN or card details.</p>

<h2>Who we share it with</h2>
<p>We don't sell your personal information. We share it only with:</p>
<ul>
    <li>payment providers, to complete your payment,</li>
    <li>our email delivery and hosting providers, to run the store,</li>
    <li>Google Analytics, for site statistics, and</li>
    <li>authorities, when the law of Bangladesh requires it.</li>
</ul>

<h2>Cookies</h2>
<p>We use cookies to keep you signed in and remember your cart. Google Analytics also sets cookies to measure visits. You can block or delete cookies in your browser settings, but signing in and checkout won't work without them.</p>

<h2>How long we keep it</h2>
<p>We keep order records for as long as we need them for accounting, fraud prevention and resolving disputes. You can delete your account at any time from your profile page. Order records linked to it may be kept for those purposes.</p>

<h2>Security</h2>
<p>The site is served over encrypted connections and access to customer data is limited to staff who need it. No system is completely secure, so please use a strong, unique password for your account.</p>

<h2>Your choices</h2>
<ul>
    <li>Update your name, email and phone number from your profile page.</li>
    <li>Delete your account from your profile page.</li>
    <li><a href="{{ route('contact') }}">Contact us</a> to ask what information we hold about you.</li>
</ul>

<h2>Children</h2>
<p>If you are under 18, please use this store with a parent's or guardian's permission.</p>

<h2>Changes to this policy</h2>
<p>If we change this policy, we'll update it on this page and change the date at the top.</p>

<h2>Contact</h2>
<p>
    Questions about your privacy can be sent through the <a href="{{ route('contact') }}">contact page</a>@if(site_setting('contact_email')) or to <a href="mailto:{{ site_setting('contact_email') }}">{{ site_setting('contact_email') }}</a>@endif.
</p>
@endsection
