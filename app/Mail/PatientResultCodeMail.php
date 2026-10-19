<?php

namespace App\Mail;

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PatientResultCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $client;
    public $code;
    public $secureLink;

    public function __construct($client, $code, $secureLink)
    {
        $this->client = $client;
        $this->code = $code;
        $this->secureLink = $secureLink;
    }

    public function build()
    {
        return $this->subject('Vos résultats de laboratoire sont disponibles')
            ->view('emails.patient-result-code');
    }
}
