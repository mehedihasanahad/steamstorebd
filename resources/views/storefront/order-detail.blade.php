@extends('layouts.storefront')

@section('title', 'Order #' . $order->order_number . ' — Steam Store BD')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="{{ route('orders.lookup') }}" class="text-sm text-gray-400 hover:text-brand-400 flex items-center gap-1 mb-4">
            ← Back to My Orders
        </a>
        <h1 class="text-2xl font-bold text-white">Order #{{ $order->order_number }}</h1>
        <p class="text-gray-400 text-sm mt-1">Placed {{ $order->created_at->format('d M Y, H:i') }}</p>
    </div>

    {{-- Status badge --}}
    <div class="mb-6">
        @php
        $statusColor = match($order->status) {
            'completed' => 'green',
            'paid' => 'blue',
            'pending', 'payment_initiated' => 'yellow',
            'failed', 'refunded' => 'red',
            default => 'gray',
        };
        @endphp
        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium
            {{ $statusColor === 'green' ? 'bg-green-500/10 text-green-400 border border-green-500/30' : '' }}
            {{ $statusColor === 'blue' ? 'bg-blue-500/10 text-blue-400 border border-blue-500/30' : '' }}
            {{ $statusColor === 'yellow' ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/30' : '' }}
            {{ $statusColor === 'red' ? 'bg-red-500/10 text-red-400 border border-red-500/30' : '' }}
            {{ $statusColor === 'gray' ? 'bg-gray-500/10 text-gray-400 border border-gray-500/30' : '' }}">
            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
        </span>
    </div>

    {{-- Codes (only if paid/completed) --}}
    @if(in_array($order->status, ['paid', 'completed']))
    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-6 mb-6">
        <h2 class="font-bold text-white text-lg mb-4">Your Gift Card Codes</h2>
        @foreach($order->items as $item)
        <div class="mb-4">
            <p class="text-gray-400 text-sm mb-2">{{ $item->giftCard->name }} × {{ $item->quantity }}</p>
            @foreach($item->orderItemCodes as $itemCode)
            <div x-data="{ copied: false }" class="flex items-center gap-3 mb-2">
                <div class="flex-1 bg-gray-800 border border-gray-600 rounded-xl px-4 py-3 font-mono text-brand-400 font-bold text-sm">
                    {{ $itemCode->giftCardCode->code }}
                </div>
                <button @click="navigator.clipboard.writeText('{{ $itemCode->giftCardCode->code }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 btn-steam px-4 py-3 rounded-xl text-sm font-semibold">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak>✓</span>
                </button>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif

    {{-- Order items --}}
    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-6">
        <h2 class="font-bold text-white text-lg mb-4">Order Items</h2>
        @foreach($order->items as $item)
        <div class="flex items-center justify-between py-3 border-b border-gray-800 last:border-0">
            <div>
                <p class="text-white text-sm font-medium">{{ $item->giftCard->name }}</p>
                <p class="text-gray-400 text-xs">{{ format_bdt($item->unit_price_bdt) }} × {{ $item->quantity }}</p>
            </div>
            <span class="text-brand-400 font-semibold">{{ format_bdt($item->subtotal_bdt) }}</span>
        </div>
        @endforeach
        <div class="flex justify-between font-bold text-white pt-3 text-lg">
            <span>Total</span>
            <span class="text-brand-400">{{ format_bdt($order->total_bdt) }}</span>
        </div>
    </div>

    {{-- ── Review form (paid/completed orders, no review yet) ── --}}
    @if($order->isPaid())
    @php $alreadyReviewed = $order->reviews()->where('user_id', auth()->id())->exists(); @endphp
    <div class="mt-6 rounded-2xl border p-6
        {{ $alreadyReviewed ? 'border-green-500/20' : 'border-brand-500/20' }}"
         style="background:rgba(37,99,235,0.04);">

        @if($alreadyReviewed)
            <div class="flex items-center gap-3 text-green-400">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                <span class="font-semibold text-sm">Thank you! Your review has been submitted and is pending approval.</span>
            </div>
        @else
            <h3 class="font-bold text-white text-base mb-1">Leave a Review</h3>
            <p class="text-gray-400 text-xs mb-5">Share your experience — it helps other Bangladeshi gamers!</p>

            <form action="{{ route('reviews.store', $order->order_number) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                {{-- Star rating --}}
                <div x-data="{ rating: 5 }">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Your Rating</label>
                    <div class="flex gap-1.5">
                        @for($i = 1; $i <= 5; $i++)
                        <button type="button"
                                @click="rating = {{ $i }}"
                                class="text-2xl leading-none transition-transform hover:scale-110 focus:outline-none">
                            <span :class="rating >= {{ $i }} ? 'opacity-100' : 'opacity-25'">⭐</span>
                        </button>
                        @endfor
                    </div>
                    <input type="hidden" name="rating" :value="rating">
                </div>

                {{-- Comment --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Your Review</label>
                    <textarea name="comment" rows="3" required minlength="10" maxlength="1000"
                              placeholder="How was your experience? Did you receive your code quickly?"
                              class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-500 focus:outline-none focus:border-brand-500 resize-none">{{ old('comment') }}</textarea>
                    @error('comment') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Optional screenshot --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">
                        Screenshot (optional)
                        <span class="text-gray-600 font-normal normal-case ml-1">Steam redemption, chat proof, etc. Max 5 MB.</span>
                    </label>
                    <input type="file" name="screenshot" accept="image/*"
                           class="block w-full text-sm text-gray-400
                                  file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                  file:text-xs file:font-semibold file:text-white
                                  file:cursor-pointer"
                           style="file:background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                    @error('screenshot') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                        class="btn-brand px-6 py-2.5 rounded-xl text-sm font-semibold">
                    Submit Review
                </button>
            </form>
        @endif
    </div>
    @endif
</div>
@endsection
