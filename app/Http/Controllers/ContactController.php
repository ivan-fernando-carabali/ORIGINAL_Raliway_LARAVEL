<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * 📧 Enviar correo de contacto
     */
    public function sendContactEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'empresa' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'mensaje' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Construir plantilla de correo
            $emailTemplate = $this->buildEmailTemplate($data);

            // Enviar correo a smartinventory685@gmail.com
            Mail::send([], [], function ($message) use ($data, $emailTemplate) {
                $message->to('smartinventory685@gmail.com')
                    ->subject('Nueva consulta de contacto - SmartInventory')
                    ->replyTo($data['correo'], $data['nombre'])
                    ->html($emailTemplate);
            });

            Log::info('📧 Correo de contacto enviado desde: ' . $data['correo']);

            return response()->json([
                'status' => 'success',
                'message' => '¡Gracias por contactarnos! Te responderemos pronto.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al enviar correo de contacto: ' . $e->getMessage());
            Log::error('❌ Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al enviar el mensaje. Por favor, intenta nuevamente más tarde.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * 📝 Construir plantilla de correo HTML
     */
    private function buildEmailTemplate(array $data): string
    {
        $empresa = $data['empresa'] ?? 'No especificada';
        $telefono = $data['telefono'] ?? 'No especificado';

        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #555; }
                .value { color: #333; }
                .message-box { background-color: white; padding: 15px; border-left: 4px solid #4CAF50; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Nueva Consulta de Contacto</h1>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>Nombre completo:</div>
                        <div class='value'>{$data['nombre']}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Correo electrónico:</div>
                        <div class='value'>{$data['correo']}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Empresa:</div>
                        <div class='value'>{$empresa}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Teléfono:</div>
                        <div class='value'>{$telefono}</div>
                    </div>
                    <div class='field'>
                        <div class='label'>Mensaje:</div>
                        <div class='message-box'>" . nl2br(e($data['mensaje'])) . "</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

