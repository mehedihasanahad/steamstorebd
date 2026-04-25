<?php

namespace App\Http\Controllers;

use App\Models\BkashPayment;
use App\Models\Order;
use App\Services\BkashService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class BkashController extends Controller
{
    public function __construct(
        private BkashService $bkashService,
        private OrderService $orderService,
    ) {}

    public function callback(Request $request)
    {
        $paymentId = $request->get('paymentID');
        $status    = $request->get('status');

        if (! $paymentId || $status === 'cancel') {
            $order = $this->getCurrentOrder();
            if ($order) {
                $this->orderService->failOrder($order);
            }
            return redirect()->route('checkout.failed');
        }

        if ($status === 'failure') {
            $order = $this->getCurrentOrder();
            if ($order) {
                $this->orderService->failOrder($order);
            }
            return redirect()->route('checkout.failed');
        }

        try {
            $executeResponse = $this->bkashService->executePayment($paymentId);

            if (($executeResponse['statusCode'] ?? '') !== '0000') {
                Log::warning('bKash execute failed', $executeResponse);
                $order = $this->getCurrentOrder();
                if ($order) {
                    $this->orderService->failOrder($order);
                }
                return redirect()->route('checkout.failed');
            }

            $bkashPayment = BkashPayment::where('payment_id', $paymentId)->first();

            if (! $bkashPayment) {
                Log::error('bKash payment record not found for paymentID: ' . $paymentId);
                return redirect()->route('checkout.failed');
            }

            $order = Order::with('items')->find($bkashPayment->order_id);

            if (! $order) {
                return redirect()->route('checkout.failed');
            }

            // Verify amount
            $paidAmount = (float) ($executeResponse['amount'] ?? 0);
            if (abs($paidAmount - (float) $order->total_bdt) > 0.01) {
                Log::error('bKash amount mismatch', [
                    'expected' => $order->total_bdt,
                    'received' => $paidAmount,
                ]);
                $this->orderService->failOrder($order);
                return redirect()->route('checkout.failed');
            }

            $this->orderService->completeOrder($order, $executeResponse);
            Session::forget('current_order_id');

            return redirect()->route('checkout.success', $order->order_number);

        } catch (\Exception $e) {
            Log::error('bKash callback error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('checkout.failed');
        }
    }

    private function getCurrentOrder(): ?Order
    {
        $orderId = Session::get('current_order_id');
        return $orderId ? Order::with('items')->find($orderId) : null;
    }
}
