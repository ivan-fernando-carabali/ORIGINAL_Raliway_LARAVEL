<?php

namespace App\Mail;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public Alert $alert;

    /**
     * Crear una nueva instancia del mensaje.
     */
    public function __construct(Alert $alert)
    {
        $this->alert = $alert;
    }

    /**
     * Construir el mensaje del correo.
     */
    public function build()
    {
        $productName = $this->alert->product->name ?? 'Producto desconocido';
        $alertType = strtoupper(str_replace('_', ' ', $this->alert->alert_type));
        $subject = "⚠️ Alerta de stock: {$productName} ({$alertType})";

        return $this->subject($subject)
                    ->view('emails.stock_alert')
                    ->with([
                        'productName' => $productName,
                        'message'     => $this->alert->message,
                        'date'        => $this->alert->date,
                        'alertType'   => $this->alert->alert_type,
                        'status'      => $this->alert->status,
                    ]);
    }
}
