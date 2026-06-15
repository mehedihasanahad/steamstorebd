<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReferralUsage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function isEnabled(): bool
    {
        return (bool) SiteSetting::get('referral_enabled', false);
    }

    public function getSettings(): array
    {
        return [
            'enabled'             => $this->isEnabled(),
            'discount_type'       => SiteSetting::get('referral_discount_type', 'flat'),
            'discount_value'      => (float) SiteSetting::get('referral_discount_value', 0),
            'min_order_amount'    => (float) SiteSetting::get('referral_min_order_amount', 0),
            'owner_reward_amount' => (float) SiteSetting::get('referral_owner_reward_amount', 0),
            'max_discount_cap'    => (float) SiteSetting::get('referral_max_discount_cap', 0),
        ];
    }

    /**
     * Validate a referral code against an order total and current user.
     * Returns ['valid', 'discount', 'referrer', 'message'].
     */
    public function validateCode(string $code, ?User $currentUser, float $orderTotal): array
    {
        if (! $this->isEnabled()) {
            return ['valid' => false, 'discount' => 0, 'referrer' => null, 'message' => 'Referral program is not active.'];
        }

        $referrer = User::where('referral_code', strtoupper(trim($code)))->first();

        if (! $referrer) {
            return ['valid' => false, 'discount' => 0, 'referrer' => null, 'message' => 'Invalid referral code.'];
        }

        if ($currentUser && $referrer->id === $currentUser->id) {
            return ['valid' => false, 'discount' => 0, 'referrer' => null, 'message' => 'You cannot use your own referral code.'];
        }

        // Global standard: referral discount is for new customers only.
        if ($currentUser) {
            // Block if user already has a paid/completed order (existing customer).
            $hasExistingOrders = Order::where('user_id', $currentUser->id)
                ->whereIn('status', ['paid', 'completed'])
                ->exists();

            if ($hasExistingOrders) {
                return ['valid' => false, 'discount' => 0, 'referrer' => null, 'message' => 'Referral discounts available once for new customers only.'];
            }

            // Block if they already used a referral code on a previous (possibly failed) order.
            $alreadyUsed = ReferralUsage::where('referee_id', $currentUser->id)
                ->whereIn('status', ['pending', 'credited'])
                ->exists();

            if ($alreadyUsed) {
                return ['valid' => false, 'discount' => 0, 'referrer' => null, 'message' => 'You have already used a referral code before.'];
            }
        }

        $settings = $this->getSettings();

        if ($settings['min_order_amount'] > 0 && $orderTotal < $settings['min_order_amount']) {
            return [
                'valid'    => false,
                'discount' => 0,
                'referrer' => null,
                'message'  => 'Minimum order ৳' . number_format($settings['min_order_amount'], 0) . ' required for referral discount.',
            ];
        }

        $discount = $this->calculateDiscount($orderTotal, $settings);

        return [
            'valid'    => true,
            'discount' => $discount,
            'referrer' => $referrer,
            'message'  => 'Referral code applied! You save ৳' . number_format($discount, 0),
        ];
    }

    public function calculateDiscount(float $orderTotal, array $settings): float
    {
        if ($settings['discount_type'] === 'percentage') {
            $discount = $orderTotal * ($settings['discount_value'] / 100);
            if ($settings['max_discount_cap'] > 0) {
                $discount = min($discount, $settings['max_discount_cap']);
            }
        } else {
            $discount = $settings['discount_value'];
        }

        return min($discount, $orderTotal);
    }

    /**
     * Record a referral usage when an order is created.
     */
    public function recordUsage(Order $order, User $referrer, float $discountGiven): ReferralUsage
    {
        $settings = $this->getSettings();

        return ReferralUsage::create([
            'referrer_id'    => $referrer->id,
            'referee_id'     => $order->user_id,
            'order_id'       => $order->id,
            'discount_given' => $discountGiven,
            'owner_reward'   => $settings['owner_reward_amount'],
            'status'         => 'pending',
        ]);
    }

    /**
     * Credit the referrer's wallet after a successful order payment.
     */
    public function creditReferrerWallet(Order $order): void
    {
        $usage = ReferralUsage::where('order_id', $order->id)
            ->where('status', 'pending')
            ->with('referrer')
            ->first();

        if (! $usage || $usage->owner_reward <= 0) {
            return;
        }

        DB::transaction(function () use ($usage, $order) {
            $referrer   = $usage->referrer;
            $newBalance = (float) $referrer->wallet_balance + (float) $usage->owner_reward;

            $referrer->update(['wallet_balance' => $newBalance]);

            WalletTransaction::create([
                'user_id'        => $referrer->id,
                'amount'         => $usage->owner_reward,
                'type'           => 'credit',
                'source'         => 'referral_reward',
                'description'    => "Referral reward for order #{$order->order_number}",
                'reference_id'   => $order->id,
                'reference_type' => Order::class,
                'balance_after'  => $newBalance,
            ]);

            $usage->update(['status' => 'credited']);
        });
    }

    /**
     * Cancel a pending referral usage (on order failure/cancellation).
     */
    public function cancelUsage(Order $order): void
    {
        ReferralUsage::where('order_id', $order->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
    }

    /**
     * Debit the user's wallet for an order payment and record the transaction.
     */
    public function debitWallet(User $user, float $amount, Order $order): void
    {
        if ($amount <= 0) {
            return;
        }

        $newBalance = max(0, (float) $user->wallet_balance - $amount);

        $user->update(['wallet_balance' => $newBalance]);

        WalletTransaction::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'type'           => 'debit',
            'source'         => 'order_payment',
            'description'    => "Wallet used for order #{$order->order_number}",
            'reference_id'   => $order->id,
            'reference_type' => Order::class,
            'balance_after'  => $newBalance,
        ]);
    }

    /**
     * Refund wallet credit back to a user (on order refund/cancellation).
     */
    public function refundWallet(User $user, float $amount, Order $order): void
    {
        if ($amount <= 0) {
            return;
        }

        $newBalance = (float) $user->wallet_balance + $amount;

        $user->update(['wallet_balance' => $newBalance]);

        WalletTransaction::create([
            'user_id'        => $user->id,
            'amount'         => $amount,
            'type'           => 'credit',
            'source'         => 'manual_credit',
            'description'    => "Wallet refund for order #{$order->order_number}",
            'reference_id'   => $order->id,
            'reference_type' => Order::class,
            'balance_after'  => $newBalance,
        ]);
    }

    /**
     * Generate a unique 8-character uppercase alphanumeric referral code.
     */
    public function generateUniqueCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }
}
