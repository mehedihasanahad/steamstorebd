<x-guest-layout title="Create Account">
    <x-auth-shell heading="Create account" subheading="Your orders, codes and wallet, all in one place">

        <x-auth-google label="Sign up with Google" />

        <form method="POST" action="{{ route('register') }}" class="space-y-5"
              x-data="{
                  pw: '',
                  get len()   { return this.pw.length >= 8; },
                  get upp()   { return /[A-Z]/.test(this.pw); },
                  get num()   { return /[0-9]/.test(this.pw); },
                  get sym()   { return /[^A-Za-z0-9]/.test(this.pw); },
                  get score() { return [this.len, this.upp, this.num, this.sym].filter(Boolean).length; },
                  get label() { return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.score]; },
                  get tone()  { return ['bg-surface-3', 'bg-danger', 'bg-warning', 'bg-accent', 'bg-success'][this.score]; },
                  get toneText() { return ['text-ink-low', 'text-danger', 'text-warning', 'text-accent-hover', 'text-success'][this.score]; }
              }">
            @csrf

            <x-ui.input label="Full name" name="name" required autofocus autocomplete="name"
                        :value="old('name')" placeholder="e.g. Rahim Uddin"
                        :error="$errors->first('name')" />

            <x-ui.input label="Email address" name="email" type="email" required autocomplete="username"
                        :value="old('email')" placeholder="you@gmail.com"
                        :error="$errors->first('email')" />

            <div>
                <x-auth-password autocomplete="new-password"
                                 placeholder="Min 8 chars, uppercase, number, symbol"
                                 x-model="pw" />

                {{-- The rules are the ones Password::defaults() enforces, shown
                     as they are met rather than as a rejection afterwards. --}}
                <div x-show="pw.length > 0" x-cloak class="mt-3">
                    <div class="flex items-center gap-3">
                        <span class="h-1 flex-1 overflow-hidden rounded-full bg-surface-3">
                            <span class="block h-full rounded-full transition-all duration-300"
                                  :class="tone" :style="`width: ${score * 25}%`"></span>
                        </span>
                        <span class="min-w-[42px] text-right text-meta font-bold" :class="toneText" x-text="label"></span>
                    </div>

                    <ul class="mt-2.5 grid grid-cols-2 gap-x-4 gap-y-2">
                        @foreach([['len', '8+ characters'], ['upp', 'Uppercase letter'], ['num', 'Number (0–9)'], ['sym', 'Special character']] as [$prop, $text])
                            <li class="flex items-center gap-2 text-meta" :class="{{ $prop }} ? 'text-success' : 'text-ink-low'">
                                <span class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full transition-colors"
                                      :class="{{ $prop }} ? 'bg-success' : 'bg-surface-3'" aria-hidden="true">
                                    <svg x-show="{{ $prop }}" class="h-2.5 w-2.5 text-surface-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                {{ $text }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <x-auth-password name="password_confirmation" label="Confirm password"
                             autocomplete="new-password" placeholder="Re-enter your password" />

            <x-ui.button type="submit" size="lg" class="w-full">Create account</x-ui.button>
        </form>

        <p class="mt-6 text-center text-caption text-ink-low">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-accent-hover hover:underline">Sign in</a>
        </p>
    </x-auth-shell>
</x-guest-layout>
