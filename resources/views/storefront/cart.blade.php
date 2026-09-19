@extends('layouts.storefront')

@section('title', 'Cart — Steam Store BD')
@section('robots', 'noindex, follow')
@section('meta_description', 'Review your order before checkout.')

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 pb-28 sm:px-6 lg:px-8 lg:pb-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Cart', 'url' => null],
    ]" />

    @if($groups->isEmpty())
        <h1 class="text-title md:text-display font-extrabold text-ink-hi">Shopping cart</h1>

        <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-12 text-center">
            <p class="text-body font-semibold text-ink-hi">Your cart is empty</p>
            <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">Browse gift cards, top-ups, keys and subscriptions, and add what you need.</p>
            <x-ui.button :href="route('home')" class="mt-6">Browse the catalog</x-ui.button>
        </div>
    @else
        @php
            // The whole cart as one payload, so the panel and the lines share a
            // single source of truth instead of each re-deriving the total.
            $lines = $groups->flatten(1)->mapWithKeys(fn ($line) => [
                (string) $line['key'] => [
                    'id'       => $line['gift_card_id'],
                    'qty'      => $line['quantity'],
                    'price'    => $line['price'],
                    'saved'    => $line['gift_card']->discountAmount() ?? 0,
                    'min'      => $line['min'],
                    'max'      => max($line['min'], $line['max']),
                    'selected' => $line['selected'],
                ],
            ]);
        @endphp

        <div x-data="cartPage(@js($lines))">

            <div class="flex flex-wrap items-baseline justify-between gap-3">
                <h1 class="text-title md:text-display font-extrabold text-ink-hi">Shopping cart</h1>
                <p class="text-caption text-ink-mid"><span class="font-semibold text-ink-hi" x-text="selectedCount"></span> item(s) selected.</p>
            </div>

            <div class="mt-5 grid grid-cols-12 gap-5">

                {{-- ══ Lines, grouped by product ══ --}}
                <div class="col-span-12 lg:col-span-8 space-y-4">
                    @foreach($groups as $productName => $group)
                        <section class="rounded-card border border-surface-3 bg-surface-1" aria-labelledby="group-{{ $loop->index }}">
                            <h2 id="group-{{ $loop->index }}" class="border-b border-surface-3 px-4 py-3 text-body font-semibold text-ink-hi">{{ $productName }}</h2>

                            <ul class="divide-y divide-surface-3">
                                @foreach($group as $line)
                                    @php
                                        $key      = (string) $line['key'];
                                        $card     = $line['gift_card'];
                                        $discount = $card->discountPercent();
                                    @endphp
                                    {{-- One row on desktop — tick, art, name, unit
                                         price, stepper, line total, remove — and
                                         wrapping to two on a narrow screen. --}}
                                    <li class="flex flex-wrap items-center gap-x-3 gap-y-3 p-4">

                                        <input type="checkbox"
                                               @change="toggle(@js($key), $event.target.checked)"
                                               :checked="lines[@js($key)].selected"
                                               @checked($line['selected'])
                                               aria-label="Include {{ $card->name }} in this order"
                                               class="h-5 w-5 flex-shrink-0 cursor-pointer rounded-chip border-surface-3 bg-surface-2 text-accent focus:ring-2 focus:ring-accent/40">

                                        <x-catalog.artwork :image="$card->image ?: $card->category?->image" :name="$card->name"
                                                           ratio="aspect-square" class="w-12 flex-shrink-0 rounded-control" :width="48" :height="48" />

                                        <div class="min-w-0 flex-1 basis-48">
                                            <p class="text-caption font-semibold text-ink-hi">{{ $card->name }}</p>

                                            @if($discount)
                                                <p class="mt-0.5 text-meta font-semibold text-success">Discount: {{ $discount }}%</p>
                                            @endif

                                            @if(! empty($line['buyer_inputs']))
                                                <dl class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                                    @foreach($line['buyer_inputs'] as $field => $value)
                                                        <div class="flex gap-1 text-meta">
                                                            <dt class="text-ink-low">{{ Str::headline($field) }}:</dt>
                                                            <dd class="font-medium text-ink-mid">{{ $value }}</dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            @endif

                                            @unless($line['in_stock'])
                                                <p class="mt-1 text-meta font-semibold text-danger">
                                                    Only {{ $card->stock_count }} left — lower the quantity to check out.
                                                </p>
                                            @endunless
                                        </div>

                                        <div class="flex-shrink-0 text-right">
                                            <x-catalog.price :price="$card->price_bdt" :compare-at="$card->compare_at_price_bdt"
                                                             size="text-caption" class="flex-col items-end" />
                                            <span class="block text-meta text-ink-low">each</span>
                                        </div>

                                        <div class="flex flex-shrink-0 items-center gap-2">
                                            <div class="flex items-center rounded-control border border-surface-3 bg-surface-2">
                                                <button type="button" @click="dec(@js($key))"
                                                        :disabled="lines[@js($key)].qty <= lines[@js($key)].min || busy === @js($key)"
                                                        aria-label="Decrease quantity"
                                                        class="flex h-9 w-9 items-center justify-center text-body font-bold text-ink-mid transition-colors hover:text-ink-hi disabled:opacity-30 disabled:cursor-not-allowed">&minus;</button>
                                                <span class="w-9 border-x border-surface-3 py-1.5 text-center text-caption font-bold tabular-nums text-ink-hi"
                                                      x-text="lines[@js($key)].qty">{{ $line['quantity'] }}</span>
                                                <button type="button" @click="inc(@js($key))"
                                                        :disabled="lines[@js($key)].qty >= lines[@js($key)].max || busy === @js($key)"
                                                        aria-label="Increase quantity"
                                                        class="flex h-9 w-9 items-center justify-center text-body font-bold text-ink-mid transition-colors hover:text-ink-hi disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                                            </div>

                                            <svg x-show="busy === @js($key)" x-cloak class="h-4 w-4 animate-spin text-accent" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                            </svg>
                                        </div>

                                        <span class="w-24 flex-shrink-0 text-right text-body font-bold tabular-nums text-success"
                                              x-text="money(lines[@js($key)].qty * lines[@js($key)].price)">{{ format_bdt($line['price'] * $line['quantity']) }}</span>

                                        <form method="POST" action="{{ route('cart.remove', $key) }}" class="flex-shrink-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" aria-label="Remove {{ $card->name }}"
                                                    class="flex h-9 w-9 items-center justify-center rounded-control text-ink-low transition-colors hover:bg-surface-2 hover:text-danger">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach

                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-caption font-medium text-accent-hover transition-colors hover:underline">
                        &larr; Continue shopping
                    </a>
                </div>

                {{-- ══ Totals ══ --}}
                <div class="col-span-12 lg:col-span-4">
                    <div class="rounded-card border border-surface-3 bg-surface-1 p-5 lg:sticky lg:top-[120px]">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-body font-semibold text-ink-hi">Total</span>
                            <span class="text-title font-bold tabular-nums text-success" x-text="money(subtotal)">{{ format_bdt($subtotal) }}</span>
                        </div>

                        <div class="mt-4 flex items-baseline justify-between gap-3 border-t border-surface-3 pt-4" x-show="savings > 0" x-cloak>
                            <span class="text-caption text-ink-mid">You save</span>
                            <span class="text-caption font-semibold tabular-nums text-success" x-text="'- ' + money(savings)"></span>
                        </div>

                        <x-ui.button :href="route('checkout')" size="lg" class="mt-5 w-full" x-bind:class="selectedCount === 0 ? 'pointer-events-none opacity-40' : ''">
                            Checkout
                        </x-ui.button>

                        <p class="mt-3 text-center text-meta text-ink-low" x-show="selectedCount === 0" x-cloak>
                            Tick at least one item to check out.
                        </p>

                        <p class="mt-4 flex items-center justify-center gap-1.5 text-meta text-ink-low">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Secured checkout
                        </p>
                    </div>
                </div>
            </div>

            {{-- Mobile totals bar --}}
            <div class="fixed inset-x-0 bottom-0 z-40 border-t border-surface-3 bg-surface-1/97 px-4 py-3 backdrop-blur lg:hidden">
                <div class="flex items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-meta text-ink-low"><span x-text="selectedCount"></span> item(s) selected</p>
                        <p class="text-body font-bold tabular-nums text-success" x-text="money(subtotal)"></p>
                    </div>
                    <a href="{{ route('checkout') }}" x-bind:class="selectedCount === 0 ? 'pointer-events-none opacity-40' : ''"
                       class="flex h-11 flex-shrink-0 items-center rounded-control bg-accent px-6 text-body font-bold text-white">Checkout</a>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cartPage', (lines) => ({
        lines,
        busy: null,

        get selectedCount() {
            return Object.values(this.lines).filter(line => line.selected).length;
        },

        get subtotal() {
            return Object.values(this.lines)
                .filter(line => line.selected)
                .reduce((sum, line) => sum + line.qty * line.price, 0);
        },

        get savings() {
            return Object.values(this.lines)
                .filter(line => line.selected)
                .reduce((sum, line) => sum + line.qty * line.saved, 0);
        },

        money(amount) {
            return '৳ ' + Math.round(amount).toLocaleString('en-US');
        },

        inc(key) {
            const line = this.lines[key];
            if (line.qty < line.max) this.saveQuantity(key, line.qty + 1);
        },

        dec(key) {
            const line = this.lines[key];
            if (line.qty > line.min) this.saveQuantity(key, line.qty - 1);
        },

        // The panel updates once the server has accepted the change, so a
        // rejected quantity never leaves a total on screen that the order
        // would not honour.
        async saveQuantity(key, quantity) {
            if (this.busy !== null) return;
            this.busy = key;

            const response = await this.post('{{ route('cart.update-quantity') }}', {
                gift_card_id: this.lines[key].id,
                key,
                quantity,
            });

            if (response.ok) this.lines[key].qty = quantity;
            this.busy = null;
        },

        async toggle(key, selected) {
            const previous = this.lines[key].selected;
            this.lines[key].selected = selected;

            const response = await this.post('{{ route('cart.update-selection') }}', { key, selected });

            if (!response.ok) this.lines[key].selected = previous;
        },

        async post(url, body) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(body),
                });

                if (!res.ok) {
                    const payload = await res.json().catch(() => ({}));
                    if (payload.error) alert(payload.error);
                }

                return res;
            } catch (e) {
                return { ok: false };
            }
        },
    }));
});
</script>
@endpush

@endsection
