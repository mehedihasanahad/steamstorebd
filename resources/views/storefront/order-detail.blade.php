@extends('layouts.storefront')

@section('title', 'Order #' . $order->order_number . ' — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your order and your delivered items.')

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'My orders', 'url' => route('orders.lookup')],
        ['label' => '#' . $order->order_number, 'url' => null],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-title md:text-display font-extrabold text-ink-hi">Order #{{ $order->order_number }}</h1>
            <p class="mt-1 text-caption text-ink-low">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
        </div>

        @php
            $tone = match ($order->status) {
                'completed', 'paid'               => 'success',
                'processing', 'pending_review',
                'pending', 'payment_initiated'    => 'warning',
                'failed', 'refunded', 'cancelled' => 'danger',
                default                           => 'neutral',
            };
        @endphp
        <x-ui.badge :tone="$tone" class="text-caption">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</x-ui.badge>
    </div>

    @if($order->isPaid())
        @php
            $codeItems      = $order->items->filter(fn ($item) => $item->orderItemCodes->isNotEmpty());
            $deliveredItems = $order->items->filter(fn ($item) => $item->isFulfilled() && filled($item->delivered_payload));
            $awaitingItems  = $order->items->filter(fn ($item) => $item->needsFulfilment());
        @endphp

        {{-- 1 · Codes from the pool --}}
        @if($codeItems->isNotEmpty())
            <section class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="codes-heading">
                <h2 id="codes-heading" class="text-lede font-bold text-ink-hi">Your codes</h2>

                @foreach($codeItems as $item)
                    <div class="mt-4">
                        <p class="text-caption text-ink-low">{{ $item->deliveryLabel() }} — {{ $item->giftCard->name }} &times; {{ $item->quantity }}</p>

                        <ul class="mt-2 space-y-2">
                            @foreach($item->orderItemCodes as $itemCode)
                                <x-ui.copy-script />
                                <li x-data="copyable(@js($itemCode->giftCardCode->code))" class="flex items-center gap-2">
                                    <code class="flex-1 truncate rounded-control border border-surface-3 bg-surface-2 px-3 py-3 font-mono text-caption font-bold text-ink-hi">{{ $itemCode->giftCardCode->code }}</code>
                                    <x-ui.button variant="secondary" x-on:click="copy()">
                                        <span x-show="! copied && ! failed">Copy</span>
                                        <span x-show="copied" x-cloak>Copied</span>
                                        <span x-show="failed" x-cloak>Failed</span>
                                    </x-ui.button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- 2 · What an admin delivered by hand. Masked until asked for: these
             are account credentials, not a spent gift card code. --}}
        @if($deliveredItems->isNotEmpty())
            <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="delivered-heading">
                <h2 id="delivered-heading" class="text-lede font-bold text-ink-hi">Your account details</h2>

                @foreach($deliveredItems as $item)
                    <div x-data="{ revealed: false }" class="mt-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-caption text-ink-low">{{ $item->deliveryLabel() }} &times; {{ $item->quantity }}</p>
                            <div class="flex gap-2">
                                <x-ui.button variant="secondary" size="sm" x-on:click="revealed = !revealed">
                                    <span x-show="!revealed">Reveal</span>
                                    <span x-show="revealed" x-cloak>Hide</span>
                                </x-ui.button>
                                <x-ui.copy-script />
                                <span x-data="copyable(@js($item->delivered_payload))">
                                    <x-ui.button variant="secondary" size="sm" x-on:click="copy()">
                                        <span x-show="! copied && ! failed">Copy</span>
                                        <span x-show="copied" x-cloak>Copied</span>
                                        <span x-show="failed" x-cloak>Failed</span>
                                    </x-ui.button>
                                </span>
                            </div>
                        </div>

                        <pre x-show="revealed" x-cloak class="mt-2 whitespace-pre-wrap break-words rounded-control border border-surface-3 bg-surface-2 px-3 py-3 font-mono text-caption text-ink-hi">{{ $item->delivered_payload }}</pre>
                        <p x-show="!revealed" class="mt-2 rounded-control border border-surface-3 bg-surface-2 px-3 py-3 font-mono text-caption text-ink-low">••••••••••••••••</p>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- 3 · Still being handled --}}
        @if($awaitingItems->isNotEmpty())
            <section class="mt-4 rounded-card border border-warning/40 bg-surface-1 p-5" aria-labelledby="awaiting-heading">
                <h2 id="awaiting-heading" class="text-lede font-bold text-ink-hi">Being delivered</h2>
                <p class="mt-1 text-caption text-ink-mid">Paid and in our queue. We will e-mail you the moment it is done.</p>

                <ul class="mt-4 space-y-3">
                    @foreach($awaitingItems as $item)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-control border border-surface-3 bg-surface-2 p-3">
                            <div class="min-w-0">
                                <p class="text-caption font-semibold text-ink-hi">{{ $item->giftCard->name }} &times; {{ $item->quantity }}</p>
                                @if(! empty($item->buyer_inputs))
                                    <p class="mt-0.5 text-meta text-ink-low">
                                        @foreach($item->buyer_inputs as $field => $value){{ Str::headline($field) }}: {{ $value }}@if(! $loop->last) · @endif @endforeach
                                    </p>
                                @endif
                            </div>
                            <x-ui.badge tone="warning">{{ $item->giftCard->delivery_eta_label ?: 'In progress' }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>

                <x-ui.button :href="route('contact')" variant="secondary" size="sm" class="mt-4">Ask about this order</x-ui.button>
            </section>
        @endif
    @endif

    {{-- Order lines --}}
    <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="items-heading">
        <h2 id="items-heading" class="text-lede font-bold text-ink-hi">Order items</h2>

        <ul class="mt-3 divide-y divide-surface-3">
            @foreach($order->items as $item)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-caption font-medium text-ink-hi">{{ $item->giftCard->name }}</p>
                        <p class="text-meta text-ink-low">{{ format_bdt($item->unit_price_bdt) }} &times; {{ $item->quantity }}</p>
                    </div>
                    <span class="flex-shrink-0 text-caption font-semibold tabular-nums text-success">{{ format_bdt($item->subtotal_bdt) }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-3 flex items-center justify-between border-t border-surface-3 pt-3">
            <span class="text-body font-bold text-ink-hi">Total</span>
            <span class="text-lede font-bold tabular-nums text-success">{{ format_bdt($order->total_bdt) }}</span>
        </div>
    </section>

    {{-- Review --}}
    @if($order->isPaid())
        @php $alreadyReviewed = $order->reviews()->where('user_id', auth()->id())->exists(); @endphp

        <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="review-heading">
            @if($alreadyReviewed)
                <p class="flex items-center gap-2 text-caption font-semibold text-success">
                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                    Thank you — your review is in and waiting for approval.
                </p>
            @else
                <h2 id="review-heading" class="text-lede font-bold text-ink-hi">Leave a review</h2>
                <p class="mt-1 text-caption text-ink-low">Share how it went — it helps the next buyer decide.</p>

                <form action="{{ route('reviews.store', $order->order_number) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div x-data="{ rating: 5 }">
                        <span class="mb-2 block text-caption font-medium text-ink-mid">Your rating</span>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" @click="rating = {{ $i }}" aria-label="{{ $i }} star{{ $i > 1 ? 's' : '' }}"
                                        class="flex h-11 w-11 items-center justify-center">
                                    <svg class="h-6 w-6" :class="rating >= {{ $i }} ? 'text-warning' : 'text-surface-3'" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.29 3.97a1 1 0 00.95.69h4.17c.97 0 1.37 1.24.59 1.81l-3.38 2.45a1 1 0 00-.36 1.12l1.29 3.97c.3.92-.76 1.69-1.54 1.12l-3.37-2.45a1 1 0 00-1.18 0l-3.37 2.45c-.79.57-1.84-.2-1.54-1.12l1.29-3.97a1 1 0 00-.37-1.12L1.05 9.4c-.78-.57-.38-1.81.59-1.81h4.17a1 1 0 00.95-.69l1.29-3.97z"/>
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" :value="rating">
                    </div>

                    <div>
                        <label for="review-comment" class="mb-1.5 block text-caption font-medium text-ink-mid">Your review</label>
                        <textarea id="review-comment" name="comment" rows="3" required minlength="10" maxlength="1000"
                                  placeholder="How was it? Did the code arrive quickly?"
                                  class="w-full resize-none rounded-control border border-surface-3 bg-surface-2 px-3 py-2.5 text-body text-ink-hi placeholder:text-ink-low focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">{{ old('comment') }}</textarea>
                        @error('comment')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="review-shot" class="mb-1.5 block text-caption font-medium text-ink-mid">
                            Screenshot <span class="text-ink-low">(optional, max 5 MB)</span>
                        </label>
                        <input id="review-shot" type="file" name="screenshot" accept="image/*"
                               class="block w-full text-caption text-ink-mid file:mr-3 file:cursor-pointer file:rounded-control file:border-0 file:bg-surface-2 file:px-4 file:py-2 file:text-caption file:font-semibold file:text-ink-hi">
                        @error('screenshot')<p class="mt-1.5 text-meta text-danger">{{ $message }}</p>@enderror
                    </div>

                    <x-ui.button type="submit">Submit review</x-ui.button>
                </form>
            @endif
        </section>
    @endif
</div>

@endsection
