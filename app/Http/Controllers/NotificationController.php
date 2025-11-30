<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Contract\Messaging;   // <--- IMPORTANTE
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificacionesController extends Controller
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    public function enviarNotificacion(Request $request)
    {
        // Validar que llegue el token del dispositivo
        $request->validate([
            'token' => 'required|string'
        ]);

        $deviceToken = $request->token;

        try {

            // Construir el mensaje
            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(
                    Notification::create(
                        '📢 Notificación desde Laravel',
                        'Esto es un mensaje enviado por API.'
                    )
                );

            // Enviar push
            $this->messaging->send($message);

            return response()->json([
                'status'    => 'success',
                'message'   => 'Notificación enviada correctamente.'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'status'    => 'error',
                'message'   => 'Error al enviar la notificación.',
                'details'   => $e->getMessage()
            ], 500);
        }
    }
}
