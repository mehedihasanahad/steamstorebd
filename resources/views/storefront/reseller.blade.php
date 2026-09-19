@extends('layouts.storefront')

@section('title', 'Wholesale Gift Card Reseller Program — ' . site_setting('site_name', 'Steam Store BD'))
@section('meta_description', 'Join the ' . site_setting('site_name', 'Steam Store BD') . ' reseller program: wholesale gift card prices, priority delivery and bulk stock for sellers in Bangladesh.')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
<style>
    /* Tom Select, themed through the same tokens as the other inputs. Shared
       by the single-select (platform) and the multi-select (categories).

       The bare <select> is styled too, so the moment before the script boots
       still looks right instead of flashing a native control. The
       :not(.ts-hidden-accessible) guard drops those styles the instant Tom
       Select takes over — it keeps the original select in the DOM, visually
       hidden but still focusable, so the HTML5 required check keeps working. */
    select.rs-select:not(.ts-hidden-accessible) {
        width: 100%; min-height: 44px; padding: 10px 12px; font-size: 15px;
        color: rgb(var(--text-hi)); background: rgb(var(--surface-2));
        border: 1px solid rgb(var(--surface-3)); border-radius: 8px;
    }

    .ts-wrapper.rs-select { padding: 0; border: 0; background: none; min-height: 0; }
    .ts-wrapper.rs-select .ts-control {
        background: rgb(var(--surface-2)); border: 1px solid rgb(var(--surface-3)); border-radius: 8px;
        box-shadow: none; min-height: 44px; color: rgb(var(--text-hi)); font-size: 15px;
        transition: border-color .15s;
    }
    .ts-wrapper.rs-select.focus .ts-control { border-color: rgb(var(--accent)); box-shadow: none; }
    .ts-wrapper.rs-select.input-active .ts-control { background: rgb(var(--surface-2)); }
    .ts-wrapper.rs-select .ts-control > input { color: rgb(var(--text-hi)); font-size: 15px; margin: 0; }
    .ts-wrapper.rs-select .ts-control > input::placeholder { color: rgb(var(--text-low)); }

    .ts-wrapper.rs-select.single .ts-control { padding: 10px 12px; }
    .ts-wrapper.rs-select.single .ts-control > .item { color: rgb(var(--text-hi)); }
    .ts-wrapper.rs-select.single .ts-control:after { border-top-color: rgb(var(--text-low)); }
    .ts-wrapper.rs-select.single.dropdown-active .ts-control:after { border-bottom-color: rgb(var(--text-low)); }

    .ts-wrapper.rs-select.multi .ts-control { padding: 7px 10px; gap: 6px; }
    .ts-wrapper.rs-select.multi .ts-control > input { min-width: 8rem; }
    .ts-wrapper.rs-select.multi .ts-control > .item {
        background: rgb(var(--accent) / 0.18); border: 1px solid rgb(var(--accent) / 0.45);
        color: rgb(var(--text-hi)); border-radius: 6px; padding: 4px 8px 4px 10px;
        font-size: 13px; font-weight: 600; display: inline-flex; align-items: center;
    }
    .ts-wrapper.rs-select.plugin-remove_button .item .remove {
        border-left: 0; color: rgb(var(--text-mid)); padding: 0 4px 0 8px; font-size: 15px; line-height: 1;
    }
    .ts-wrapper.rs-select.plugin-remove_button .item .remove:hover { color: rgb(var(--text-hi)); background: none; }

    .ts-wrapper.rs-select .ts-dropdown {
        background: rgb(var(--surface-1)); border: 1px solid rgb(var(--surface-3));
        border-radius: 10px; margin-top: 6px; box-shadow: var(--shadow-hover);
        overflow: hidden;
    }
    .ts-wrapper.rs-select .ts-dropdown .option { color: rgb(var(--text-mid)); font-size: 15px; padding: 10px 12px; }
    .ts-wrapper.rs-select .ts-dropdown .option.active { background: rgb(var(--accent) / 0.2); color: rgb(var(--text-hi)); }
    .ts-wrapper.rs-select .ts-dropdown .option.selected { color: rgb(var(--text-hi)); }
    .ts-wrapper.rs-select .ts-dropdown .no-results { color: rgb(var(--text-low)); font-size: 15px; padding: 10px 12px; }
