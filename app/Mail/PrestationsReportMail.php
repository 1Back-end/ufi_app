<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PrestationsReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Rapport Journalier des Prestations - ' . $this->data['centre_name'] . ' - ' . $this->data['date'])
            ->view('emails.prestations_report');
    }
}
