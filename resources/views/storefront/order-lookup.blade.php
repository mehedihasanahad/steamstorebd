@extends('layouts.storefront')

@section('title', 'Track Your Order — Steam Store BD')
@section('robots', 'noindex, follow')
@section('meta_description', 'Look up a Steam Store BD order with your email and order number.')

@section('content')

<div class="mx-auto max-w-md px-4 py-section sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Track your order', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Track your order</h1>
    <p class="mt-1 text-caption text-ink-mid">Enter your e-mail and order number to see your order and its codes.</p>

    <form method="POST" action="{{ route('orders.lookup.submit') }}" class="mt-6 space-y-4 rounded-card border border-surface-3 bg-surface-1 p-5">
        @csrf

        <x-ui.input label="Email address" name="email" type="email" required
                    :value="old('email', auth()->user()?->email)"
                    placeholder="you@example.com"
                    :error="$errors->first('email')" />

        <x-ui.input label="Order number" name="order_number" required
                    :value="old('order_number')"
                    placeholder="BD2026-XXXXXX"
                    class="font-mono"
                    :error="$errors->first('order_number')" />

        <x-ui.button type="submit" size="lg" class="w-full">Find my order</x-ui.button>
    </form>
</div>

@endsection
