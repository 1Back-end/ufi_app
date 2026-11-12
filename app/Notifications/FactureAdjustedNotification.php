<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FactureAdjustedNotification extends Notification
{
    use Queueable;

    protected $facture;
    protected $user;

    public function __construct($facture, $user = null)
    {
        $this->facture = $facture;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $userName = $this->user ? ($this->user->nom_utilisateur ?? '') : '';

        return (new MailMessage)
            ->subject('[Notification] Ajustement de la facture n° ' . $this->facture->id)
            ->view('emails.facture_adjusted', [
                'facture' => $this->facture,
                'userName' => $userName
            ]);
    }

    public function toArray($notifiable)
    {
        $userName = $this->user ? ($this->user->nom_utilisateur ?? '') : '';

        return [
            'facture_id' => $this->facture->id,
            'prestation_id' => $this->facture->prestation_id,
            'message' => "La facture n° {$this->facture->id} a été ajustée par {$userName}.",
            'adjusted_at' => now()->toDateTimeString(),
        ];
    }
}
