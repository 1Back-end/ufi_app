<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PatientResultSecuredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $client;
    public $codePin;
    public $pdfPath;

    public function __construct($client, $codePin, $pdfPath)
    {
        $this->client = $client;
        $this->codePin = $codePin;
        $this->pdfPath = $pdfPath;
    }

    public function build()
    {
        return $this->subject('Vos résultats d\'analyses médicales - GT LABO')
            ->view('emails.patient-pdf-secured')
            ->attach($this->pdfPath, [
                'as' => 'Resultats_Medical_GT_LABO.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}
