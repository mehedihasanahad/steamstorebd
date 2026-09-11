<?php

namespace App\Jobs;

use App\Mail\ResellerApplicationReceivedMail;
use App\Models\ResellerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendResellerApplicationReceivedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public ResellerApplication $application) {}

    public function handle(): void
    {
        $application = $this->application->fresh();

        if ($application === null) {
            return;
        }

        Mail::to($application->email)->send(new ResellerApplicationReceivedMail($application));
    }
}
