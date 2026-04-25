@extends('layouts.storefront')

@section('title', 'Payment Failed — Steam Store BD')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">
    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-red-500/10 border-2 border-red-500 mb-6">
        <svg class="w-12 h-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </div>
    <h1 class="text-3xl font-bold text-white mb-3">Payment Failed</h1>
    <p class="text-gray-400 mb-8">Your payment could not be processed. Your cart items have been released.</p>

    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-5 mb-8 text-left space-y-2">
        <p class="text-gray-400 text-sm"><span class="text-white">Why did this happen?</span></p>
        <ul class="text-gray-500 text-sm space-y-1 list-disc list-inside">
            <li>Payment was cancelled or timed out</li>
            <li>Insufficient bKash balance</li>
            <li>Network error during payment</li>
        </ul>
    </div>

    <div class="flex flex-col sm:flex-row gap-4">
        <a href="{{ route('checkout') }}" class="flex-1 text-center font-bold py-4 rounded-xl text-white transition-all hover:shadow-lg" style="background-color: #E2136E;">
            Try Again with bKash
        </a>
        <a href="{{ route('shop') }}" class="flex-1 text-center bg-gray-800 hover:bg-gray-700 text-white font-semibold py-4 rounded-xl transition-colors">
            Browse Cards
        </a>
    </div>

    <p class="text-gray-500 text-xs mt-6">Need help? <a href="{{ route('contact') }}" class="text-brand-400 hover:underline">Contact us</a></p>
</div>
@endsection
