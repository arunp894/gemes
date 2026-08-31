<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly ContactMessage $contactMessage) {}

    public function build(): self
    {
        return $this
            ->subject("New Contact Form Submission from {$this->contactMessage->name}")
            ->replyTo($this->contactMessage->email, $this->contactMessage->name)
            ->view('emails.contact.submission', ['contactMessage' => $this->contactMessage]);
    }
}
