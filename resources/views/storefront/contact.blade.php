@extends('layouts.storefront')

@section('title', 'Contact Us — Steam Store BD')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-10">
        <h1 class="text-4xl font-bold text-white mb-3">Contact Us</h1>
        <p class="text-gray-400">We're here to help. Usually respond within 1-2 hours.</p>
    </div>

    @if(session('success'))
    <div class="bg-green-900/40 border border-green-500/50 rounded-xl p-4 mb-6 text-green-300 text-sm">
        ✅ {{ session('success') }}
    </div>
    @endif

    {{-- Quick contact --}}
    @if(site_setting('contact_whatsapp'))
    <div class="bg-green-900/20 border border-green-500/30 rounded-2xl p-5 mb-8 flex items-center gap-4">
        <div class="text-4xl">💬</div>
        <div>
            <p class="text-white font-semibold mb-1">Chat on WhatsApp</p>
            <p class="text-gray-400 text-sm mb-2">Fastest way to reach us for urgent help</p>
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', site_setting('contact_whatsapp')) }}"
               target="_blank"
               class="inline-block bg-green-600 hover:bg-green-500 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors">
                Open WhatsApp Chat →
            </a>
        </div>
    </div>
    @endif

    {{-- Contact form --}}
    <div class="bg-gray-900 rounded-2xl border border-gray-700/50 p-6">
        <h2 class="text-xl font-bold text-white mb-5">Send us a Message</h2>
        <form method="POST" action="{{ route('contact.submit') }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full bg-gray-800 border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-brand-500 focus:outline-none">
                    @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full bg-gray-800 border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-brand-500 focus:outline-none">
                    @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">Message</label>
                    <textarea name="message" rows="5" required
                              class="w-full bg-gray-800 border border-gray-600 text-white rounded-xl px-4 py-3 focus:border-brand-500 focus:outline-none resize-none"
                              placeholder="How can we help you?">{{ old('message') }}</textarea>
                    @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <button type="submit" :disabled="loading"
                    class="w-full mt-6 btn-steam font-bold py-3 rounded-xl transition-all hover:shadow-steam-glow"
                    :class="loading ? 'opacity-75 cursor-wait' : ''">
                <span x-show="!loading">Send Message</span>
                <span x-show="loading" x-cloak>Sending...</span>
            </button>
        </form>
    </div>
</div>
@endsection
