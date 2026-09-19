@extends('layouts.storefront')

@section('title', 'Page Not Found — Steam Store BD')
@section('meta_description', 'The page you are looking for does not exist or has moved.')
@section('robots', 'noindex, follow')

@section('content')

<div class="mx-auto max-w-2xl px-4 py-section text-center sm:px-6 lg:px-8 lg:py-section-lg">
    <p class="text-meta font-bold uppercase tracking-widest text-accent-hover">Error 404</p>
    <h1 class="mt-3 text-title md:text-display font-extrabold text-ink-hi">This page doesn't exist</h1>
    <p class="mx-auto mt-2 max-w-md text-body text-ink-mid">
        The product or page you were after may have moved, or is no longer on sale.
    </p>

    <div class="mt-6 flex flex-col justify-center gap-2 sm:flex-row">
        <x-ui.button :href="route('home')" size="lg">Browse the catalog</x-ui.button>
        <x-ui.button :href="route('contact')" variant="secondary" size="lg">Contact support</x-ui.button>
    </div>
</div>

@if(($footerBrands ?? collect())->isNotEmpty())
    <section class="mx-auto max-w-shell px-4 pb-section sm:px-6 lg:px-8 lg:pb-section-lg" aria-labelledby="popular-brands-heading">
        <h2 id="popular-brands-heading" class="mb-3 text-center text-lede font-bold text-ink-hi">Popular brands</h2>
        <div class="flex flex-wrap justify-center gap-2">
            @foreach($footerBrands as $brand)
                <x-ui.chip :href="route('brand', $brand->slug)">{{ $brand->name }}</x-ui.chip>
            @endforeach
        </div>
    </section>
@endif

@endsection
