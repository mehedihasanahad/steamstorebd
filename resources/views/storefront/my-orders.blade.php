@extends('layouts.storefront')

@section('title', 'My Orders — Steam Store BD')

@section('content')

{{-- Breadcrumb --}}
<div style="background:#071428; border-bottom:1px solid rgba(37,99,235,0.12);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-300 font-medium">My Orders</span>
        </nav>
    </div>
</div>

<div style="background:#040D1A; min-height:calc(100vh - 64px);">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">My Orders</h1>
            <p class="text-gray-400 text-sm mt-1">{{ $orders->count() }} {{ Str::plural('order', $orders->count()) }} found</p>
        </div>
        <a href="{{ route('home') }}"
           class="btn-brand px-5 py-2.5 rounded-xl text-sm font-semibold hidden sm:inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 6M17 13l1.4 6M9 21h.01M19 21h.01"/></svg>
            Shop More
        </a>
    </div>

    @if($orders->isEmpty())

    {{-- Empty state --}}
    <div class="rounded-2xl p-12 text-center" style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
        <div class="text-5xl mb-4">📦</div>
        <h2 class="text-lg font-bold text-white mb-2">No orders yet</h2>
        <p class="text-gray-400 text-sm mb-6">You haven't placed any orders. Browse our gift cards and get started!</p>
        <a href="{{ route('home') }}" class="btn-brand inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-semibold">
            Browse Gift Cards →
        </a>
    </div>

    @else

    {{-- Order list --}}
    <div class="space-y-4">
        @foreach($orders as $order)
        @php
        $statusColor = match($order->status) {
            'completed'                      => ['bg' => 'rgba(34,197,94,0.08)',  'border' => 'rgba(34,197,94,0.3)',  'text' => '#4ade80'],
            'paid'                           => ['bg' => 'rgba(37,99,235,0.08)',  'border' => 'rgba(37,99,235,0.3)',  'text' => '#60a5fa'],
            'pending', 'payment_initiated'   => ['bg' => 'rgba(234,179,8,0.08)', 'border' => 'rgba(234,179,8,0.3)',  'text' => '#facc15'],
            'failed', 'refunded'             => ['bg' => 'rgba(220,38,38,0.08)', 'border' => 'rgba(220,38,38,0.3)',  'text' => '#f87171'],
            default                          => ['bg' => 'rgba(37,99,235,0.05)', 'border' => 'rgba(37,99,235,0.15)', 'text' => '#94a3b8'],
        };
        $itemCount = $order->items->sum('quantity');
        @endphp

        <div class="rounded-2xl p-5 sm:p-6 transition-all hover:border-brand-500/40"
             style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                {{-- Icon --}}
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 text-xl"
                     style="background:linear-gradient(135deg,#071428,#0D2040); border:1px solid rgba(37,99,235,0.2);">🎮</div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="text-white font-bold text-sm font-mono">{{ $order->order_number }}</span>
                        <span class="text-xs font-medium px-2.5 py-0.5 rounded-full"
                              style="background:{{ $statusColor['bg'] }}; border:1px solid {{ $statusColor['border'] }}; color:{{ $statusColor['text'] }};">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                    <p class="text-gray-400 text-xs">
                        {{ $order->created_at->format('d M Y, h:i A') }}
                        · {{ $itemCount }} {{ Str::plural('item', $itemCount) }}
                        @if($order->items->isNotEmpty())
                        — {{ $order->items->map(fn($i) => $i->giftCard->name)->implode(', ') }}
                        @endif
                    </p>
                </div>

                {{-- Total + action --}}
                <div class="flex sm:flex-col items-center sm:items-end gap-3 sm:gap-1 flex-shrink-0">
                    <span class="text-brand-400 font-bold text-lg">৳ {{ number_format($order->total_bdt, 0) }}</span>
                    <a href="{{ route('orders.show', $order->order_number) }}"
                       class="text-xs font-semibold px-4 py-2 rounded-lg transition-colors whitespace-nowrap"
                       style="background:rgba(37,99,235,0.12); border:1px solid rgba(37,99,235,0.25); color:#60a5fa;"
                       onmouseover="this.style.background='rgba(37,99,235,0.2)'" onmouseout="this.style.background='rgba(37,99,235,0.12)'">
                        View Details →
                    </a>
                </div>

            </div>
        </div>
        @endforeach
    </div>

    @endif

</div>
</div>

@push('styles')
<style>body { background: #040D1A !important; }</style>
@endpush

@endsection
