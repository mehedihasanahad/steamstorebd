@extends('storefront.pages.layout')

@section('title', 'Refund & Replacement Policy — Steam Store BD')
@section('meta_description', 'How Steam Store BD handles refunds and replacements for digital gift card orders in Bangladesh: faulty codes, orders we cannot fulfil and duplicate payments.')
@section('heading', 'Refund & Replacement Policy')
@section('updated', '12 September 2026')

@section('page_content')
<h2>Digital codes are final once delivered</h2>
<p>Gift card codes are digital products that can be used the moment they are revealed. Because of this, an order can't be cancelled or refunded after its code has been delivered to you, except in the cases described below.</p>

<h2>If a code doesn't work</h2>
<p>If a code we delivered shows as invalid or already redeemed, <a href="{{ route('contact') }}">contact us</a> with:</p>
<ul>
    <li>your order number,</li>
    <li>a screenshot of the error message, and</li>
    <li>the account or platform where you tried to redeem it.</li>
</ul>
<p>Once we confirm the problem, we replace the code with a working code of the same value. If no replacement is available, we refund the amount you paid for that code.</p>

<h2>Orders we can't fulfil</h2>
<p>If you paid but we can't deliver your order, for example because the card went out of stock or the payment couldn't be verified, we cancel the order and refund the full amount to the payment method you used.</p>

<h2>Duplicate or mistaken payments</h2>
<p>If you were charged twice for the same order, or paid without an order being created, contact us with the transaction ID. After we match the payment to our records, we refund the extra amount.</p>

<h2>What isn't covered</h2>
<ul>
    <li>Codes redeemed on the wrong account.</li>
    <li>Buying the wrong brand, amount or region when the correct details were shown on the product page.</li>
    <li>Codes that stop working because the platform suspended or restricted your account.</li>
    <li>Referral wallet credit, which isn't cash and can only be taken out through the withdrawal option in your account.</li>
</ul>

<h2>How refunds are paid</h2>
<p>Refunds go back to the wallet or number you paid from. How long the money takes to arrive depends on the payment provider.</p>

<h2>Questions</h2>
<p>For anything not covered here, <a href="{{ route('contact') }}">contact us</a> and include your order number.</p>
@endsection
