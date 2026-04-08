<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotificationBase;

class ResetPasswordNotification extends ResetPasswordNotificationBase
{
    use Queueable;

    public function __construct($token)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable)
    {
        $url = config('app.url') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        // Ou mieux pour mobile : juste le token + instructions
        // $url = $this->token;  // et le frontend construit le lien ou envoie directement le token

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe - Fuelix')
            ->greeting('Bonjour,')
            ->line('Vous avez demandé à réinitialiser votre mot de passe.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.')
            ->line('Ce lien expire dans 60 minutes.')
            ->salutation('Cordialement, l\'équipe Fuelix');
    }
}
