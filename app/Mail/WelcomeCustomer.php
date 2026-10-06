<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeCustomer extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $customerName, public string $restaurantName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to '.$this->restaurantName);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.customer.welcome');
    }
}
