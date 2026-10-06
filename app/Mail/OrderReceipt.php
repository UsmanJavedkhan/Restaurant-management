<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public array $details) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Order '.$this->details['order_number'].' · '.str_replace('_', ' ', $this->details['status']));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.receipt');
    }
}
