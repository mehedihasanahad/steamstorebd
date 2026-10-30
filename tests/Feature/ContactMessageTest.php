<?php

use App\Mail\AdminContactMessageMail;
use App\Models\ContactMessage;

it('offers support channels instead of a form', function () {
    $this->get(route('contact'))
        ->assertSuccessful()
        ->assertSee('Message our support team')
        ->assertDontSee('name="message"', false);
});

it('no longer accepts a posted message', function () {
    // The form was retired rather than throttled: an unauthenticated POST that
    // writes a row and queues an e-mail is the cheapest thing on the site to
    // flood, and support already runs through WhatsApp and e-mail.
    $this->post('/contact', [
        'name'    => 'Rahim Uddin',
        'email'   => 'rahim@example.com',
        'message' => 'My code shows as already redeemed.',
    ])->assertStatus(405);

    expect(ContactMessage::count())->toBe(0);
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
