<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        // The subject quotes the same delivery time the body does — the one
        // an admin set on the card — and quotes none at all when the lines
        // disagree or nobody has committed to one.
        $eta = $this->order->sharedDeliveryEta();

        return new Envelope(
            subject: 'Order #' . $this->order->order_number . ' Received — Under Review'
                . ($eta ? ' (' . $eta . ')' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-pending',
            text: 'emails.order-pending-plain',
        );
    }
}
