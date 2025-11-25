<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SmsService
{
    /**
     * Enviar SMS usando Twilio o servicio similar
     *
     * @param string $to Número de teléfono destino (formato: +1234567890)
     * @param string $message Mensaje a enviar
     * @return bool True si se envió correctamente, False en caso contrario
     */
    public function sendSms(string $to, string $message): bool
    {
        try {
            // Limpiar número de teléfono
            $phone = $this->cleanPhoneNumber($to);

            if (!$phone) {
                Log::warning("Número de teléfono inválido: {$to}");
                return false;
            }

            // Obtener configuración de SMS desde .env
            $provider = config('services.sms.provider', 'twilio');

            switch ($provider) {
                case 'twilio':
                    return $this->sendViaTwilio($phone, $message);
                case 'nexmo':
                    return $this->sendViaNexmo($phone, $message);
                default:
                    // Para desarrollo, solo loguear
                    Log::info("SMS (Simulado) enviado a {$phone}: {$message}");
                    return true;
            }
        } catch (\Exception $e) {
            Log::error("Error enviando SMS: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Limpiar y formatear número de teléfono
     */
    private function cleanPhoneNumber(string $phone): ?string
    {
        // Remover espacios, guiones, paréntesis
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Si no tiene código de país, agregar +57 (Colombia) o +1 (EEUU) según configuración
        if (!str_starts_with($cleaned, '+')) {
            $countryCode = config('services.sms.default_country_code', '+57');
            $cleaned = $countryCode . $cleaned;
        }

        // Validar formato básico
        if (preg_match('/^\+[1-9]\d{1,14}$/', $cleaned)) {
            return $cleaned;
        }

        return null;
    }

    /**
     * Enviar SMS usando Twilio
     */
    private function sendViaTwilio(string $to, string $message): bool
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from_number');

        if (!$accountSid || !$authToken || !$fromNumber) {
            Log::warning("Twilio no configurado correctamente");
            return false;
        }

        try {
            $response = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                    'From' => $fromNumber,
                    'To' => $to,
                    'Body' => $message
                ]);

            if ($response->successful()) {
                Log::info("SMS enviado exitosamente a {$to} via Twilio");
                return true;
            } else {
                Log::error("Error enviando SMS via Twilio: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Excepción enviando SMS via Twilio: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Enviar SMS usando Nexmo/Vonage
     */
    private function sendViaNexmo(string $to, string $message): bool
    {
        $apiKey = config('services.nexmo.key');
        $apiSecret = config('services.nexmo.secret');
        $from = config('services.nexmo.from');

        if (!$apiKey || !$apiSecret || !$from) {
            Log::warning("Nexmo no configurado correctamente");
            return false;
        }

        try {
            $response = Http::post('https://rest.nexmo.com/sms/json', [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'to' => $to,
                'from' => $from,
                'text' => $message
            ]);

            if ($response->successful() && $response->json('messages.0.status') === '0') {
                Log::info("SMS enviado exitosamente a {$to} via Nexmo");
                return true;
            } else {
                Log::error("Error enviando SMS via Nexmo: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Excepción enviando SMS via Nexmo: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Generar mensaje de SMS para orden de reabastecimiento
     */
    public function generateOrderMessage($order): string
    {
        $productName = $order->product->name ?? 'Producto';
        $quantity = $order->quantity ?? 0;

        return "📦 SOLICITUD DE REABASTECIMIENTO\n\n" .
               "Producto: {$productName}\n" .
               "Cantidad: {$quantity} unidades\n\n" .
               "Por favor, confirme disponibilidad.\n" .
               "Smart Inventory System";
    }

    /**
     * Generar mensaje de SMS para alerta de stock
     */
    public function generateAlertMessage($alert): string
    {
        $productName = $alert->product->name ?? 'Producto';
        $alertType = $alert->alert_type === 'sin_stock' ? 'SIN STOCK' : 'STOCK BAJO';

        return "⚠️ ALERTA DE INVENTARIO\n\n" .
               "Producto: {$productName}\n" .
               "Estado: {$alertType}\n\n" .
               "Se requiere reabastecimiento urgente.\n" .
               "Smart Inventory System";
    }
}









