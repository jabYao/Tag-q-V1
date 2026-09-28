<?php

namespace App\Mail;

use App\Models\RegistrationVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes = RegistrationVerification::CODE_TTL_MINUTES,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código de verificación de Tag q',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-verification-code',
        );
    }
}
