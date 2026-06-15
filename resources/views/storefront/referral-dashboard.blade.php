@extends('layouts.storefront')

@section('title', 'Referral & Wallet — Steam Store BD')

@section('content')

{{-- Breadcrumb --}}
<div style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
        <nav class="flex items-center gap-2 text-sm text-gray-400">
            <a href="{{ route('home') }}" class="hover:text-brand-500 transition-colors">Home</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="font-medium" style="color:#0E1F35;">Referral &amp; Wallet</span>
        </nav>
    </div>
</div>

<div style="background:#F8FAFF; min-height:calc(100vh - 64px);">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-bold mb-8" style="color:#071428;">Referral &amp; Wallet</h1>

    {{-- Top cards: Referral Code + Wallet Balance --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        {{-- Referral Code Card --}}
        <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);"
             x-data="{ copied: false }">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl"
                     style="background:linear-gradient(135deg,#071428,#1a3a6b);">🔗</div>
                <div>
                    <h2 class="font-bold text-base" style="color:#071428;">Your Referral Code</h2>
                    <p class="text-xs text-gray-400">Share this code with friends to earn rewards</p>
                </div>
            </div>

            @if($user->referral_code)
            <div class="flex items-center gap-3 p-4 rounded-2xl mb-4" style="background:#F0F5FF; border:1px solid #DBEAFE;">
                <span class="flex-1 text-2xl font-black font-mono tracking-widest" style="color:#1D4ED8;">
                    {{ $user->referral_code }}
                </span>
                <button @click="navigator.clipboard.writeText('{{ $user->referral_code }}'); copied=true; setTimeout(()=>copied=false,2000)"
                        class="text-sm font-bold px-4 py-2 rounded-xl transition-all"
                        style="background:#2563EB; color:#fff;">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </button>
            </div>

            <div class="text-xs text-gray-400 space-y-1">
                <div class="flex justify-between">
                    <span>Total referrals</span>
                    <span class="font-semibold" style="color:#071428;">{{ $usages->total() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Total earned</span>
                    <span class="font-semibold text-green-600">
                        ৳ {{ number_format($usages->getCollection()->where('status', 'credited')->sum('owner_reward'), 0) }}
                    </span>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400">No referral code assigned. Please contact support.</p>
            @endif
        </div>

        {{-- Wallet Balance Card --}}
        <div class="bg-white rounded-2xl p-6" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl"
                     style="background:linear-gradient(135deg,#064E3B,#065F46);">💰</div>
                <div>
                    <h2 class="font-bold text-base" style="color:#071428;">Wallet Balance</h2>
                    <p class="text-xs text-gray-400">Use at checkout to reduce your order total</p>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-4">
                <span class="text-4xl font-black" style="color:#059669;">৳ {{ number_format($user->wallet_balance, 0) }}</span>
                <span class="text-sm text-gray-400">BDT</span>
            </div>
            <a href="{{ route('checkout') }}"
               class="block text-center text-sm font-bold py-2.5 rounded-xl text-white transition-all hover:opacity-90"
               style="background:#059669;">
                Shop &amp; Use Balance →
            </a>
        </div>
    </div>

    {{-- How It Works --}}
    <div class="bg-white rounded-2xl p-6 mb-8" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
        <h2 class="font-bold text-base mb-5" style="color:#071428;">How the Referral Program Works</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['icon'=>'🔗','title'=>'Share Your Code','desc'=>'Give your unique referral code to anyone.'],
                ['icon'=>'🛒','title'=>'They Buy &amp; Save','desc'=>'Your friend uses the code at checkout and gets a discount.'],
                ['icon'=>'💸','title'=>'You Earn Wallet Credit','desc'=>'After their order is confirmed, wallet credit is added to your account.'],
            ] as $step)
            <div class="text-center p-4 rounded-xl" style="background:#F8FAFF; border:1px solid #E8EEF8;">
                <div class="text-3xl mb-2">{{ $step['icon'] }}</div>
                <h3 class="font-bold text-sm mb-1" style="color:#071428;">{!! $step['title'] !!}</h3>
                <p class="text-xs text-gray-400">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

        {{-- Referral History --}}
        <div>
            <h2 class="font-bold text-base mb-4" style="color:#071428;">Referral History</h2>
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
                @if($usages->isEmpty())
                <div class="p-8 text-center">
                    <div class="text-4xl mb-2">🔗</div>
                    <p class="text-sm text-gray-400">No referrals yet. Share your code to start earning!</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Order</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Buyer Saved</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">You Earned</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($usages as $usage)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs" style="color:#071428;">
                                    {{ $usage->order?->order_number ?? '—' }}
                                    <div class="text-gray-400 font-sans">{{ $usage->created_at->format('d M Y') }}</div>
                                </td>
                                <td class="px-4 py-3 text-green-600 font-semibold">৳ {{ number_format($usage->discount_given, 0) }}</td>
                                <td class="px-4 py-3 font-semibold" style="color:#071428;">৳ {{ number_format($usage->owner_reward, 0) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                        @if($usage->status === 'credited') bg-green-100 text-green-700
                                        @elseif($usage->status === 'pending') bg-yellow-100 text-yellow-700
                                        @else bg-red-100 text-red-600 @endif">
                                        {{ ucfirst($usage->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($usages->hasPages())
                <div class="px-4 py-3 border-t border-gray-50">{{ $usages->links() }}</div>
                @endif
                @endif
            </div>
        </div>

        {{-- Wallet Transactions --}}
        <div>
            <h2 class="font-bold text-base mb-4" style="color:#071428;">Wallet Transactions</h2>
            <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
                @if($transactions->isEmpty())
                <div class="p-8 text-center">
                    <div class="text-4xl mb-2">💳</div>
                    <p class="text-sm text-gray-400">No wallet activity yet.</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Description</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Amount</th>
                                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($transactions as $tx)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-medium" style="color:#071428;">{{ $tx->sourceLabel() }}</div>
                                    @if($tx->description)
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $tx->description }}</div>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $tx->created_at->format('d M Y, h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3 font-bold text-sm
                                    @if($tx->type === 'credit') text-green-600 @else text-red-500 @endif">
                                    {{ $tx->type === 'credit' ? '+' : '-' }} ৳ {{ number_format($tx->amount, 0) }}
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold" style="color:#071428;">
                                    ৳ {{ number_format($tx->balance_after, 0) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-50">{{ $transactions->links() }}</div>
                @endif
                @endif
            </div>
        </div>

    </div>
</div>
</div>

@push('styles')
<style>body { background: #F8FAFF !important; }</style>
@endpush

@endsection
