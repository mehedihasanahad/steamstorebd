@extends('layouts.storefront')

@section('title', ($currentCategory ? $currentCategory->name . ' — ' : '') . 'Shop Steam Gift Cards')
@section('meta_description', 'Browse all Steam gift cards available in Bangladesh. Buy with bKash.')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-400 mb-6 flex items-center gap-2">
        <a href="{{ route('home') }}" class="hover:text-brand-400">Home</a>
        <span>/</span>
        <a href="{{ route('shop') }}" class="hover:text-brand-400">Shop</a>
        @if($currentCategory)
        <span>/</span>
        <span class="text-white">{{ $currentCategory->name }}</span>
        @endif
    </nav>

    <div class="flex flex-col lg:flex-row gap-8">

        {{-- Sidebar --}}
        <aside class="w-full lg:w-64 flex-shrink-0">
            <div class="bg-gray-900 rounded-2xl p-5 border border-gray-700/50 sticky top-24">
                <h3 class="font-bold text-white mb-4">Categories</h3>
                <ul class="space-y-2 mb-6">
                    <li>
                        <a href="{{ route('shop') }}"
                           class="block text-sm py-1.5 px-3 rounded-lg transition-colors {{ !$currentCategory ? 'bg-brand-500/10 text-brand-400 font-medium' : 'text-gray-400 hover:text-brand-400' }}">
                            All Cards
                        </a>
                    </li>
                    @foreach($categories as $category)
                    <li>
                        <a href="{{ route('shop.category', $category->slug) }}"
                           class="block text-sm py-1.5 px-3 rounded-lg transition-colors {{ $currentCategory && $currentCategory->id === $category->id ? 'bg-brand-500/10 text-brand-400 font-medium' : 'text-gray-400 hover:text-brand-400' }}">
                            {{ $category->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>

                {{-- Sort --}}
                <form method="GET" action="{{ request()->url() }}">
                    <h3 class="font-bold text-white mb-3">Sort By</h3>
                    <select name="sort" onchange="this.form.submit()"
                            class="w-full bg-gray-800 border border-gray-600 text-gray-300 text-sm rounded-lg px-3 py-2 focus:border-brand-500 focus:outline-none">
                        <option value="sort_order" {{ $sort === 'sort_order' ? 'selected' : '' }}>Featured</option>
                        <option value="price_asc" {{ $sort === 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                        <option value="popular" {{ $sort === 'popular' ? 'selected' : '' }}>Most Popular</option>
                    </select>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-white">
                    {{ $currentCategory ? $currentCategory->name : 'All Steam Gift Cards' }}
                </h1>
                <span class="text-sm text-gray-400">Showing {{ $cards->count() }} of {{ $cards->total() }} cards</span>
            </div>

            @if($cards->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                @foreach($cards as $card)
                <a href="{{ route('card.detail', $card->slug) }}">
                    <x-gift-card-card :card="$card" />
                </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-10">
                {{ $cards->links() }}
            </div>
            @else
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="text-6xl mb-4">😔</div>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">No cards available right now</h3>
                <p class="text-gray-500 mb-6">We're constantly restocking. Check back soon!</p>
                <a href="{{ route('shop') }}" class="btn-steam font-semibold px-6 py-2.5 rounded-xl">View All Cards</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
