<?php

namespace App\Jobs;

use App\Mail\AdminNewOrderMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminNewOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Order $order) {}

    public function handle(): void
    {
        $order  = $this->order->fresh(['items.giftCard']);
        $adminEmail = config('mail.admin_notification_email', config('mail.from.address'));
        Mail::to($adminEmail)->send(new AdminNewOrderMail($order));
    }
}
