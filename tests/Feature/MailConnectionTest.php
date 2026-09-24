<?php

/**
 * Every queued job dials the mail server itself.
 *
 * A queue worker runs for hours and Laravel caches the resolved mailer, so
 * without this each job after the first reuses one SMTP connection. Orders
 * arrive minutes apart; by then the provider has closed its side, and the next
 * job is refused at MAIL FROM with "451 4.4.2 Timeout waiting for data from
 * client". The retry a minute later opens a fresh connection and succeeds,
 * which is why it looked intermittent — it was really every order, delivered
 * one backoff late.
 *
 * Symfony's keepalive does not help: it pings with NOOP and only reconnects if
 * the NOOP throws, and this server answers the NOOP before refusing the
 * transaction after it.
 */

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Mail;

function startNextJob(): void
{
    // What the worker fires before it hands a job to its handler. The
    // framework's own context listener reads the payload, so the double has to
    // answer that much.
    $job = Mockery::mock(Job::class);
    $job->shouldReceive('payload')->andReturn([]);

    event(new JobProcessing('database', $job));
}

it('hands each job a mailer of its own', function () {
    $first = Mail::mailer('smtp');

    startNextJob();

    expect(Mail::mailer('smtp'))->not->toBe($first);
});

it('keeps reusing the mailer within a single job', function () {
    // Dropping it mid-job would mean a fresh connection per message, which is
    // not what this is for.
    startNextJob();

    $a = Mail::mailer('smtp');
    $b = Mail::mailer('smtp');

    expect($b)->toBe($a);
});

it('leaves the mailer working after it has been dropped', function () {
    Mail::mailer('smtp');
    startNextJob();

    expect(Mail::mailer('smtp'))->toBeInstanceOf(Illuminate\Mail\Mailer::class);
});
