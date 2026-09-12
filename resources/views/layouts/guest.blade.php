<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Steam Store BD') : config('app.name', 'Steam Store BD') }}</title>
    <meta name="robots" content="noindex, follow">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/storefront.css'])
</head>
<body class="antialiased" style="background:#040D1A; min-height:100vh;">
    <div role="note" style="background:#071428;border-bottom:1px solid rgba(37,99,235,0.2);color:#9BB5D5;font-size:0.8125rem;line-height:1.5;text-align:center;padding:0.625rem 1rem;">
        This is a Steam Store BD account, not a Steam login. We are an independent gift card store, not affiliated with Valve or Steam, and will never ask for your Steam password.
    </div>
    {{ $slot }}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.11/dist/cdn.min.js"></script>
</body>
</html>
