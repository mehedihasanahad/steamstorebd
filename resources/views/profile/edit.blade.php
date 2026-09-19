@extends('layouts.storefront')

@section('title', 'My Profile — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your Steam Store BD account details.')

@section('content')

<div class="mx-auto max-w-3xl px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Profile', 'url' => null],
    ]" />

    <div class="flex items-center gap-4">
        <span class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-card bg-accent text-title font-black text-white" aria-hidden="true">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </span>
        <div class="min-w-0">
            <h1 class="truncate text-title font-extrabold text-ink-hi">{{ $user->name }}</h1>
            <p class="mt-0.5 truncate text-caption text-ink-low">{{ $user->email }}</p>
        </div>
    </div>

    <div class="mt-6 space-y-4">

        {{-- ── Profile information ── --}}
        <section class="rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6" aria-labelledby="profile-info-heading">
            <h2 id="profile-info-heading" class="text-lede font-bold text-ink-hi">Profile information</h2>
            <p class="mt-1 text-caption text-ink-low">Update your name, e-mail address and phone number.</p>

            <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

            <form method="post" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('patch')

                @if($errors->any() && ! $errors->updatePassword->any() && ! $errors->userDeletion->any())
                    <ul class="list-inside list-disc space-y-1 rounded-control border border-danger/40 bg-danger/10 p-4 text-caption text-danger" role="alert">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif

                <x-ui.input label="Full name" name="name" required autofocus autocomplete="name"
                            :value="old('name', $user->name)" />

                <div>
                    <x-ui.input label="Email address" name="email" type="email" required autocomplete="username"
                                :value="old('email', $user->email)" />

                    @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <p class="mt-2 flex flex-wrap items-center gap-2 text-meta">
                            <span class="text-warning">E-mail not verified.</span>
                            <button form="send-verification" class="text-accent-hover underline underline-offset-2">Resend verification</button>
                            @if(session('status') === 'verification-link-sent')
                                <span class="text-success">Sent.</span>
                            @endif
                        </p>
                    @endif
                </div>

                <x-ui.input label="Phone" name="phone" type="tel" autocomplete="tel"
                            :value="old('phone', $user->phone)" placeholder="01XXXXXXXXX"
                            hint="Used for bKash payment during checkout." />

                <div class="flex items-center gap-3 pt-1">
                    <x-ui.button type="submit">Save changes</x-ui.button>
                    @if(session('status') === 'profile-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                              class="text-caption text-success" role="status">Saved.</span>
                    @endif
                </div>
            </form>
        </section>

        {{-- ── Update password ── --}}
        <section class="rounded-card border border-surface-3 bg-surface-1 p-5 md:p-6" aria-labelledby="password-heading">
            <h2 id="password-heading" class="text-lede font-bold text-ink-hi">Update password</h2>
            <p class="mt-1 text-caption text-ink-low">Use a long, unguessable password to keep your account secure.</p>

            <form method="post" action="{{ route('password.update') }}" class="mt-5 space-y-4">
                @csrf
                @method('put')

                @if($errors->updatePassword->any())
                    <ul class="list-inside list-disc space-y-1 rounded-control border border-danger/40 bg-danger/10 p-4 text-caption text-danger" role="alert">
                        @foreach($errors->updatePassword->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                @endif

                <x-auth-password name="current_password" id="current_password" label="Current password"
                                 autocomplete="current-password" />

                <x-auth-password name="password" id="new_password" label="New password"
                                 autocomplete="new-password" />

                <x-auth-password name="password_confirmation" id="confirm_new_password" label="Confirm new password"
                                 autocomplete="new-password" />

                <div class="flex items-center gap-3 pt-1">
                    <x-ui.button type="submit">Update password</x-ui.button>
                    @if(session('status') === 'password-updated')
                        <span x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                              class="text-caption text-success" role="status">Updated.</span>
                    @endif
                </div>
            </form>
        </section>

        {{-- ── Delete account ── --}}
        <section x-data="{ showModal: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }"
                 class="rounded-card border border-danger/30 bg-surface-1 p-5 md:p-6" aria-labelledby="delete-heading">
            <h2 id="delete-heading" class="text-lede font-bold text-ink-hi">Delete account</h2>
            <p class="mt-1 text-caption text-ink-low">Once deleted, everything on the account is removed permanently and cannot be recovered.</p>

            <x-ui.button variant="outline" class="mt-5 border-danger text-danger hover:bg-danger/10"
                         x-on:click="showModal = true">
                Delete account
            </x-ui.button>

            <div x-show="showModal" x-cloak @keydown.escape.window="showModal = false"
                 class="fixed inset-0 z-[80] flex items-center justify-center bg-surface-0/85 p-4 backdrop-blur"
                 role="dialog" aria-modal="true" aria-labelledby="delete-modal-heading">

                <div class="w-full max-w-md rounded-card border border-danger/40 bg-surface-1 p-6" @click.outside="showModal = false">
                    <h3 id="delete-modal-heading" class="text-lede font-bold text-ink-hi">Delete your account?</h3>
                    <p class="mt-1.5 text-caption text-ink-mid">This is permanent and cannot be undone. Enter your password to confirm.</p>

                    <form method="post" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('delete')

                        @if($errors->userDeletion->any())
                            <ul class="list-inside list-disc space-y-1 rounded-control border border-danger/40 bg-danger/10 p-3 text-caption text-danger" role="alert">
                                @foreach($errors->userDeletion->all() as $error)<li>{{ $error }}</li>@endforeach
                            </ul>
                        @endif

                        <x-auth-password name="password" id="delete_password" label="Password"
                                         autocomplete="current-password" placeholder="Enter your password" />

                        <div class="flex items-center justify-end gap-2">
                            <x-ui.button type="button" variant="ghost" x-on:click="showModal = false">Cancel</x-ui.button>
                            <x-ui.button type="submit" variant="danger">Yes, delete my account</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
</div>

@endsection
