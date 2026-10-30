<x-guest-layout title="Verify Email">
    @php
        $email   = auth()->user()?->email;
        $minutes = (int) config('auth.verification.expire', 60);
        $expiry  = $minutes % 60 === 0
            ? ($minutes / 60) . ' hour' . ($minutes > 60 ? 's' : '')
            : $minutes . ' minutes';
    @endphp

    <x-auth-shell heading="Check your inbox"
                  subheading="One click confirms your address and your account is ready.">

        <p class="text-caption leading-relaxed text-ink-mid">
            We have e-mailed a confirmation link to
            @if($email)
                <strong class="font-semibold text-ink-hi">{{ $email }}</strong>.
            @else
                the address you registered with.
            @endif
            Open it and press <strong class="font-semibold text-ink-hi">Confirm my email address</strong>.
        </p>

        {{-- Three steps rather than a paragraph: the whole job is to make the
             next action unmissable to someone who is half-reading. --}}
        <ol class="mt-5 space-y-3">
            @foreach([
                ['Open your inbox', 'The message is from ' . site_setting('site_name', 'Steam Store BD') . ', subject "Confirm your email".'],
                ['Press the button', 'That confirms the address straight away — nothing to type in.'],
                ['Come back and shop', 'Your cart is still here, and checkout opens the moment you are confirmed.'],
            ] as $index => [$step, $detail])
                <li class="flex gap-3">
                    <span class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-accent/15 text-meta font-bold text-accent-hover" aria-hidden="true">{{ $index + 1 }}</span>
                    <span>
                        <span class="block text-caption font-semibold text-ink-hi">{{ $step }}</span>
                        <span class="block text-meta leading-relaxed text-ink-low">{{ $detail }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        <div class="mt-5 rounded-control border border-surface-3 bg-surface-2 px-4 py-3">
            <p class="text-meta leading-relaxed text-ink-mid">
                <strong class="font-semibold text-ink-hi">Not there?</strong>
                It can take a minute to arrive. Check your spam or promotions folder before asking for another,
                and note the link expires in {{ $expiry }}.
            </p>
        </div>

        {{-- One logout form, submitted by both the button and the link below. --}}
        <form id="verify-logout" method="POST" action="{{ route('logout') }}" class="hidden">@csrf</form>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit">Send the link again</x-ui.button>
            </form>

            <x-ui.button type="submit" form="verify-logout" variant="ghost">Log out</x-ui.button>
        </div>

        <p class="mt-4 text-meta leading-relaxed text-ink-low">
            Wrong address?
            <button type="submit" form="verify-logout" class="text-accent-hover underline underline-offset-2 hover:text-accent">Log out</button>
            and sign up with the one you use — your codes are delivered to it.
        </p>

        <p class="mt-5 border-t border-surface-3 pt-4 text-meta text-ink-low">
            Still stuck? <a href="{{ route('contact') }}" class="text-accent-hover underline underline-offset-2 hover:text-accent">Message our support team</a> and we will confirm it for you.
        </p>
    </x-auth-shell>
</x-guest-layout>
