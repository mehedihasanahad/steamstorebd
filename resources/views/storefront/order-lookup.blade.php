@extends('layouts.storefront')

@section('title', 'Track Your Order — Steam Store BD')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-10">
        <div class="text-5xl mb-4">📦</div>
        <h1 class="text-3xl font-bold text-white mb-2">Track Your Order</h1>
        <p class="text-gray-400">Enter your email and order number to view your order details and gift card codes.</p>
    </div>

    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-6">
        <form method="POST" action="{{ route('orders.lookup.submit') }}">
            @csrf
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                           class="w-full bg-gray-800 border {{ $errors->has('order_number') ? 'border-red-500' : 'border-gray-600' }} text-white rounded-xl px-4 py-3 focus:border-brand-500 focus:outline-none"
                           placeholder="your@email.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Order Number</label>
                    <input type="text" name="order_number" value="{{ old('order_number') }}" required
                           class="w-full bg-gray-800 border {{ $errors->has('order_number') ? 'border-red-500' : 'border-gray-600' }} text-white rounded-xl px-4 py-3 focus:border-brand-500 focus:outline-none font-mono"
                           placeholder="BD2026-XXXXXX">
                    @error('order_number') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" class="w-full btn-steam font-bold py-3 rounded-xl transition-all hover:shadow-steam-glow">
                Find My Order
            </button>
        </form>
    </div>
</div>
@endsection