</style>
@endpush

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Leave the native selects in place if the CDN script did not load —
        // they still submit selling_platform and gift_card_types[] on their own.
        if (typeof TomSelect === 'undefined') {
            return;
        }

        var noResults = function (label) {
            return function () {
                return '<div class="no-results">No ' + label + ' found.</div>';
            };
        };

        var platform = document.getElementById('rs-platform');

        if (platform) {
            new TomSelect(platform, {
                maxItems: 1,
                // The empty prompt option exists for the no-JS fallback; keep it
                // out of the dropdown and show it as placeholder text instead.
                allowEmptyOption: false,
                placeholder: 'Search or select where you sell…',
                render: { no_results: noResults('platform') },
            });
        }

        var types = document.getElementById('rs-types');

        if (types) {
            new TomSelect(types, {
                plugins: ['remove_button'],
                maxItems: null,
                hideSelected: true,
                closeAfterSelect: false,
                placeholder: 'Search and select categories…',
                render: { no_results: noResults('category') },
            });
        }
    });
</script>
@endpush

@section('content')

@php
    $submittedNumber = session('reseller_application_number');
    $platforms       = $program->platformOptions();
    $brandOptions    = $program->giftCardTypeOptions();
    $benefits        = $program->benefits();
    $oldTypes        = array_values((array) old('gift_card_types', []));
