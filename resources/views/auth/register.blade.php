<x-guest-layout>

<div style="min-height:100vh; display:flex; align-items:stretch;">

    {{-- Left decorative panel --}}
    <div class="auth-left-panel" style="width:52%; position:relative; overflow:hidden; background:linear-gradient(160deg,#071428 0%,#040D1A 60%,#071428 100%);">
        <div class="grid-overlay" style="position:absolute;inset:0;opacity:0.5;"></div>
        <div class="left-panel-orb" style="width:400px;height:400px;background:rgba(37,99,235,0.18);top:-100px;left:-80px;"></div>
        <div class="left-panel-orb" style="width:300px;height:300px;background:rgba(29,78,216,0.12);bottom:60px;right:-60px;"></div>

        <div style="position:relative;z-index:1;display:flex;flex-direction:column;justify-content:center;height:100%;padding:3.5rem;">
            <div style="margin-bottom:3rem;">
                <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;gap:0.75rem;text-decoration:none;">
                    <img src="{{ asset('images/logo.svg') }}" alt="Steam Store BD" width="48" height="48" style="border-radius:12px;flex-shrink:0;">
                    <div>
                        <div style="font-weight:800;font-size:1.25rem;color:#fff;letter-spacing:-0.02em;">Steam Store BD</div>
                        <div style="font-size:0.75rem;color:#557AA0;">Official Gift Card Marketplace</div>
                    </div>
                </a>
            </div>

            <h1 style="font-size:2.25rem;font-weight:800;color:#fff;line-height:1.15;letter-spacing:-0.03em;margin-bottom:1rem;">
                Join thousands of<br>
                <span style="background:linear-gradient(90deg,#4B8FEF,#2563EB);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">gamers in Bangladesh</span>
            </h1>
            <p style="color:#7898BB;font-size:0.9375rem;line-height:1.65;max-width:380px;margin-bottom:2.5rem;">
                Create a free account to buy Steam gift cards, track your orders, and get instant code delivery to your inbox.
            </p>

            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:3rem;">
                @foreach([
                    ['⚡','Instant code delivery after payment'],
                    ['📦','All orders saved in your account'],
                    ['🔒','Secured by bKash Tokenized Checkout'],
                ] as [$icon, $text])
                <div style="display:flex;align-items:center;gap:0.875rem;">
                    <div style="width:36px;height:36px;background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">{{ $icon }}</div>
                    <span style="color:#9BB5D5;font-size:0.875rem;">{{ $text }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Right form panel --}}
    <div class="auth-right-panel" style="width:48%;background:#0E1F35;display:flex;align-items:center;justify-content:center;padding:2rem;position:relative;">
        <div style="position:absolute;top:-80px;right:-80px;width:280px;height:280px;background:rgba(37,99,235,0.08);border-radius:50%;filter:blur(60px);pointer-events:none;"></div>

        <div style="width:100%;max-width:400px;position:relative;z-index:1;">

            {{-- Mobile logo --}}
            <div class="md:hidden" style="text-align:center;margin-bottom:2rem;">
                <a href="{{ url('/') }}" style="display:inline-flex;align-items:center;gap:0.625rem;text-decoration:none;">
                    <img src="{{ asset('images/logo.svg') }}" alt="Steam Store BD" width="40" height="40" style="border-radius:10px;flex-shrink:0;">
                    <span style="font-weight:800;font-size:1.125rem;color:#fff;">Steam Store BD</span>
                </a>
            </div>

            <div style="margin-bottom:1.75rem;">
                <h2 style="font-size:1.625rem;font-weight:800;color:#fff;letter-spacing:-0.025em;margin-bottom:0.375rem;">Create account</h2>
                <p style="color:#557AA0;font-size:0.875rem;">Takes less than a minute — free forever</p>
            </div>

            @if(session('error'))
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;padding:0.75rem 1rem;margin-bottom:1.25rem;">
                <p style="color:#FCA5A5;font-size:0.8125rem;">{{ session('error') }}</p>
            </div>
            @endif

            {{-- Google OAuth --}}
            <a href="{{ route('auth.google') }}"
               style="display:flex;align-items:center;justify-content:center;gap:0.75rem;width:100%;padding:0.8rem 1.5rem;background:#fff;border:none;border-radius:0.75rem;font-weight:600;font-size:0.9375rem;color:#1F2937;text-decoration:none;transition:box-shadow 0.2s,transform 0.1s;box-shadow:0 1px 4px rgba(0,0,0,0.12);"
               onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.18)';this.style.transform='translateY(-1px)'"
               onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,0.12)';this.style.transform='translateY(0)'">
                <svg width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    <path fill="none" d="M0 0h48v48H0z"/>
                </svg>
                Continue with Google
            </a>

            {{-- Divider --}}
            <div style="display:flex;align-items:center;gap:1rem;margin:1.5rem 0;">
                <div style="flex:1;height:1px;background:rgba(85,122,160,0.2);"></div>
                <span style="font-size:0.75rem;color:#3A5E80;">or register with email</span>
                <div style="flex:1;height:1px;background:rgba(85,122,160,0.2);"></div>
            </div>

            {{-- Registration form --}}
            <form method="POST" action="{{ route('register') }}"
                  x-data="{
                      pw: '',
                      get len()  { return this.pw.length >= 8; },
                      get upp()  { return /[A-Z]/.test(this.pw); },
                      get num()  { return /[0-9]/.test(this.pw); },
                      get sym()  { return /[^A-Za-z0-9]/.test(this.pw); },
                      get score(){ return [this.len, this.upp, this.num, this.sym].filter(Boolean).length; },
                      get color(){ return ['#3A5E80','#EF4444','#F59E0B','#3B82F6','#22C55E'][this.score]; },
                      get label(){ return ['','Weak','Fair','Good','Strong'][this.score]; }
                  }"
                  style="display:flex;flex-direction:column;gap:1.25rem;">
                @csrf

                {{-- Name --}}
                <div>
                    <label for="name" class="auth-label">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}"
                           required autofocus autocomplete="name"
                           placeholder="e.g. Rahim Uddin"
                           class="auth-input"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                    @error('name')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="auth-label">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           required autocomplete="username"
                           placeholder="you@gmail.com"
                           class="auth-input"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                    @error('email')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="auth-label">Password</label>
                    <input id="password" name="password" type="password"
                           required autocomplete="new-password"
                           placeholder="Min 8 chars, uppercase, number, symbol"
                           class="auth-input"
                           x-model="pw"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">

                    {{-- Strength indicator --}}
                    <div x-show="pw.length > 0" x-cloak style="margin-top:0.75rem;">

                        {{-- Bar + label --}}
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.625rem;">
                            <div style="flex:1;height:4px;border-radius:99px;background:rgba(85,122,160,0.15);overflow:hidden;">
                                {{-- Object syntax merges with static style; string syntax replaces it --}}
                                <div style="height:100%;border-radius:99px;"
                                     :style="{ width: (score * 25) + '%', background: color, transition: 'width 0.35s ease, background 0.35s ease' }"></div>
                            </div>
                            <span style="font-size:0.75rem;font-weight:700;min-width:38px;text-align:right;"
                                  :style="{ color: color }" x-text="label"></span>
                        </div>

                        {{-- Requirements — written explicitly so len/upp/num/sym stay reactive --}}
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem 1rem;">

                            @foreach([['len','8+ characters'],['upp','Uppercase letter'],['num','Number (0–9)'],['sym','Special character']] as [$prop, $text])
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <div style="width:16px;height:16px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                                     :style="{ background: {{ $prop }} ? '#22C55E' : 'rgba(85,122,160,0.2)', transition: 'background 0.2s' }">
                                    <svg x-show="{{ $prop }}" width="9" height="9" viewBox="0 0 9 9" fill="none">
                                        <path d="M1.5 4.5l2 2L7.5 2" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <svg x-show="!{{ $prop }}" width="9" height="9" viewBox="0 0 9 9" fill="none">
                                        <path d="M2.5 2.5l4 4M6.5 2.5l-4 4" stroke="#557AA0" stroke-width="1.6" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <span style="font-size:0.75rem;" :style="{ color: {{ $prop }} ? '#4ADE80' : '#557AA0' }">{{ $text }}</span>
                            </div>
                            @endforeach

                        </div>
                    </div>

                    @error('password')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="auth-label">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           required autocomplete="new-password"
                           placeholder="Re-enter your password"
                           class="auth-input"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                    @error('password_confirmation')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-btn" style="margin-top:0.25rem;">
                    Create Account
                </button>
            </form>

            {{-- Login link --}}
            <p style="text-align:center;font-size:0.875rem;color:#557AA0;margin-top:1.5rem;">
                Already have an account?
                <a href="{{ route('login') }}" style="color:#4B8FEF;font-weight:600;text-decoration:none;"
                   onmouseover="this.style.color='#7CB3F5'" onmouseout="this.style.color='#4B8FEF'">
                    Sign in
                </a>
            </p>

            {{-- Back to store --}}
            <a href="{{ url('/') }}" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;padding:0.75rem 1.5rem;margin-top:1rem;background:rgba(37,99,235,0.08);border:1px solid rgba(37,99,235,0.2);border-radius:0.75rem;color:#4B8FEF;font-size:0.875rem;font-weight:500;text-decoration:none;" onmouseover="this.style.background='rgba(37,99,235,0.15)'" onmouseout="this.style.background='rgba(37,99,235,0.08)'">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to store
            </a>
        </div>
    </div>
</div>

</x-guest-layout>
