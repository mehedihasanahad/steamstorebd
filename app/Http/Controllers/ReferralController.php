<?php

namespace App\Http\Controllers;

use App\Models\ReferralUsage;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $transactions     = WalletTransaction::where('user_id', $user->id)->latest()->paginate(10, ['*'], 'tx_page');
        $usages           = ReferralUsage::where('referrer_id', $user->id)
            ->with(['order', 'referee'])
            ->latest()
            ->paginate(10, ['*'], 'ref_page');
        $referralSettings = $this->referralService->getSettings();

        return view('storefront.referral-dashboard', compact('user', 'transactions', 'usages', 'referralSettings'));
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
}
