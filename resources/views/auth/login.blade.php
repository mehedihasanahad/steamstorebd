<x-guest-layout title="Sign In">
    <x-auth-shell heading="Welcome back" subheading="Sign in to your Steam Store BD account">

        <x-auth-google />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <x-ui.input label="Email address" name="email" type="email" required autofocus
                        autocomplete="username" :value="old('email')" placeholder="you@example.com"
                        :error="$errors->first('email')" />

            <x-auth-password autocomplete="current-password">
                <x-slot:action>
                    @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-meta text-accent-hover hover:underline">Forgot password?</a>
                    @endif
                </x-slot:action>
            </x-auth-password>

            <label class="flex cursor-pointer items-center gap-2">
                <input id="remember_me" name="remember" type="checkbox"
                       class="h-4 w-4 rounded-chip border-surface-3 bg-surface-2 text-accent focus:ring-2 focus:ring-accent/40">
                <span class="text-caption text-ink-mid">Remember me</span>
            </label>

            <x-ui.button type="submit" size="lg" class="w-full">Sign in to account</x-ui.button>
        </form>

        <p class="mt-6 text-center text-caption text-ink-low">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-accent-hover hover:underline">Create one</a>
        </p>
    </x-auth-shell>
</x-guest-layout>
