@php
    $delta = $settlement['delta'];
@endphp

<div class="space-y-3 text-sm">
    <dl class="space-y-2">
        <div class="flex items-center justify-between">
            <dt class="text-gray-500 dark:text-gray-400">Subtotal</dt>
            <dd class="font-medium tabular-nums text-gray-950 dark:text-white">{{ format_bdt($settlement['subtotal']) }}</dd>
        </div>

        @if ($settlement['referral'] > 0)
            <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Referral discount</dt>
                <dd class="font-medium tabular-nums text-success-600 dark:text-success-400">− {{ format_bdt($settlement['referral']) }}</dd>
            </div>
        @endif

        @if ($settlement['wallet'] > 0)
            <div class="flex items-center justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Wallet credit</dt>
                <dd class="font-medium tabular-nums text-success-600 dark:text-success-400">− {{ format_bdt($settlement['wallet']) }}</dd>
            </div>
        @endif

        <div class="flex items-center justify-between border-t border-gray-200 pt-2 dark:border-white/10">
            <dt class="font-semibold text-gray-950 dark:text-white">New total</dt>
            <dd class="text-base font-bold tabular-nums text-gray-950 dark:text-white">{{ format_bdt($settlement['total']) }}</dd>
        </div>

        <div class="flex items-center justify-between">
            <dt class="text-gray-500 dark:text-gray-400">Current total on file</dt>
            <dd class="tabular-nums text-gray-500 dark:text-gray-400">{{ format_bdt($settlement['total_before']) }}</dd>
        </div>
    </dl>

    @if ($settlement['wallet_refund'] > 0)
        <p class="rounded-lg bg-info-50 px-3 py-2 text-info-700 dark:bg-info-400/10 dark:text-info-400">
            {{ format_bdt($settlement['wallet_refund']) }} of wallet credit no longer fits this order and will be returned to the customer's balance.
        </p>
    @endif

    @if ($delta > 0)
        <p class="rounded-lg bg-warning-50 px-3 py-2 font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
            @if ($settlement['already_paid'])
                Collect {{ format_bdt($delta) }} more from the customer — this order is already paid.
            @else
                The amount payable goes up by {{ format_bdt($delta) }}.
            @endif
        </p>
    @elseif ($delta < 0)
        <p class="rounded-lg bg-danger-50 px-3 py-2 font-medium text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
            @if ($settlement['already_paid'])
                Refund {{ format_bdt(abs($delta)) }} to the customer — this order is already paid.
            @else
                The amount payable drops by {{ format_bdt(abs($delta)) }}.
            @endif
        </p>
    @else
        <p class="text-gray-500 dark:text-gray-400">No change to the amount payable.</p>
    @endif
</div>
