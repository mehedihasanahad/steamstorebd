@extends('layouts.storefront')

@section('title', 'Contact Us — Steam Store BD')
@section('meta_description', 'Contact Steam Store BD about an order, a code or a top-up. Send a message and get a reply within 5–10 minutes.')

@section('content')

<div class="mx-auto max-w-4xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Contact us', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Get in touch</h1>
    <p class="mt-1 max-w-2xl text-body text-ink-mid">A question about an order, a code or a top-up? Send it over and we will come straight back.</p>

    <div class="mt-4 flex flex-wrap gap-2">
        <x-ui.badge tone="success">Reply in 5–10 minutes</x-ui.badge>
        <x-ui.badge tone="accent">Every day</x-ui.badge>
    </div>

    <div class="mt-6 grid grid-cols-12 gap-5">

        {{-- The three things most messages turn out to be about, answered
             before the form so nobody has to wait for a reply they could
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

            @if(site_setting('contact_email') || site_setting('contact_whatsapp'))
                <div class="rounded-card border border-surface-3 bg-surface-1 p-4">
                    <p class="text-body font-semibold text-ink-hi">Reach us directly</p>
                    <ul class="mt-2 space-y-1 text-caption text-ink-mid">
                        @if(site_setting('contact_email'))
                            <li><a href="mailto:{{ site_setting('contact_email') }}" class="text-accent-hover hover:underline">{{ site_setting('contact_email') }}</a></li>
                        @endif
                        @if(site_setting('contact_whatsapp'))
                            <li>{{ site_setting('contact_whatsapp') }}</li>
                        @endif
                    </ul>
                </div>
            @endif
        </aside>

        <div class="col-span-12 lg:col-span-7">
            <form method="POST" action="{{ route('contact.submit') }}"
                  x-data="{ loading: false }" @submit="loading = true"
                  class="space-y-4 rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6">
                @csrf

                <div>
                    <h2 class="text-lede font-bold text-ink-hi">Send a message</h2>
                    <p class="mt-1 text-caption text-ink-low">Include your order number if you have one — it saves a round trip.</p>
                </div>

                <x-ui.input label="Your name" name="name" required
                            :value="old('name')" placeholder="e.g. Rahim Uddin"
                            :error="$errors->first('name')" />

                <x-ui.input label="Email address" name="email" type="email" required
                            :value="old('email')" placeholder="you@example.com"
                            :error="$errors->first('email')" />

                <div>
                    <label for="contact-message" class="mb-1.5 block text-caption font-medium text-ink-mid">
                        Your message <span class="text-danger">*</span>
                    </label>
                    <textarea id="contact-message" name="message" rows="6" required
                              placeholder="What happened, and what were you expecting?"
                              class="w-full resize-none rounded-control border bg-surface-2 px-3 py-2.5 text-body text-ink-hi placeholder:text-ink-low
                                     transition-colors focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30
                                     {{ $errors->has('message') ? 'border-danger' : 'border-surface-3' }}">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                </div>

                <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="loading">
                    <span x-show="!loading">Send message</span>
                    <span x-show="loading" x-cloak class="flex items-center gap-2">
                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Sending
                    </span>
                </x-ui.button>

                <p class="text-center text-meta text-ink-low">
                    By sending a message you agree to our
                    <a href="{{ route('faq') }}" class="underline underline-offset-2 hover:text-ink-mid">FAQ and policies</a>.
                </p>
            </form>
        </div>
    </div>
</div>

@endsection
