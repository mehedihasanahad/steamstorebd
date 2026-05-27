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
                Reset your<br>
                <span style="background:linear-gradient(90deg,#4B8FEF,#2563EB);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">account password</span>
            </h1>
            <p style="color:#7898BB;font-size:0.9375rem;line-height:1.65;max-width:380px;margin-bottom:2.5rem;">
                No worries — it happens. Enter your email and we'll send you a secure link to reset your password.
            </p>

            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:3rem;">
                @foreach([
                    ['🔗','Secure reset link sent to your email'],
                    ['⏱️','Link expires in 60 minutes'],
                    ['🔒','Your account stays protected'],
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

            {{-- Lock icon --}}
            <div style="width:56px;height:56px;background:rgba(37,99,235,0.15);border:1px solid rgba(37,99,235,0.3);border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:1.5rem;">
                <svg style="width:26px;height:26px;color:#4B8FEF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>

            <div style="margin-bottom:1.75rem;">
                <h2 style="font-size:1.625rem;font-weight:800;color:#fff;letter-spacing:-0.025em;margin-bottom:0.375rem;">Forgot password?</h2>
                <p style="color:#557AA0;font-size:0.875rem;">Enter your email to receive a reset link</p>
            </div>

            {{-- Success status --}}
            @if(session('status'))
            <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:flex-start;gap:0.75rem;">
                <svg style="width:18px;height:18px;color:#4ADE80;flex-shrink:0;margin-top:1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p style="color:#86EFAC;font-size:0.875rem;line-height:1.5;">{{ session('status') }}</p>
            </div>
            @endif

            {{-- Request form --}}
            <form method="POST" action="{{ route('password.email') }}" style="display:flex;flex-direction:column;gap:1.25rem;">
                @csrf

                <div>
                    <label for="email" class="auth-label">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username"
                           placeholder="you@example.com"
                           class="auth-input"
                           onfocus="this.style.borderColor='rgba(37,99,235,0.6)'"
                           onblur="this.style.borderColor='rgba(85,122,160,0.25)'">
                    @error('email')
                        <p class="auth-error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="auth-btn" style="margin-top:0.25rem;">
                    Send Reset Link
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
