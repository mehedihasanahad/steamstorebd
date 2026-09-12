<?php

use App\Jobs\SendAdminContactMessageEmail;
use App\Mail\AdminContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Queue;

it('stores the message and notifies the admin', function () {
    Queue::fake();

    $this->from(route('contact'))
        ->post(route('contact.submit'), [
            'name'    => '  Rahim Uddin ',
            'email'   => 'Rahim@Example.com',
            'message' => 'My code shows as already redeemed.',
        ])
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success');

    $contactMessage = ContactMessage::sole();

    expect($contactMessage)
        ->name->toBe('Rahim Uddin')
        ->email->toBe('rahim@example.com')
        ->message->toBe('My code shows as already redeemed.')
        ->read_at->toBeNull();

    Queue::assertPushed(SendAdminContactMessageEmail::class, fn ($job) => $job->contactMessage->is($contactMessage));
});

it('rejects an incomplete message without storing it', function () {
    Queue::fake();

    $this->from(route('contact'))
        ->post(route('contact.submit'), ['name' => 'Rahim', 'email' => 'not-an-email'])
        ->assertRedirect(route('contact'))
        ->assertSessionHasErrors(['email', 'message']);

    expect(ContactMessage::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('builds an admin email the team can reply to', function () {
    $contactMessage = ContactMessage::create([
        'name'       => 'Rahim Uddin',
        'email'      => 'rahim@example.com',
        'message'    => 'My code shows as already redeemed.',
        'ip_address' => '203.0.113.7',
    ]);

    $mail = new AdminContactMessageMail($contactMessage);

    $mail->assertHasReplyTo('rahim@example.com', 'Rahim Uddin');
    $mail->assertHasSubject('New Contact Message — Rahim Uddin');
    $mail->assertSeeInHtml('My code shows as already redeemed.');
    $mail->assertSeeInText('203.0.113.7');
});