@endphp

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Become a reseller', 'url' => null],
    ]" />

    {{-- ══ Hero ══ --}}
    <section class="rounded-card border border-surface-3 bg-surface-1 p-5 md:p-8" aria-labelledby="reseller-hero-heading">
        <div class="flex flex-wrap gap-2">
            <x-ui.badge tone="accent">Reseller programme</x-ui.badge>
            <x-ui.badge tone="success">Reply {{ $program->responseTime() }}</x-ui.badge>
            <x-ui.badge>Wholesale pricing</x-ui.badge>
        </div>

        <h1 id="reseller-hero-heading" class="mt-4 max-w-3xl text-title md:text-display font-extrabold text-ink-hi">
            {{ $program->heroTitle() }}
        </h1>
        <p class="mt-2 max-w-2xl text-body leading-relaxed text-ink-mid">
            {{ $program->heroSubtitle() }}
        </p>

        <x-ui.button href="#apply" size="lg" class="mt-5">Apply now — it's free</x-ui.button>

        <dl class="mt-6 flex flex-wrap gap-8 border-t border-surface-3 pt-6">
            @foreach([['No fee', 'To join the programme'], ['24/7', 'Stock availability'], ['100%', 'Genuine codes']] as [$value, $label])
                <div>
                    <dt class="sr-only">{{ $label }}</dt>
                    <dd>
                        <span class="block text-title font-extrabold text-ink-hi">{{ $value }}</span>
                        <span class="mt-0.5 block text-meta text-ink-low">{{ $label }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- ══ Benefits ══ --}}
    <section class="mt-section md:mt-section-lg" aria-labelledby="benefits-heading">
        <h2 id="benefits-heading" class="text-lede md:text-title font-bold text-ink-hi">Built for people who sell</h2>
        <p class="mt-1 max-w-2xl text-body text-ink-mid">Everything you need to serve your own customers faster, at a better margin.</p>

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($benefits as $benefit)
                <div class="rounded-card border border-surface-3 bg-surface-1 p-5">
                    <span class="text-title leading-none" aria-hidden="true">{{ $benefit['icon'] }}</span>
                    <h3 class="mt-3 text-body font-semibold text-ink-hi">{{ $benefit['title'] }}</h3>
                    @if($benefit['description'] !== '')
                        <p class="mt-1.5 text-caption leading-relaxed text-ink-mid">{{ $benefit['description'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══ How it works ══ --}}
    <section class="mt-section md:mt-section-lg" aria-labelledby="steps-heading">
        <h2 id="steps-heading" class="text-lede md:text-title font-bold text-ink-hi">Three steps to get started</h2>
        <p class="mt-1 max-w-2xl text-body text-ink-mid">No paperwork, no joining fee. Just a short conversation to check we are a good fit.</p>

        <ol class="mt-4 grid gap-3 md:grid-cols-3">
            @foreach([
                ['Submit your application', 'Fill in the short form below. It takes under two minutes and needs no documents.'],
                ['We verify you', 'Our team reviews your details and messages you on WhatsApp to confirm your business.'],
                ['Start selling', 'Once approved you get your reseller price list and can place your first bulk order.'],
            ] as $index => [$title, $desc])
                <li class="rounded-card border border-surface-3 bg-surface-1 p-5">
                    <span class="text-meta font-bold text-accent-hover">Step {{ $index + 1 }}</span>
                    <p class="mt-1.5 text-body font-semibold text-ink-hi">{{ $title }}</p>
                    <p class="mt-1 text-caption leading-relaxed text-ink-mid">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- ══ Application ══ --}}
    <section id="apply" class="mt-section md:mt-section-lg scroll-mt-[120px]" aria-labelledby="apply-heading">

        @if($submittedNumber)
            <div class="mx-auto max-w-2xl rounded-card border border-success/40 bg-surface-1 p-6 text-center md:p-8">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-2 border-success bg-success/10" aria-hidden="true">
                    <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </span>

                <h2 id="apply-heading" class="mt-5 text-title font-extrabold text-ink-hi">Application received</h2>
                <p class="mx-auto mt-2 max-w-lg text-body leading-relaxed text-ink-mid">
                    Thanks for applying. A confirmation is on its way to your e-mail, and our team will come back to you
                    <strong class="text-success">{{ $program->responseTime() }}</strong>.
                </p>

                <div class="mx-auto mt-5 inline-flex flex-col items-center gap-1 rounded-card border border-surface-3 bg-surface-2 px-6 py-4">
                    <span class="text-meta font-semibold uppercase tracking-widest text-ink-low">Your application ID</span>
                    <span class="font-mono text-lede font-bold tracking-widest text-ink-hi">{{ $submittedNumber }}</span>
                </div>

                <ol class="mt-6 space-y-2 text-left">
                    @foreach([
                        'Our team reviews your application details',
                        'We message you on WhatsApp to verify your business',
                        'Once approved, you receive your reseller price list by e-mail',
                    ] as $i => $next)
                        <li class="flex items-start gap-3 rounded-control border border-surface-3 bg-surface-2 p-3 text-caption text-ink-mid">
                            <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-chip bg-accent/15 text-meta font-bold text-accent-hover">{{ $i + 1 }}</span>
                            {{ $next }}
                        </li>
                    @endforeach
                </ol>

                <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
                    <x-ui.button :href="route('home')">Continue shopping</x-ui.button>
                    <x-ui.button :href="route('contact')" variant="secondary">Contact support</x-ui.button>
                </div>
            </div>
        @else
            <div class="mx-auto max-w-2xl">
                <h2 id="apply-heading" class="text-lede md:text-title font-bold text-ink-hi">Apply To Become a Reseller</h2>
                <p class="mt-1 text-body text-ink-mid">Takes under two minutes. We reply {{ $program->responseTime() }}.</p>

                @if($errors->any())
                    <div class="mt-4 rounded-card border border-danger/40 bg-danger/10 p-4" role="alert">
                        <p class="text-caption font-bold text-danger">Please check the form</p>
                        <p class="mt-0.5 text-caption text-danger">
                            {{ $errors->count() === 1 ? 'One field needs your attention.' : $errors->count() . ' fields need your attention.' }}
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('reseller.submit') }}" x-data="{ loading: false }" @submit="loading = true"
                      class="mt-5 space-y-5 rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6">
                    @csrf

                    <x-ui.input label="Your Name" name="name" id="rs-name" required maxlength="100"
                                :value="old('name')" placeholder="e.g. Rahim Uddin"
                                :error="$errors->first('name')" />

                    <x-ui.input label="Email Address" name="email" id="rs-email" type="email" required maxlength="150"
                                :value="old('email')" placeholder="you@example.com"
                                hint="We send your approval and price list here."
                                :error="$errors->first('email')" />

                    <div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-ui.input label="Phone Number" name="phone" id="rs-phone" type="tel" required maxlength="20"
                                        inputmode="tel" :value="old('phone')" placeholder="01712345678"
                                        :error="$errors->first('phone')" />

                            <x-ui.input label="WhatsApp Number" name="whatsapp_number" id="rs-whatsapp" type="tel" required maxlength="20"
                                        inputmode="tel" :value="old('whatsapp_number')" placeholder="01712345678"
                                        :error="$errors->first('whatsapp_number')" />
                        </div>
                        <p class="mt-2 text-meta text-ink-low">Bangladeshi mobile numbers only. We verify every applicant on WhatsApp.</p>
                    </div>

                    <div>
                        <label for="rs-platform" class="mb-1.5 block text-caption font-medium text-ink-mid">
                            Where Do You Sell? <span class="text-danger">*</span>
                        </label>
                        <select id="rs-platform" name="selling_platform" required autocomplete="off" class="rs-select">
                            <option value="" disabled {{ old('selling_platform') ? '' : 'selected' }}>Select your selling platform</option>
                            @foreach($platforms as $value => $label)
                                <option value="{{ $value }}" @selected(old('selling_platform') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-meta text-ink-low">Type to search, or pick from the list.</p>
                        @error('selling_platform')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="rs-types" class="mb-1.5 block text-caption font-medium text-ink-mid">
                            Which Gift Cards Do You Want To Sell? <span class="text-danger">*</span>
                        </label>
                        <select id="rs-types" name="gift_card_types[]" multiple required autocomplete="off"
                                placeholder="Search and select categories…" class="rs-select">
                            @foreach($brandOptions as $slug => $label)
                                <option value="{{ $slug }}" @selected(in_array($slug, $oldTypes, true))>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-meta text-ink-low">Type to search. Pick every category you plan to stock — you can change this later.</p>
                        @error('gift_card_types')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                        @error('gift_card_types.*')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                    </div>

                    <x-ui.button type="submit" size="lg" class="w-full" x-bind:disabled="loading">
                        <span x-show="!loading">Submit Application</span>
                        <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            Submitting
                        </span>
                    </x-ui.button>

                    <p class="text-center text-meta text-ink-low">
                        Free to apply. We never share your details — see our
                        <a href="{{ route('faq') }}" class="underline underline-offset-2 hover:text-ink-mid">FAQ &amp; policies</a>.
                    </p>
                </form>
            </div>
        @endif
    </section>

    {{-- ══ Reseller FAQ ══ --}}
    <section class="mt-section md:mt-section-lg" aria-labelledby="reseller-faq-heading">
        <h2 id="reseller-faq-heading" class="text-lede md:text-title font-bold text-ink-hi">Reseller FAQ</h2>

        <div x-data="{ open: null }" class="mt-4 space-y-2">
            @foreach([
                ['Is there any fee to join?', 'No. Applying and joining the reseller programme is free. You only pay for the gift cards you order.'],
                ['How long does approval take?', 'We review every application by hand and reply ' . $program->responseTime() . '. We contact you on WhatsApp to verify your business before approving.'],
                ['Do I need a trade licence?', 'No trade licence or paperwork is required. We just need to confirm you are a genuine seller — a Facebook page, shop or gaming zone with real selling history is enough.'],
                ['What is the minimum order?', 'Reseller pricing starts at bulk quantities. We share the exact tiers and minimums once your application is approved.'],
                ['How do I pay for reseller orders?', 'The same way as regular orders — bKash, Nagad or Rocket. Approved resellers get priority verification so codes arrive faster.'],
                ['What if my application is declined?', 'We e-mail you the reason. You are welcome to fix it and apply again, and you can keep buying as a regular customer in the meantime.'],
            ] as $i => [$question, $answer])
                <div class="rounded-card border bg-surface-1 transition-colors"
                     :class="open === {{ $i }} ? 'border-accent/45' : 'border-surface-3'">
                    <h3>
                        <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}"
                                :aria-expanded="open === {{ $i }} ? 'true' : 'false'"
                                class="flex w-full items-center justify-between gap-4 px-4 py-4 text-left">
                            <span class="text-body font-semibold text-ink-hi">{{ $question }}</span>
                            <svg class="h-4 w-4 flex-shrink-0 text-ink-low transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </h3>
                    <div x-show="open === {{ $i }}" x-cloak class="border-t border-surface-3 px-4 py-4 text-caption leading-relaxed text-ink-mid">
                        {{ $answer }}
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>

@endsection
