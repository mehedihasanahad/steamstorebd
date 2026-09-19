<x-guest-layout title="Verify Email">
    <x-auth-shell heading="Verify your e-mail"
                  subheading="We sent you a link. Click it and you are in.">

        <p class="text-caption leading-relaxed text-ink-mid">
            Thanks for signing up. Before you start, confirm your address by clicking the link we just e-mailed you.
            If it has not arrived, check your spam folder — or we will gladly send another.
        </p>

        @if(session('status') === 'verification-link-sent')
            <p class="mt-4 rounded-control border border-success/30 bg-success/10 px-4 py-3 text-caption text-success" role="status">
                A new verification link has been sent to the address you registered with.
            </p>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit">Resend verification e-mail</x-ui.button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-ui.button type="submit" variant="ghost">Log out</x-ui.button>
            </form>
        </div>
    </x-auth-shell>
</x-guest-layout>
