<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Steam Store BD') : config('app.name', 'Steam Store BD') }}</title>
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="{{ config('storefront.theme_color') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/storefront.css'])
</head>
<body class="min-h-screen bg-surface-0 text-ink-mid font-sans antialiased">

    {{-- The disclaimer that stops this page being mistaken for a Steam login.
         It is the first thing on the page on purpose. --}}
    <p role="note" class="border-b border-surface-3 bg-surface-1 px-4 py-2.5 text-center text-caption leading-relaxed text-ink-mid">
        This is a Steam Store BD account, not a Steam login. We are an independent gift card store, not affiliated with
        Valve or Steam, and will never ask for your Steam password.
    </p>

    {{ $slot }}

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.11/dist/cdn.min.js"></script>
</body>
</html>
