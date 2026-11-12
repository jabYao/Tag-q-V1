<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Enlace de recuperación con la identidad de Tag q.
 *
 * Sustituye el correo genérico de Laravel para que el cliente reciba el
 * mismo tono y la misma marca que el resto de los correos transaccionales.
 */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $expiresInMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );

        return (new MailMessage)
            ->subject('Restablece tu contraseña de Tag q')
            ->view('emails.reset-password', [
                'url' => $this->resetUrl($notifiable),
                'name' => $notifiable->name,
                'expiresInMinutes' => $expiresInMinutes,
            ]);
    }
}
