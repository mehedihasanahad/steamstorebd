<x-guest-layout title="Forgot Password">
    <x-auth-shell heading="Forgot password?"
                  subheading="Tell us the address on your account and we will send a reset link.">

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
            @csrf

            <x-ui.input label="Email address" name="email" type="email" required autofocus
                        :value="old('email')" placeholder="you@example.com"
                        :error="$errors->first('email')" />

            <x-ui.button type="submit" size="lg" class="w-full">Send reset link</x-ui.button>
        </form>

        <p class="mt-6 text-center text-caption text-ink-low">
            Remembered it?
            <a href="{{ route('login') }}" class="font-semibold text-accent-hover hover:underline">Sign in</a>
        </p>
    </x-auth-shell>
</x-guest-layout>
