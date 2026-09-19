@props([
    'heading',
    'subheading' => null,
])

{{--
    The chrome every auth page shares: a marketing panel on the left that
    collapses away on a phone, and the form on the right. Four pages used to
    carry their own copy of this; now they carry only their form.
--}}
<div class="flex min-h-[calc(100vh-44px)] items-stretch">

    <aside class="hidden w-1/2 flex-col justify-center gap-8 border-r border-surface-3 bg-surface-1 p-12 lg:flex" aria-label="About Steam Store BD">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/logo.svg') }}" alt="" width="44" height="44" class="h-11 w-11 rounded-card" aria-hidden="true">
            <span>
                <span class="block text-lede font-extrabold tracking-tight text-ink-hi">Steam Store BD</span>
                <span class="block text-caption text-ink-low">Independent digital goods store</span>
            </span>
        </a>

        <div>
            <h2 class="max-w-md text-display font-extrabold leading-tight text-ink-hi">
                Gift cards, top-ups and keys — bought in Bangladesh
            </h2>
            <p class="mt-3 max-w-md text-body leading-relaxed text-ink-mid">
                Pay with bKash and get your code by e-mail. Sign in to see your orders, your codes and your wallet.
            </p>
        </div>

        <ul class="flex max-w-md flex-col gap-3">
            @foreach([
                'Instant code delivery to your inbox',
                'Secured by bKash Tokenized Checkout',
                'Support that answers, every day',
            ] as $point)
                <li class="flex items-center gap-3 text-caption text-ink-mid">
                    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-control bg-accent/15" aria-hidden="true">
                        <svg class="h-4 w-4 text-accent-hover" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </span>
                    {{ $point }}
                </li>
            @endforeach
        </ul>
    </aside>

    <main class="flex w-full items-center justify-center p-6 lg:w-1/2">
        <div class="w-full max-w-sm">

            <a href="{{ url('/') }}" class="mb-8 flex items-center justify-center gap-2.5 lg:hidden">
                <img src="{{ asset('images/logo.svg') }}" alt="" width="36" height="36" class="h-9 w-9 rounded-control" aria-hidden="true">
                <span class="text-lede font-extrabold text-ink-hi">Steam Store BD</span>
            </a>

            <h1 class="text-title font-extrabold text-ink-hi">{{ $heading }}</h1>
            @if($subheading)
                <p class="mt-1 text-caption text-ink-low">{{ $subheading }}</p>
            @endif

            @if(session('status'))
                <p class="mt-5 rounded-control border border-success/30 bg-success/10 px-4 py-3 text-caption text-success" role="status">{{ session('status') }}</p>
            @endif

            @if(session('error'))
                <p class="mt-5 rounded-control border border-danger/40 bg-danger/10 px-4 py-3 text-caption text-danger" role="alert">{{ session('error') }}</p>
            @endif

            <div class="mt-6">{{ $slot }}</div>

            <a href="{{ url('/') }}"
               class="mt-6 flex min-h-[44px] items-center justify-center gap-2 rounded-control border border-surface-3 bg-surface-1 px-4 text-caption font-medium text-ink-mid transition-colors hover:text-ink-hi">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to the store
            </a>
        </div>
    </main>
</div>
