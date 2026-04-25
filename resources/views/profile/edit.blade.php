@extends('layouts.storefront')

@section('title', 'My Profile — Steam Store BD')

@section('content')

{{-- Breadcrumb --}}
<div style="background:#071428; border-bottom:1px solid rgba(37,99,235,0.12);">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-400 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-300 font-medium">Profile</span>
        </nav>
    </div>
</div>

<div style="background:#040D1A; min-height:calc(100vh - 64px);">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex items-center gap-4 mb-8">
        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl font-black text-white flex-shrink-0"
             style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $user->name }}</h1>
            <p class="text-gray-400 text-sm mt-0.5">{{ $user->email }}</p>
        </div>
    </div>

    <div class="space-y-6">

        {{-- ── Profile Information ── --}}
        <div class="rounded-2xl p-6 sm:p-8" style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
            <h2 class="text-lg font-bold text-white mb-1">Profile Information</h2>
            <p class="text-sm text-gray-400 mb-6">Update your name, email address and phone number.</p>

            <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

            <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
                @csrf
                @method('patch')

                @if($errors->any() && !$errors->updatePassword->any() && !$errors->userDeletion->any())
                <div class="rounded-xl p-4" style="background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.3);">
                    <ul class="text-red-400 text-sm space-y-1">
                        @foreach($errors->all() as $error)<li>• {{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-2 flex items-center gap-2">
                        <span class="text-yellow-400 text-xs">Email not verified.</span>
                        <button form="send-verification" class="text-xs text-brand-400 hover:text-brand-300 underline transition-colors">Resend verification</button>
                        @if (session('status') === 'verification-link-sent')
                        <span class="text-green-400 text-xs">Sent!</span>
                        @endif
                    </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">Phone</label>
                    <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);"
                           placeholder="01XXXXXXXXX">
                    <p class="text-gray-500 text-xs mt-1.5">Used for bKash payment during checkout.</p>
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit" class="btn-brand px-6 py-2.5 rounded-xl text-sm font-semibold">
                        Save Changes
                    </button>
                    @if (session('status') === 'profile-updated')
                    <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                          class="text-green-400 text-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Saved!
                    </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Update Password ── --}}
        <div class="rounded-2xl p-6 sm:p-8" style="background:#071428; border:1px solid rgba(37,99,235,0.15);">
            <h2 class="text-lg font-bold text-white mb-1">Update Password</h2>
            <p class="text-sm text-gray-400 mb-6">Use a long, random password to keep your account secure.</p>

            <form method="post" action="{{ route('password.update') }}" class="space-y-5">
                @csrf
                @method('put')

                @if($errors->updatePassword->any())
                <div class="rounded-xl p-4" style="background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.3);">
                    <ul class="text-red-400 text-sm space-y-1">
                        @foreach($errors->updatePassword->all() as $error)<li>• {{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">Current Password</label>
                    <input type="password" name="current_password" autocomplete="current-password"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">New Password</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-1.5">Confirm New Password</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                           style="background:#0E1F35; border:1px solid rgba(37,99,235,0.2);">
                </div>

                <div class="flex items-center gap-4 pt-1">
                    <button type="submit" class="btn-brand px-6 py-2.5 rounded-xl text-sm font-semibold">
                        Update Password
                    </button>
                    @if (session('status') === 'password-updated')
                    <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                          class="text-green-400 text-sm flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Updated!
                    </span>
                    @endif
                </div>
            </form>
        </div>

        {{-- ── Delete Account ── --}}
        <div x-data="{ showModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }"
             class="rounded-2xl p-6 sm:p-8" style="background:#071428; border:1px solid rgba(220,38,38,0.2);">
            <h2 class="text-lg font-bold text-white mb-1">Delete Account</h2>
            <p class="text-sm text-gray-400 mb-6">Once deleted, all your data is permanently removed and cannot be recovered.</p>

            <button @click="showModal = true"
                    class="px-6 py-2.5 rounded-xl text-sm font-semibold text-red-400 transition-colors"
                    style="border:1px solid rgba(220,38,38,0.3);"
                    onmouseover="this.style.background='rgba(220,38,38,0.1)'" onmouseout="this.style.background=''">
                Delete Account
            </button>

            {{-- Confirm Modal --}}
            <div x-show="showModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="background:rgba(4,13,26,0.85); backdrop-filter:blur(6px);">
                <div class="w-full max-w-md rounded-2xl p-7" style="background:#071428; border:1px solid rgba(220,38,38,0.3);">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(220,38,38,0.12);">
                            <svg class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">Delete your account?</h3>
                    </div>
                    <p class="text-gray-400 text-sm mb-6">This action is permanent and cannot be undone. Enter your password to confirm.</p>

                    <form method="post" action="{{ route('profile.destroy') }}">
                        @csrf
                        @method('delete')

                        @if($errors->userDeletion->any())
                        <div class="rounded-xl p-3 mb-4" style="background:rgba(220,38,38,0.08); border:1px solid rgba(220,38,38,0.3);">
                            <ul class="text-red-400 text-sm space-y-1">
                                @foreach($errors->userDeletion->all() as $error)<li>• {{ $error }}</li>@endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="mb-6">
                            <label class="block text-sm font-semibold text-gray-300 mb-1.5">Password</label>
                            <input type="password" name="password" placeholder="Enter your password"
                                   class="w-full rounded-xl px-4 py-3 text-sm text-white focus:outline-none transition-all"
                                   style="background:#0E1F35; border:1px solid rgba(220,38,38,0.3);">
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="button" @click="showModal = false"
                                    class="px-5 py-2.5 rounded-xl text-sm font-medium text-gray-400 transition-colors"
                                    style="border:1px solid rgba(37,99,235,0.2);"
                                    onmouseover="this.style.background='rgba(37,99,235,0.08)'" onmouseout="this.style.background=''">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors"
                                    style="background:#DC2626;">
                                Yes, Delete Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

@push('styles')
<style>
body { background: #040D1A !important; }
input::placeholder { color: #3A5E80; }
input:focus { border-color: rgba(37,99,235,0.5) !important; outline: none; }
</style>
@endpush

@endsection
