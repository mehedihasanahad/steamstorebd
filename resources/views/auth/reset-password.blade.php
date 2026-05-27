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
                Create a new<br>
                <span style="background:linear-gradient(90deg,#4B8FEF,#2563EB);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">secure password</span>
            </h1>
            <p style="color:#7898BB;font-size:0.9375rem;line-height:1.65;max-width:380px;margin-bottom:2.5rem;">
                Choose a strong password to keep your Steam Store BD account safe and secure.
            </p>

            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:3rem;">
                @foreach([
                    ['🔒','Use at least 8 characters'],
                    ['🔡','Mix uppercase & lowercase letters'],
                    ['#️⃣','Include numbers and symbols'],
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

            {{-- Shield icon --}}
            <div style="width:56px;height:56px;background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;">
                <svg style="width:26px;height:26px;color:#4B8FEF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>

            <div style="margin-bottom:1.75rem;">
                <h2 style="font-size:1.625rem;font-weight:800;color:#fff;letter-spacing:-0.025em;margin-bottom:0.375rem;">Set new password</h2>
                <p style="color:#557AA0;font-size:0.875rem;">Must meet the strength requirements below</p>
            </div>

            {{-- Reset form with Alpine for strength bar --}}
            <form method="POST" action="{{ route('password.store') }}"
                  x-data="{
                      pw: '',
                      showPw: false,
                      showPwConfirm: false,
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

                {{-- Hidden token --}}
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email --}}
                <div>
                    <label for="email" class="auth-label">Email address</label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email', $request->email) }}"
                           required autocomplete="username"
                           placeholder="you@example.com"
                           class="auth-input"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                    @error('email')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- New Password --}}
                <div>
                    <label for="password" class="auth-label">New Password</label>
                    <div style="position:relative;">
                        <input id="password" name="password" :type="showPw ? 'text' : 'password'"
                               required autocomplete="new-password"
                               placeholder="Min 8 chars, uppercase, number, symbol"
                               class="auth-input"
                               style="padding-right:2.75rem;"
                               x-model="pw"
                               onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                               onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                        <button type="button" @click="showPw = !showPw"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;"
                                :style="{ color: showPw ? '#4B8FEF' : '#557AA0' }">
                            <svg x-show="!showPw" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPw" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Strength indicator --}}
                    <div x-show="pw.length > 0" x-cloak style="margin-top:0.75rem;">

                        {{-- Bar + label --}}
                        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.625rem;">
                            <div style="flex:1;height:4px;border-radius:99px;background:rgba(85,122,160,0.15);overflow:hidden;">
                                <div style="height:100%;border-radius:99px;"
                                     :style="{ width: (score * 25) + '%', background: color, transition: 'width 0.35s ease, background 0.35s ease' }"></div>
                            </div>
                            <span style="font-size:0.75rem;font-weight:700;min-width:38px;text-align:right;"
                                  :style="{ color: color }" x-text="label"></span>
                        </div>

                        {{-- Requirements --}}
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
                    <label for="password_confirmation" class="auth-label">Confirm New Password</label>
                    <div style="position:relative;">
                        <input id="password_confirmation" name="password_confirmation" :type="showPwConfirm ? 'text' : 'password'"
                               required autocomplete="new-password"
                               placeholder="Re-enter your new password"
                               class="auth-input"
                               style="padding-right:2.75rem;"
                               onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                               onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                        <button type="button" @click="showPwConfirm = !showPwConfirm"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;"
                                :style="{ color: showPwConfirm ? '#4B8FEF' : '#557AA0' }">
                            <svg x-show="!showPwConfirm" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPwConfirm" x-cloak width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-btn" style="margin-top:0.25rem;">
                    Reset Password
                </button>
            </form>

            {{-- Back to login --}}
            <p style="text-align:center;font-size:0.875rem;color:#557AA0;margin-top:1.5rem;">
                Remember your password?
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
