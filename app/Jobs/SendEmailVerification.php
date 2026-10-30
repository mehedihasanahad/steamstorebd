<?php

namespace App\Jobs;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

/**
 * The confirm-your-address message, off the registration request.
 *
 * Sending inline made the shopper wait on SMTP before the page came back,
 * which on a slow handshake is the first thing they ever see this shop do.
 *
 * The signed link is built here rather than at dispatch on purpose: its
 * expiry should start when the message actually goes out, so a backed-up
 * queue shortens nobody's window to confirm.
 */
class SendEmailVerification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $user = $this->user->fresh();

        // Between dispatch and delivery the account can be deleted, or the
        // address confirmed from a link we sent earlier.
        if ($user === null || $user->hasVerifiedEmail()) {
            return;
        }

        $minutes = (int) config('auth.verification.expire', 60);

        Mail::to($user->getEmailForVerification())->send(new VerifyEmailMail(
            $user,
            URL::temporarySignedRoute('verification.verify', now()->addMinutes($minutes), [
                'id'   => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]),
            $minutes,
        ));
    }
}
