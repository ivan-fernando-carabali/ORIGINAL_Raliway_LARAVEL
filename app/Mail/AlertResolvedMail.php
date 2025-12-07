<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertResolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $alert;

    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function build()
    {
        return $this->subject('✅ Alerta Resuelta - ' . $this->alert->product->name)
                    ->view('emails.alert-resolved');
    }
}
