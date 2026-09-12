<?php

namespace App\Jobs;

use App\Mail\AdminContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminContactMessageEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public ContactMessage $contactMessage) {}

    public function handle(): void
    {
        $contactMessage = $this->contactMessage->fresh();

        if ($contactMessage === null) {
            return;
        }

        Mail::to(config('mail.admin_notification_email', config('mail.from.address')))->send(new AdminContactMessageMail($contactMessage));
    }
}
