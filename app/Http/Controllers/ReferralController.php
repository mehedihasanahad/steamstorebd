<?php

namespace App\Http\Controllers;

use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function __construct(private ReferralService $referralService) {}

    public function dashboard()
    {
        /** @var User $user */
        $user = Auth::user();

        // Lazy backfill for existing users who pre-date the referral feature.
        if (! $user->referral_code) {
            $user->update(['referral_code' => $this->referralService->generateUniqueCode()]);
        }

        $transactions       = WalletTransaction::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'tx_page');
        $usages             = ReferralUsage::where('referrer_id', $user->id)
            ->with(['order', 'referee'])
            ->latest()
            ->paginate(10, ['*'], 'ref_page');
        $withdrawals        = WithdrawalRequest::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'wd_page');
        $referralSettings   = $this->referralService->getSettings();

        return view('storefront.referral-dashboard', compact('user', 'transactions', 'usages', 'withdrawals', 'referralSettings'));
    }

    public function applyCode(Request $request)
    {
        $request->validate([
            'code'        => ['required', 'string', 'max:20'],
            'order_total' => ['required', 'numeric', 'min:0'],
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        $result = $this->referralService->validateCode(
            $request->string('code')->toString(),
            $user,
            (float) $request->input('order_total')
        );

        if ($result['valid']) {
            return response()->json([
                'success'  => true,
                'discount' => $result['discount'],
                'message'  => $result['message'],
            ]);
        }

        return response()->json(['success' => false, 'message' => $result['message']], 422);
    }

    public function requestWithdrawal(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $minAmount = (float) $this->referralService->getSettings()['min_withdrawal_amount'];

        $request->validate([
            'amount'       => ['required', 'numeric', 'min:' . $minAmount, 'max:' . (float) $user->wallet_balance],
            'method'       => ['required', 'in:bkash,nagad'],
            'account_type' => ['required', 'in:merchant,personal'],
            'phone_number' => ['required', 'string', 'regex:/^01[3-9][0-9]{8}$/'],
        ], [
            'amount.max'         => 'Withdrawal amount cannot exceed your wallet balance.',
            'amount.min'         => 'Minimum withdrawal amount is ৳' . number_format($minAmount, 0) . '.',
            'phone_number.regex' => 'Enter a valid Bangladeshi mobile number (e.g. 01XXXXXXXXX).',
        ]);

        $transferType = $request->input('account_type') === 'merchant' ? 'cashout' : 'send_money';
        $amount       = (float) $request->input('amount');

        DB::transaction(function () use ($user, $request, $amount, $transferType) {
            $newBalance = (float) $user->wallet_balance - $amount;

            $user->update(['wallet_balance' => $newBalance]);

            WalletTransaction::create([
                'user_id'      => $user->id,
                'amount'       => $amount,
                'type'         => 'debit',
                'source'       => 'withdrawal',
                'description'  => 'Withdrawal via ' . strtoupper($request->input('method')),
                'balance_after' => $newBalance,
            ]);

            WithdrawalRequest::create([
                'user_id'      => $user->id,
                'amount'       => $amount,
                'method'       => $request->input('method'),
                'account_type' => $request->input('account_type'),
                'transfer_type' => $transferType,
                'phone_number' => $request->input('phone_number'),
                'status'       => 'pending',
            ]);
        });

        return back()->with('withdrawal_success', 'Your withdrawal request of ৳' . number_format($amount, 0) . ' has been submitted. We will process it shortly.');
    }
}
