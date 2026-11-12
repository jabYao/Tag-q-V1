<?php

namespace App\Mail;

use App\Models\PasswordChangeVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangeVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes = PasswordChangeVerification::CODE_TTL_MINUTES,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu código para cambiar tu contraseña — Tag q',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-change-verification-code',
        );
    }
}
