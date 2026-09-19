@extends('layouts.storefront')

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => trim(View::yieldContent('heading')), 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">@yield('heading')</h1>
    @hasSection('updated')
        <p class="mt-1 text-caption text-ink-low">Last updated: @yield('updated')</p>
    @endif

    <article class="rich-content mt-6 rounded-card border border-surface-3 bg-surface-1 p-5 md:p-8">
        @yield('page_content')
    </article>
</div>

@endsection
