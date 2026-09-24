@extends('layouts.storefront')

@section('title', 'Email preferences')

@section('content')

<div class="mx-auto max-w-xl px-4 py-section lg:py-section-lg sm:px-6">

    <div class="rounded-card border border-surface-3 bg-surface-1 p-6 md:p-8">

        @if($justNow ?? false)
            <h1 class="text-title font-extrabold text-ink-hi">You are unsubscribed</h1>
            <p class="mt-3 text-body text-ink-mid">
                We will not send <span class="font-semibold text-ink-hi">{{ $email }}</span> any more
                offers or announcements.
            </p>
            <p class="mt-3 text-caption text-ink-low">
                Order confirmations, delivered codes and replies to your own messages are not affected —
                those are part of the orders you place and will keep arriving.
            </p>

        @elseif($alreadyOut)
            <h1 class="text-title font-extrabold text-ink-hi">Already unsubscribed</h1>
            <p class="mt-3 text-body text-ink-mid">
                <span class="font-semibold text-ink-hi">{{ $email }}</span> is not on our campaign list,
                so there is nothing to do.
            </p>

        @else
            <h1 class="text-title font-extrabold text-ink-hi">Unsubscribe</h1>
            <p class="mt-3 text-body text-ink-mid">
                Stop sending offers and announcements to
                <span class="font-semibold text-ink-hi">{{ $email }}</span>?
            </p>
            <p class="mt-3 text-caption text-ink-low">
                You will still get order confirmations and your codes — unsubscribing only covers
                marketing.
            </p>

            {{-- Posts back to this same signed URL, so the signature still covers it. --}}
            <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6">
                @csrf

                <label for="reason" class="block text-caption text-ink-mid">
                    If you have a moment — why? <span class="text-ink-low">(optional)</span>
                </label>
                <input id="reason" name="reason" type="text" maxlength="200"
                       class="mt-2 w-full rounded-control border border-surface-3 bg-surface-2 px-3 py-2 text-body text-ink-hi placeholder:text-ink-low focus:border-accent"
                       placeholder="Too many emails, not relevant, …">

                <button type="submit"
                        class="mt-4 w-full rounded-control bg-accent px-5 py-3 text-body font-bold text-ink-hi hover:bg-accent-hover sm:w-auto">
                    Unsubscribe me
                </button>
            </form>
        @endif

        <p class="mt-6 border-t border-surface-3 pt-5 text-caption text-ink-low">
            Changed your mind, or landed here by accident?
            <a href="{{ route('home') }}" class="text-accent-hover hover:underline">Back to the store</a>
        </p>
    </div>
</div>

@endsection
