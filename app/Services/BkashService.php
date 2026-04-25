<?php

namespace App\Services;

use App\Models\BkashPayment;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Karim007\LaravelBkashTokenize\Facade\BkashPaymentTokenize;

class BkashService
{
    public function initiatePayment(Order $order): array
    {
        $amount = number_format($order->total_bdt, 2, '.', '');

        $payload = json_encode([
            'mode'                  => '0011',
            'payerReference'        => $order->order_number,
            'callbackURL'           => config('bkash.callbackURL'),
            'amount'                => $amount,
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $order->order_number,
        ]);

        $response = BkashPaymentTokenize::cPayment($payload);

        if (isset($response['bkashURL'])) {
            BkashPayment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'payment_id' => $response['paymentID'] ?? null,
                    'amount' => $order->total_bdt,
                    'status' => 'initiated',
                    'bkash_response' => $response,
                ]
            );

            $order->update(['status' => 'payment_initiated']);
        }

        return $response;
    }

    public function executePayment(string $paymentId): array
    {
        return BkashPaymentTokenize::executePayment($paymentId);
    }

    public function queryPayment(string $paymentId): array
    {
        return BkashPaymentTokenize::queryPayment($paymentId);
    }
}
