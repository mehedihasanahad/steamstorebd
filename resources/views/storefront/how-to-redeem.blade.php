@extends('layouts.storefront')

@section('title', 'How to Redeem Your Code — Steam Store BD')
@section('meta_description', 'Step-by-step guide to redeeming a gift card code, a game key, a top-up or a subscription bought at Steam Store BD. Works on PC, Mac and mobile.')

@php
    $steps = [
        ['Open Steam', 'Launch the Steam desktop app on Windows or Mac, or open store.steampowered.com in any browser.', null],
        ['Sign in to your account', 'Log in with the Steam account you want the funds to land in.', 'The funds go to whichever account is signed in — check before you redeem.'],
        ['Click your username', 'Top-right corner of the Steam window or website. Click it to open the dropdown.', null],
        ['Select "Account details"', 'This opens the page showing your current Steam Wallet balance.', null],
        ['Click "Add funds to your Steam Wallet"', 'It sits just below the balance.', null],
        ['Choose "Redeem a Steam Gift Card or Wallet Code"', 'Scroll past the card options to the bottom of the list. Do not pick a card method.', null],
        ['Enter your code', 'Fifteen characters, in the shape XXXXX-XXXXX-XXXXX. Paste it from your e-mail rather than typing it.', 'Copy and paste removes any chance of a typo.'],
        ['Click "Continue"', 'Confirm, and the full value is credited to your wallet straight away.', null],
    ];

    $issues = [
        ['"Code already redeemed"', 'The code has been activated somewhere. Contact support with your order number — we verify every code before sale and will sort it out.'],
        ['"Invalid code"', 'Check for typos and paste directly from the e-mail. Codes are not case-sensitive, but stray spaces do break them.'],
        ['"Not available in your region"', 'The code is for a different region than your account. Check the region on the product page, then message us and we will help you swap it.'],
        ['The e-mail never arrived', 'Check spam and junk first. Your code is also always on your order page under My Orders.'],
        ['The box will not take the code', 'Remove any leading or trailing space, and make sure you are in the redeem section rather than the payment field.'],
    ];
@endphp

@push('schema')
@php
$_howToSchema = [
    '@context'    => 'https://schema.org',
    '@type'       => 'HowTo',
    'name'        => 'How to redeem a Steam gift card code',
    'description' => 'Add your gift card value to a Steam Wallet in eight steps.',
    'step'        => collect($steps)->map(fn (array $step, int $i) => [
        '@type'    => 'HowToStep',
        'position' => $i + 1,
        'name'     => $step[0],
        'text'     => $step[1],
    ])->all(),
];
@endphp
<script type="application/ld+json">{!! json_encode($_howToSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'How to redeem', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">How to redeem your code</h1>
    <p class="mt-1 max-w-2xl text-body text-ink-mid">
        What to do once your order arrives — whichever of the four things you bought.
    </p>

    <div class="mt-4 flex flex-wrap gap-2">
        <x-ui.badge tone="success">Under 2 minutes</x-ui.badge>
        <x-ui.badge tone="accent">PC &middot; Mac &middot; Mobile</x-ui.badge>
    </div>

    <p class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-4 text-caption leading-relaxed text-ink-mid">
        <strong class="text-ink-hi">Where is my code?</strong>
        It is e-mailed the moment your payment clears, and it stays on your
        <a href="{{ route('orders.lookup') }}" class="text-accent-hover underline underline-offset-2">order page</a> for as long as you have an account.
    </p>

    {{-- Each vertical redeems somewhere different, so each gets its own answer
         rather than one Steam-shaped instruction for all four. --}}
    <section class="mt-6" aria-labelledby="by-type-heading">
        <h2 id="by-type-heading" class="text-lede font-bold text-ink-hi">By what you bought</h2>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
            @foreach([
                ['Gift cards', 'Redeem on the platform the card is for — its wallet, billing or "redeem a code" page. The detailed Steam walkthrough below applies to any Steam card.'],
                ['Game keys and software', 'Activate the key in the store or launcher it belongs to: Steam "Activate a Product", the publisher launcher, or the software vendor\'s account page.'],
                ['Game top-ups', 'Nothing to redeem. We credit the Player ID you gave at checkout, and your order page shows the progress until it is done.'],
                ['Subscriptions', 'We send the account details to your order page, masked until you reveal them. Sign in with those on the service and change the password if the plan allows it.'],
            ] as [$title, $body])
                <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                    <h3 class="text-body font-semibold text-ink-hi">{{ $title }}</h3>
                    <p class="mt-1.5 text-caption leading-relaxed text-ink-mid">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mt-8" aria-labelledby="steam-steps-heading">
        <h2 id="steam-steps-heading" class="text-lede font-bold text-ink-hi">Step by step: a Steam wallet code</h2>

        <ol class="mt-3 space-y-2">
            @foreach($steps as $index => [$title, $desc, $tip])
                <li class="flex gap-3 rounded-card border border-surface-3 bg-surface-1 p-4">
                    <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-accent/15 text-caption font-bold text-accent-hover">{{ $index + 1 }}</span>
                    <div class="min-w-0">
                        <p class="text-body font-semibold text-ink-hi">{{ $title }}</p>
                        <p class="mt-1 text-caption leading-relaxed text-ink-mid">{{ $desc }}</p>
                        @if($tip)
                            <p class="mt-2 rounded-control border border-accent/25 bg-accent/10 px-3 py-2 text-meta text-ink-mid">{{ $tip }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-8" aria-labelledby="issues-heading">
        <h2 id="issues-heading" class="text-lede font-bold text-ink-hi">Common problems</h2>

        <div x-data="{ open: null }" class="mt-3 space-y-2">
            @foreach($issues as $idx => [$question, $answer])
                <div class="rounded-card border bg-surface-1 transition-colors"
                     :class="open === {{ $idx }} ? 'border-accent/45' : 'border-surface-3'">
                    <h3>
                        <button type="button" @click="open = open === {{ $idx }} ? null : {{ $idx }}"
                                :aria-expanded="open === {{ $idx }} ? 'true' : 'false'"
                                class="flex w-full items-center justify-between gap-4 px-4 py-4 text-left">
                            <span class="text-body font-semibold text-ink-hi">{{ $question }}</span>
                            <svg class="h-4 w-4 flex-shrink-0 text-ink-low transition-transform" :class="open === {{ $idx }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </h3>
                    <div x-show="open === {{ $idx }}" x-cloak class="border-t border-surface-3 px-4 py-4 text-caption leading-relaxed text-ink-mid">
                        {{ $answer }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="mt-8 rounded-card border border-surface-3 bg-surface-1 p-6 text-center">
        <p class="text-body font-semibold text-ink-hi">Still stuck?</p>
        <p class="mt-1 text-caption text-ink-low">Send us the order number and we will take it from there.</p>
        <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
            <x-ui.button :href="route('contact')">Contact support</x-ui.button>
            <x-ui.button :href="route('home')" variant="secondary">Browse the catalog</x-ui.button>
        </div>
    </div>
</div>

@endsection
