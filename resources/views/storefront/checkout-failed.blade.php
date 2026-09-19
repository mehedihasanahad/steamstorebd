@extends('layouts.storefront')

@section('title', 'Payment Failed — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your payment could not be processed.')

@section('content')

<div class="mx-auto max-w-md px-4 py-section text-center sm:px-6 lg:px-8 lg:py-section-lg">

    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-2 border-danger bg-danger/10" aria-hidden="true">
        <svg class="h-8 w-8 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </span>

    <h1 class="mt-5 text-title md:text-display font-extrabold text-ink-hi">Payment failed</h1>
    <p class="mt-2 text-body text-ink-mid">Your payment did not go through, and everything in your cart has been released.</p>

    <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-5 text-left">
        <p class="text-caption font-semibold text-ink-hi">Why this happens</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-caption text-ink-low">
            <li>The payment was cancelled or timed out</li>
            <li>Not enough balance in the wallet</li>
            <li>A network error partway through</li>
        </ul>
    </div>

    <div class="mt-6 flex flex-col gap-2 sm:flex-row">
        <x-ui.button :href="route('checkout')" size="lg" class="flex-1">Try again</x-ui.button>
        <x-ui.button :href="route('home')" variant="secondary" size="lg" class="flex-1">Browse the catalog</x-ui.button>
    </div>

    <p class="mt-5 text-meta text-ink-low">Need help? <a href="{{ route('contact') }}" class="text-accent-hover hover:underline">Contact us</a></p>
</div>

@endsection
