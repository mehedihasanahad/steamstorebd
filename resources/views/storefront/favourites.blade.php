@extends('layouts.storefront')

@section('title', 'Favourites — Steam Store BD')
@section('robots', 'noindex, follow')
@section('meta_description', 'Products you saved at Steam Store BD.')

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Favourites', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Favourites</h1>
    <p class="mt-1 text-caption text-ink-low">{{ $products->count() }} saved {{ Str::plural('product', $products->count()) }}</p>

    @if($products->isEmpty())
        <div class="mt-6 rounded-card border border-surface-3 bg-surface-1 p-10 text-center">
            <p class="text-body font-semibold text-ink-hi">Nothing saved yet</p>
            <p class="mx-auto mt-1.5 max-w-sm text-caption text-ink-low">Tap “Add to favourite” on any product and it will wait for you here.</p>
            <x-ui.button :href="route('home')" size="sm" class="mt-5">Browse the catalog</x-ui.button>
        </div>
    @else
        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($products as $product)
                <x-catalog.product-card :product="$product" />
            @endforeach
        </div>
    @endif
</div>

@endsection
