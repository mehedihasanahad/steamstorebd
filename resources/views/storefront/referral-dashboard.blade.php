@extends('layouts.storefront')

@section('title', 'Referral & Wallet — Steam Store BD')
@section('robots', 'noindex, nofollow')
@section('meta_description', 'Your referral code, wallet balance and withdrawals.')

@php
    $rewardAmt     = $referralSettings['owner_reward_amount'];
    $discountType  = $referralSettings['discount_type'];
    $discountVal   = $referralSettings['discount_value'];
    $discountCap   = $referralSettings['max_discount_cap'];
    $discountLabel = $discountType === 'percentage'
        ? $discountVal . '% off' . ($discountCap > 0 ? ' (up to ' . format_bdt($discountCap) . ')' : '')
        : format_bdt($discountVal) . ' off';
    $minWithdrawal = (int) $referralSettings['min_withdrawal_amount'];
@endphp

@section('content')

<div class="mx-auto max-w-shell px-4 py-5 sm:px-6 lg:px-8 lg:py-section-lg">

    <x-catalog.breadcrumbs class="mb-4" :items="[
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Referral & wallet', 'url' => null],
    ]" />

    <h1 class="text-title md:text-display font-extrabold text-ink-hi">Referral &amp; wallet</h1>

    <div class="mt-5 grid gap-4 md:grid-cols-2">

        {{-- Referral code --}}
        <x-ui.copy-script />
        <section class="rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="code-heading" x-data="copyable(@js($user->referral_code))">
            <h2 id="code-heading" class="text-lede font-bold text-ink-hi">Your referral code</h2>
            <p class="mt-1 text-caption text-ink-low">Share it with friends to earn wallet credit.</p>

            @if($user->referral_code)
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div class="rounded-control border border-success/30 bg-success/10 p-3">
                        <p class="text-meta text-ink-mid">You earn per referral</p>
                        <p class="mt-0.5 text-title font-bold tabular-nums text-success">{{ format_bdt($rewardAmt) }}</p>
                    </div>
                    <div class="rounded-control border border-surface-3 bg-surface-2 p-3">
                        <p class="text-meta text-ink-mid">Your friend gets</p>
                        <p class="mt-0.5 text-lede font-bold text-ink-hi">{{ $discountLabel }}</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 rounded-control border border-surface-3 bg-surface-2 p-3">
                    <span class="flex-1 font-mono text-title font-bold tracking-widest text-ink-hi">{{ $user->referral_code }}</span>
                    <x-ui.button size="sm" x-on:click="copy()">
                        <span x-show="! copied && ! failed">Copy</span>
                        <span x-show="copied" x-cloak>Copied</span>
                        <span x-show="failed" x-cloak>Failed</span>
                    </x-ui.button>
                </div>

                <dl class="mt-3 space-y-1 text-caption">
                    <div class="flex justify-between">
                        <dt class="text-ink-low">Total referrals</dt>
                        <dd class="font-semibold text-ink-hi">{{ $usages->total() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-ink-low">Total earned</dt>
                        <dd class="font-semibold text-success">{{ format_bdt($usages->getCollection()->where('status', 'credited')->sum('owner_reward')) }}</dd>
                    </div>
                </dl>
            @else
                <p class="mt-4 text-caption text-ink-low">No referral code assigned. Please contact support.</p>
            @endif
        </section>

        {{-- Wallet --}}
        <section class="rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="wallet-heading">
            <h2 id="wallet-heading" class="text-lede font-bold text-ink-hi">Wallet balance</h2>
            <p class="mt-1 text-caption text-ink-low">Spend it at checkout, or withdraw it to bKash or Nagad.</p>

            <p class="mt-4 text-display font-extrabold tabular-nums text-success">{{ format_bdt($user->wallet_balance) }}</p>

            <div class="mt-4 flex flex-col gap-2">
                <x-ui.button :href="route('checkout')" variant="success">Shop and use the balance</x-ui.button>
                <x-ui.button href="#withdraw-form" variant="secondary">Withdraw to bKash / Nagad</x-ui.button>
            </div>
        </section>
    </div>

    {{-- Withdrawal request --}}
    <section id="withdraw-form" class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="withdraw-heading"
             x-data="{
                 method: '',
                 accountType: '',
                 get transferType() {
                     if (this.accountType === 'merchant') return 'Cash Out';
                     if (this.accountType === 'personal') return 'Send Money';
                     return '';
                 }
             }">
        <h2 id="withdraw-heading" class="text-lede font-bold text-ink-hi">Withdraw your balance</h2>
        <p class="mt-1 text-caption text-ink-low">
            Minimum {{ format_bdt($minWithdrawal) }} &middot; current balance {{ format_bdt($user->wallet_balance) }}
        </p>

        @if(session('withdrawal_success'))
            <p class="mt-4 rounded-control border border-success/30 bg-success/10 px-4 py-3 text-caption font-medium text-success" role="status">
                {{ session('withdrawal_success') }}
            </p>
        @endif

        @if($errors->any())
            <ul class="mt-4 list-inside list-disc space-y-0.5 rounded-control border border-danger/40 bg-danger/10 px-4 py-3 text-caption text-danger" role="alert">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @if($user->wallet_balance >= $minWithdrawal)
            <form action="{{ route('referral.withdraw') }}" method="POST" class="mt-5 space-y-5">
                @csrf

                <x-ui.input label="Amount (BDT)" name="amount" type="number" required
                            :min="$minWithdrawal" :max="(int) $user->wallet_balance" step="1"
                            :value="old('amount')" placeholder="e.g. 100" />

                <fieldset>
                    <legend class="mb-2 text-caption font-medium text-ink-mid">Payment method</legend>
                    <div class="flex gap-2">
                        @foreach([['bkash', 'bKash'], ['nagad', 'Nagad']] as [$value, $label])
                            <label class="flex flex-1 cursor-pointer items-center gap-2.5 rounded-control border px-4 py-3 transition-colors"
                                   :class="method === @js($value) ? 'border-accent bg-accent/10' : 'border-surface-3 bg-surface-2 hover:border-accent/40'">
                                <input type="radio" name="method" value="{{ $value }}" x-model="method" required
                                       class="h-4 w-4 border-surface-3 bg-surface-2 text-accent focus:ring-2 focus:ring-accent/40">
                                <span>
                                    <span class="block text-caption font-bold text-ink-hi">{{ $label }}</span>
                                    <span class="block text-meta text-ink-low">Personal &amp; merchant</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset x-show="method !== ''" x-transition x-cloak>
                    <legend class="mb-2 text-caption font-medium text-ink-mid">Account type</legend>
                    <div class="flex gap-2">
                        @foreach([['merchant', 'Merchant', 'Cash Out only'], ['personal', 'Personal', 'Send Money only']] as [$value, $label, $hint])
                            <label class="flex flex-1 cursor-pointer items-center gap-2.5 rounded-control border px-4 py-3 transition-colors"
                                   :class="accountType === @js($value) ? 'border-accent bg-accent/10' : 'border-surface-3 bg-surface-2 hover:border-accent/40'">
                                <input type="radio" name="account_type" value="{{ $value }}" x-model="accountType" required
                                       class="h-4 w-4 border-surface-3 bg-surface-2 text-accent focus:ring-2 focus:ring-accent/40">
                                <span>
                                    <span class="block text-caption font-bold text-ink-hi">{{ $label }}</span>
                                    <span class="block text-meta text-ink-low">{{ $hint }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <p x-show="accountType !== ''" x-transition x-cloak
                   class="rounded-control border border-accent/30 bg-accent/10 px-4 py-2.5 text-caption text-ink-mid">
                    Transfer type: <strong class="text-ink-hi" x-text="transferType"></strong>
                </p>

                <div x-show="accountType !== ''" x-transition x-cloak>
                    <label for="withdraw-phone" class="mb-1.5 block text-caption font-medium text-ink-mid">
                        <span x-text="method ? method.charAt(0).toUpperCase() + method.slice(1) : ''"></span> number
                    </label>
                    <input id="withdraw-phone" type="tel" name="phone_number" maxlength="11" pattern="01[3-9][0-9]{8}"
                           value="{{ old('phone_number') }}" placeholder="01XXXXXXXXX" required
                           class="w-full rounded-control border border-surface-3 bg-surface-2 px-3 min-h-[44px] text-body text-ink-hi
                                  placeholder:text-ink-low focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30">
                    <p class="mt-1.5 text-meta text-ink-low">The registered mobile number, 11 digits.</p>
                </div>

                <x-ui.button type="submit" size="lg" class="w-full" x-show="accountType !== ''" x-transition x-cloak>
                    Submit withdrawal request
                </x-ui.button>
            </form>
        @else
            <p class="mt-5 text-caption text-ink-low">
                Your balance is below the {{ format_bdt($minWithdrawal) }} minimum, so there is nothing to withdraw yet.
            </p>
        @endif
    </section>

    {{-- How it works --}}
    <section class="mt-4 rounded-card border border-surface-3 bg-surface-1 p-5" aria-labelledby="how-heading">
        <h2 id="how-heading" class="text-lede font-bold text-ink-hi">How the referral programme works</h2>

        <ol class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach([
                ['Share your code', 'Give your unique code to a friend who has not bought from us before.'],
                ['They buy and save', 'Your friend enters the code at checkout and gets ' . $discountLabel . ' on their first order.'],
                ['You earn ' . format_bdt($rewardAmt), 'Once their order is confirmed and delivered, the credit lands in your wallet.'],
            ] as $index => [$title, $desc])
                <li class="rounded-card border border-surface-3 bg-surface-2 p-4">
                    <span class="text-meta font-bold text-accent-hover">Step {{ $index + 1 }}</span>
                    <p class="mt-1.5 text-caption font-semibold text-ink-hi">{{ $title }}</p>
                    <p class="mt-1 text-meta leading-relaxed text-ink-low">{{ $desc }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Withdrawal history --}}
    <section class="mt-4" aria-labelledby="withdrawals-heading">
        <h2 id="withdrawals-heading" class="mb-3 text-lede font-bold text-ink-hi">Withdrawal requests</h2>

        <div class="overflow-hidden rounded-card border border-surface-3 bg-surface-1">
            @if($withdrawals->isEmpty())
                <p class="p-8 text-center text-caption text-ink-low">No withdrawal requests yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-caption">
                        <thead>
                            <tr class="border-b border-surface-3 bg-surface-2 text-left text-meta font-semibold uppercase tracking-wide text-ink-low">
                                <th scope="col" class="px-4 py-3">Amount</th>
                                <th scope="col" class="px-4 py-3">Method</th>
                                <th scope="col" class="px-4 py-3">Transfer type</th>
                                <th scope="col" class="px-4 py-3">Number</th>
                                <th scope="col" class="px-4 py-3">Status</th>
                                <th scope="col" class="px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-3">
                            @foreach($withdrawals as $wd)
                                @php
                                    $tone = match ($wd->status) {
                                        'paid'     => 'success',
                                        'approved' => 'accent',
                                        'pending'  => 'warning',
                                        default    => 'danger',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 font-bold tabular-nums text-ink-hi">{{ format_bdt($wd->amount) }}</td>
                                    <td class="px-4 py-3 font-semibold text-ink-mid">{{ $wd->methodLabel() }}</td>
                                    <td class="px-4 py-3 text-ink-mid">{{ $wd->transferTypeLabel() }}</td>
                                    <td class="px-4 py-3 font-mono text-meta text-ink-mid">{{ $wd->phone_number }}</td>
                                    <td class="px-4 py-3">
                                        <x-ui.badge :tone="$tone">{{ ucfirst($wd->status) }}</x-ui.badge>
                                        @if($wd->admin_note)
                                            <span class="mt-1 block text-meta text-ink-low">{{ $wd->admin_note }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-meta text-ink-low">{{ $wd->created_at->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($withdrawals->hasPages())
                    <div class="border-t border-surface-3 px-4 py-3">{{ $withdrawals->links('vendor.pagination.storefront') }}</div>
                @endif
            @endif
        </div>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">

        {{-- Referral history --}}
        <section aria-labelledby="referrals-heading">
            <h2 id="referrals-heading" class="mb-3 text-lede font-bold text-ink-hi">Referral history</h2>

            <div class="overflow-hidden rounded-card border border-surface-3 bg-surface-1">
                @if($usages->isEmpty())
                    <p class="p-8 text-center text-caption text-ink-low">No referrals yet. Share your code to start earning.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-caption">
                            <thead>
                                <tr class="border-b border-surface-3 bg-surface-2 text-left text-meta font-semibold uppercase tracking-wide text-ink-low">
                                    <th scope="col" class="px-4 py-3">Order</th>
                                    <th scope="col" class="px-4 py-3">Buyer saved</th>
                                    <th scope="col" class="px-4 py-3">You earned</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-3">
                                @foreach($usages as $usage)
                                    @php
                                        $tone = match ($usage->status) {
                                            'credited' => 'success',
                                            'pending'  => 'warning',
                                            default    => 'danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 font-mono text-meta text-ink-hi">
                                            {{ $usage->order?->order_number ?? '—' }}
                                            <span class="mt-0.5 block font-sans text-ink-low">{{ $usage->created_at->format('d M Y') }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-semibold tabular-nums text-success">{{ format_bdt($usage->discount_given) }}</td>
                                        <td class="px-4 py-3 font-semibold tabular-nums text-ink-hi">{{ format_bdt($usage->owner_reward) }}</td>
                                        <td class="px-4 py-3"><x-ui.badge :tone="$tone">{{ ucfirst($usage->status) }}</x-ui.badge></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($usages->hasPages())
                        <div class="border-t border-surface-3 px-4 py-3">{{ $usages->links('vendor.pagination.storefront') }}</div>
                    @endif
                @endif
            </div>
        </section>

        {{-- Wallet transactions --}}
        <section aria-labelledby="transactions-heading">
            <h2 id="transactions-heading" class="mb-3 text-lede font-bold text-ink-hi">Wallet transactions</h2>

            <div class="overflow-hidden rounded-card border border-surface-3 bg-surface-1">
                @if($transactions->isEmpty())
                    <p class="p-8 text-center text-caption text-ink-low">No wallet activity yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-caption">
                            <thead>
                                <tr class="border-b border-surface-3 bg-surface-2 text-left text-meta font-semibold uppercase tracking-wide text-ink-low">
                                    <th scope="col" class="px-4 py-3">Description</th>
                                    <th scope="col" class="px-4 py-3">Amount</th>
                                    <th scope="col" class="px-4 py-3">Balance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-surface-3">
                                @foreach($transactions as $tx)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <span class="block font-medium text-ink-hi">{{ $tx->sourceLabel() }}</span>
                                            @if($tx->description)
                                                <span class="mt-0.5 block text-meta text-ink-low">{{ $tx->description }}</span>
                                            @endif
                                            <span class="mt-0.5 block text-meta text-ink-low">{{ $tx->created_at->format('d M Y, h:i A') }}</span>
                                        </td>
                                        <td class="px-4 py-3 font-bold tabular-nums {{ $tx->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                            {{ $tx->type === 'credit' ? '+' : '−' }} {{ format_bdt($tx->amount) }}
                                        </td>
                                        <td class="px-4 py-3 font-semibold tabular-nums text-ink-hi">{{ format_bdt($tx->balance_after) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($transactions->hasPages())
                        <div class="border-t border-surface-3 px-4 py-3">{{ $transactions->links('vendor.pagination.storefront') }}</div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</div>

@endsection
