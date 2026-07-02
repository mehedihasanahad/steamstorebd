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
            {{-- Earn amount highlight --}}
            @php
                $rewardAmt    = $referralSettings['owner_reward_amount'];
                $discountType = $referralSettings['discount_type'];
                $discountVal  = $referralSettings['discount_value'];
                $discountCap  = $referralSettings['max_discount_cap'];
                // e.g. "৳30 off" | "10% off" | "10% off (up to ৳100)"
                $discountLabel = $discountType === 'percentage'
                    ? $discountVal . '% off' . ($discountCap > 0 ? ' (up to ৳' . number_format($discountCap, 0) . ')' : '')
                    : '৳' . number_format($discountVal, 0) . ' off';
            @endphp
            <div class="flex items-center justify-between gap-3 mb-4 px-4 py-3 rounded-2xl"
                 style="background:linear-gradient(135deg,#F0FDF4,#DCFCE7); border:1px solid #BBF7D0;">
                <div>
                    <p class="text-xs font-medium text-green-700">You earn per referral</p>
                    <p class="text-2xl font-black text-green-600">৳{{ number_format($rewardAmt, 0) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-green-700">Your friend gets</p>
                    <p class="text-lg font-black text-green-600">{{ $discountLabel }}</p>
                </div>
            </div>

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
                    <p class="text-xs text-gray-400">Use at checkout or withdraw to bKash / Nagad</p>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-4">
                <span class="text-4xl font-black" style="color:#059669;">৳ {{ number_format($user->wallet_balance, 0) }}</span>
                <span class="text-sm text-gray-400">BDT</span>
            </div>
            <a href="{{ route('checkout') }}"
               class="block text-center text-sm font-bold py-2.5 rounded-xl text-white transition-all hover:opacity-90 mb-2"
               style="background:#059669;">
                Shop &amp; Use Balance →
            </a>
            <button onclick="document.getElementById('withdraw-form').scrollIntoView({behavior:'smooth'})"
                    class="block w-full text-center text-sm font-bold py-2.5 rounded-xl transition-all hover:opacity-90"
                    style="background:#1D4ED8; color:#fff;">
                Withdraw to bKash / Nagad →
            </button>
        </div>
    </div>

    {{-- Withdrawal Request Form --}}
    <div id="withdraw-form" class="bg-white rounded-2xl p-6 mb-8" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);"
         x-data="{
             method: '',
             accountType: '',
             get transferType() {
                 if (this.accountType === 'merchant') return 'Cash Out';
                 if (this.accountType === 'personal') return 'Send Money';
                 return '';
             }
         }">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl"
                 style="background:linear-gradient(135deg,#1D4ED8,#2563EB);">💸</div>
            <div>
                <h2 class="font-bold text-base" style="color:#071428;">Withdraw Wallet Balance</h2>
                <p class="text-xs text-gray-400">Minimum ৳{{ number_format($referralSettings['min_withdrawal_amount'], 0) }} · Current balance: ৳{{ number_format($user->wallet_balance, 0) }}</p>
            </div>
        </div>

        @if(session('withdrawal_success'))
        <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium bg-green-50 text-green-700 border border-green-200">
            {{ session('withdrawal_success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 px-4 py-3 rounded-xl text-sm bg-red-50 text-red-700 border border-red-200">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php $minWithdrawal = (int) $referralSettings['min_withdrawal_amount']; @endphp
        @if($user->wallet_balance >= $minWithdrawal)
        <form action="{{ route('referral.withdraw') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Amount --}}
            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:#071428;">Amount (BDT)</label>
                <input type="number" name="amount" min="{{ $minWithdrawal }}" max="{{ (int) $user->wallet_balance }}" step="1"
                       value="{{ old('amount') }}"
                       placeholder="e.g. 100"
                       class="w-full px-4 py-2.5 rounded-xl text-sm border focus:outline-none focus:ring-2 focus:ring-blue-400"
                       style="border-color:#E8EEF8; color:#071428;" required>
            </div>

            {{-- Method --}}
            <div>
                <label class="block text-xs font-semibold mb-2" style="color:#071428;">Payment Method</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2.5 px-4 py-3 rounded-xl cursor-pointer border transition-all"
                           :class="method === 'bkash' ? 'border-pink-400 bg-pink-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                        <input type="radio" name="method" value="bkash" x-model="method" class="accent-pink-500" required>
                        <div>
                            <div class="text-sm font-bold" style="color:#E2136E;">bKash</div>
                            <div class="text-xs text-gray-400">Personal &amp; Merchant</div>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-2.5 px-4 py-3 rounded-xl cursor-pointer border transition-all"
                           :class="method === 'nagad' ? 'border-orange-400 bg-orange-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                        <input type="radio" name="method" value="nagad" x-model="method" class="accent-orange-500" required>
                        <div>
                            <div class="text-sm font-bold" style="color:#F37021;">Nagad</div>
                            <div class="text-xs text-gray-400">Personal &amp; Merchant</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Account Type --}}
            <div x-show="method !== ''" x-transition>
                <label class="block text-xs font-semibold mb-2" style="color:#071428;">Account Type</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2.5 px-4 py-3 rounded-xl cursor-pointer border transition-all"
                           :class="accountType === 'merchant' ? 'border-blue-400 bg-blue-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                        <input type="radio" name="account_type" value="merchant" x-model="accountType" class="accent-blue-500" required>
                        <div>
                            <div class="text-sm font-bold" style="color:#1D4ED8;">Merchant</div>
                            <div class="text-xs text-gray-400">Cash Out only</div>
                        </div>
                    </label>
                    <label class="flex-1 flex items-center gap-2.5 px-4 py-3 rounded-xl cursor-pointer border transition-all"
                           :class="accountType === 'personal' ? 'border-purple-400 bg-purple-50' : 'border-gray-200 bg-white hover:bg-gray-50'">
                        <input type="radio" name="account_type" value="personal" x-model="accountType" class="accent-purple-500" required>
                        <div>
                            <div class="text-sm font-bold" style="color:#7C3AED;">Personal</div>
                            <div class="text-xs text-gray-400">Send Money only</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Transfer type hint --}}
            <div x-show="accountType !== ''" x-transition
                 class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium"
                 style="background:#F0F5FF; border:1px solid #DBEAFE; color:#1D4ED8;">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Transfer type: <strong x-text="transferType"></strong></span>
            </div>

            {{-- Phone Number --}}
            <div x-show="accountType !== ''" x-transition>
                <label class="block text-xs font-semibold mb-1.5" style="color:#071428;">
                    <span x-text="method ? method.charAt(0).toUpperCase() + method.slice(1) : ''"></span> Number
                </label>
                <input type="tel" name="phone_number" maxlength="11" pattern="01[3-9][0-9]{8}"
                       value="{{ old('phone_number') }}"
                       placeholder="01XXXXXXXXX"
                       class="w-full px-4 py-2.5 rounded-xl text-sm border focus:outline-none focus:ring-2 focus:ring-blue-400"
                       style="border-color:#E8EEF8; color:#071428;" required>
                <p class="text-xs text-gray-400 mt-1">Enter the registered mobile number (11 digits)</p>
            </div>

            <button type="submit"
                    x-show="accountType !== ''"
                    x-transition
                    class="w-full py-3 rounded-xl text-sm font-bold text-white transition-all hover:opacity-90"
                    style="background:linear-gradient(135deg,#1D4ED8,#2563EB);">
                Submit Withdrawal Request
            </button>
        </form>
        @else
        <div class="text-center py-6">
            <div class="text-3xl mb-2">💳</div>
            <p class="text-sm text-gray-400">Your wallet balance is too low to withdraw.<br>Minimum withdrawal is ৳{{ number_format($minWithdrawal, 0) }}.</p>
        </div>
        @endif
    </div>

    {{-- How It Works --}}
    <div class="bg-white rounded-2xl p-6 mb-8" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
        <h2 class="font-bold text-base mb-5" style="color:#071428;">How the Referral Program Works</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['icon'=>'🔗','title'=>'Share Your Code','desc'=>'Give your unique referral code to a friend who hasn\'t bought from us before.'],
                ['icon'=>'🛒','title'=>'They Buy &amp; Save','desc'=>'Your friend enters the code at checkout and gets ' . $discountLabel . ' on their first order.'],
                ['icon'=>'💸','title'=>'You Earn ৳' . number_format($rewardAmt, 0),'desc'=>'After their order is confirmed & codes sent, ৳' . number_format($rewardAmt, 0) . ' wallet credit lands in your account instantly.'],
            ] as $step)
            <div class="text-center p-4 rounded-xl" style="background:#F8FAFF; border:1px solid #E8EEF8;">
                <div class="text-3xl mb-2">{{ $step['icon'] }}</div>
                <h3 class="font-bold text-sm mb-1" style="color:#071428;">{!! $step['title'] !!}</h3>
                <p class="text-xs text-gray-400">{!! $step['desc'] !!}</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Withdrawal History --}}
    <div class="mb-8">
        <h2 class="font-bold text-base mb-4" style="color:#071428;">Withdrawal Requests</h2>
        <div class="bg-white rounded-2xl overflow-hidden" style="border:1px solid #E8EEF8; box-shadow:0 4px 24px rgba(7,20,40,0.06);">
            @if($withdrawals->isEmpty())
            <div class="p-8 text-center">
                <div class="text-4xl mb-2">💸</div>
                <p class="text-sm text-gray-400">No withdrawal requests yet.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#F8FAFF; border-bottom:1px solid #E8EEF8;">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Amount</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Method</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Transfer Type</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Number</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($withdrawals as $wd)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 font-bold" style="color:#071428;">৳ {{ number_format($wd->amount, 0) }}</td>
                            <td class="px-4 py-3 font-semibold
                                @if($wd->method === 'bkash') text-pink-600
                                @else text-orange-500 @endif">
                                {{ $wd->methodLabel() }}
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $wd->transferTypeLabel() }}</td>
                            <td class="px-4 py-3 font-mono text-xs" style="color:#071428;">{{ $wd->phone_number }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold
                                    @if($wd->status === 'paid') bg-green-100 text-green-700
                                    @elseif($wd->status === 'approved') bg-blue-100 text-blue-700
                                    @elseif($wd->status === 'pending') bg-yellow-100 text-yellow-700
                                    @else bg-red-100 text-red-600 @endif">
                                    {{ ucfirst($wd->status) }}
                                </span>
                                @if($wd->admin_note)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $wd->admin_note }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ $wd->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($withdrawals->hasPages())
            <div class="px-4 py-3 border-t border-gray-50">{{ $withdrawals->links() }}</div>
            @endif
            @endif
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
