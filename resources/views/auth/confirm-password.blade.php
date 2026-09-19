<x-guest-layout title="Confirm Password">
    <x-auth-shell heading="Confirm your password"
                  subheading="This is a secure area. Please confirm your password before continuing.">

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
            @csrf

            <x-auth-password autocomplete="current-password" />

            <x-ui.button type="submit" size="lg" class="w-full">Confirm</x-ui.button>
        </form>
    </x-auth-shell>
</x-guest-layout>
