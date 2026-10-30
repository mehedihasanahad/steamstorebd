<?php

use App\Jobs\SendEmailVerification;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('registration queues the confirm-your-address message rather than waiting on SMTP', function () {
    Queue::fake();

    $user = User::factory()->unverified()->create();
    $user->sendEmailVerificationNotification();

    Queue::assertPushed(SendEmailVerification::class, fn ($job) => $job->user->is($user));
});

test('the job sends it on the house template', function () {
    // Laravel's default builds a MailMessage from its own markdown
    // components; this one shares the layout every other message uses.
    Mail::fake();

    $user = User::factory()->unverified()->create();
    (new SendEmailVerification($user))->handle();

    Mail::assertSent(VerifyEmailMail::class, fn (VerifyEmailMail $mail) => $mail->hasTo($user->email));
});

test('the link inside that message really does verify the address', function () {
    // The signed link is built by the job rather than inherited from the
    // framework's notification, so this is the assertion that matters: if it
    // is signed or shaped differently, the route rejects it.
    Mail::fake();

    $user = User::factory()->unverified()->create();
    (new SendEmailVerification($user))->handle();

    $link = null;
    Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use (&$link) {
        $link = $mail->verificationUrl;

        return true;
    });

    $this->actingAs($user)->get($link);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('the job stays quiet if the address was confirmed while it waited', function () {
    // A second link sent from the resend button, or a slow queue, must not
    // post a confirmation to somebody who is already through.
    Mail::fake();

    $user = User::factory()->create();
    (new SendEmailVerification($user))->handle();

    Mail::assertNothingSent();
});

test('its expiry is counted from the send, not from the dispatch', function () {
    // A backed-up queue must not hand anyone a link that has already half
    // run out.
    Mail::fake();

    $user = User::factory()->unverified()->create();
    $job  = new SendEmailVerification($user);

    $this->travel(30)->minutes();
    $job->handle();

    Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use ($user) {
        parse_str((string) parse_url($mail->verificationUrl, PHP_URL_QUERY), $query);

        // Still the full hour ahead of the moment it was actually sent.
        return (int) $query['expires'] >= now()->addMinutes(59)->timestamp;
    });
});

test('the verification screen says which address to look in', function () {
    $user = User::factory()->unverified()->create(['email' => 'rahim@example.com']);

    $this->actingAs($user)->get('/verify-email')
        ->assertSuccessful()
        ->assertSee('rahim@example.com')
        ->assertSee('Check your inbox')
        ->assertSee('Confirm my email address')   // the wording on the button in the e-mail
        ->assertSee('1 hour');                    // matches auth.verification.expire
});

test('it never shows the shopper a raw session key', function () {
    // Breeze flashes `verification-link-sent` as a key, and the auth shell
    // used to echo it straight onto the page.
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->from('/verify-email')
        ->post(route('verification.send'))
        ->assertRedirect('/verify-email');

    $this->actingAs($user)->get('/verify-email')
        ->assertSuccessful()
        ->assertDontSee('verification-link-sent')
        ->assertSee('A fresh link is on its way');
});
