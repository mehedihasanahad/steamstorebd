<?php

namespace Database\Seeders\Demo;

use App\Models\Order;
use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Database\Seeder;

/**
 * What the referral dashboard renders: a code with earnings behind it, wallet
 * movements, referrals in each status, and withdrawal requests in each status.
 *
 * Without these the page shows four empty-state panels and proves nothing.
 */
class ReferralDemoSeeder extends Seeder
{
    public function run(): void
    {
        $shopper = User::where('email', OrderDemoSeeder::SHOPPER_EMAIL)->first();

        if ($shopper === null) {
            return;
        }

        $shopper->update(['referral_code' => 'AHAD1234', 'wallet_balance' => 260]);

        $this->referrals($shopper);
        $this->walletHistory($shopper);
        $this->withdrawals($shopper);
    }

    private function referrals(User $referrer): void
    {
        $friends = [
            ['Sabbir Alam', 'sabbir@example.test', 20, 30, 'credited'],
            ['Mim Akter', 'mim@example.test', 20, 30, 'credited'],
            ['Jubayer Khan', 'jubayer@example.test', 20, 30, 'pending'],
        ];

        foreach ($friends as $index => [$name, $email, $discount, $reward, $status]) {
            $friend = User::firstOrCreate(['email' => $email], [
                'name'     => $name,
                'password' => 'password', 'email_verified_at' => now(),
            ]);

            $order = Order::updateOrCreate(['order_number' => 'BD2026-2000' . ($index + 1)], [
                'user_id'               => $friend->id,
                'customer_name'         => $name,
                'customer_email'        => $email,
                'customer_phone'        => '01700000001',
                'subtotal_bdt'          => 1250,
                'total_bdt'             => 1250 - $discount,
                'status'                => $status === 'credited' ? 'completed' : 'pending_review',
                'referral_code_used'    => $referrer->referral_code,
                'referral_discount_bdt' => $discount,
            ]);

            ReferralUsage::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'referrer_id'    => $referrer->id,
                    'referee_id'     => $friend->id,
                    'discount_given' => $discount,
                    'owner_reward'   => $reward,
                    'status'         => $status,
                ],
            );
        }
    }

    /**
     * A running balance the page can show: two credits in, one spend and one
     * withdrawal out, landing on the shopper's current balance.
     */
    private function walletHistory(User $shopper): void
    {
        $movements = [
            ['credit', 30, 'referral_reward', 'Referral reward — Sabbir Alam', 30, 12],
            ['credit', 30, 'referral_reward', 'Referral reward — Mim Akter', 60, 9],
            ['debit', 50, 'order_payment', 'Used on order BD2026-100001', 10, 5],
            ['credit', 350, 'admin_adjustment', 'Goodwill credit from support', 360, 4],
            ['debit', 100, 'withdrawal', 'Withdrawal to bKash', 260, 2],
        ];

        foreach ($movements as [$type, $amount, $source, $description, $balanceAfter, $daysAgo]) {
            $transaction = WalletTransaction::updateOrCreate(
                ['user_id' => $shopper->id, 'description' => $description],
                [
                    'amount'        => $amount,
                    'type'          => $type,
                    'source'        => $source,
                    'balance_after' => $balanceAfter,
                ],
            );

            $transaction->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }
    }

    private function withdrawals(User $shopper): void
    {
        // transfer_type is an enum of exactly 'cashout' and 'send_money', and a
        // merchant account can only be cashed out.
        $requests = [
            [100, 'bkash', 'personal', 'send_money', '01711223344', 'paid', null, 2],
            [150, 'nagad', 'merchant', 'cashout', '01811223344', 'pending', null, 1],
            [500, 'bkash', 'personal', 'send_money', '01711223344', 'rejected', 'Balance was below the minimum at the time.', 6],
        ];

        foreach ($requests as [$amount, $method, $accountType, $transferType, $phone, $status, $note, $daysAgo]) {
            $request = WithdrawalRequest::updateOrCreate(
                ['user_id' => $shopper->id, 'amount' => $amount, 'method' => $method],
                [
                    'account_type'  => $accountType,
                    'transfer_type' => $transferType,
                    'phone_number'  => $phone,
                    'status'        => $status,
                    'admin_note'    => $note,
                    'processed_at'  => $status === 'pending' ? null : now()->subDays($daysAgo),
                ],
            );

            $request->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        }
    }
}
