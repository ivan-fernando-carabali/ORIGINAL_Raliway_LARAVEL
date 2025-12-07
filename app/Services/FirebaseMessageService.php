<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

class FirebaseMessageService
{
    /**
     * Enviar notificación a un token específico
     */
    public function sendToToken(string $deviceToken, string $title, string $body)
    {
        try {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification(
                    Notification::create($title, $body)
                );

            return $messaging->send($message);
        } catch (MessagingException $e) {
            Log::error('❌ Error enviando notificación FCM: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Enviar notificación a todos los tokens de un usuario
     */
    public function sendToUser(int $userId, string $title, string $body): array
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                Log::warning("⚠️ Usuario {$userId} no encontrado para enviar notificación");
                return ['success' => 0, 'failed' => 0];
            }

            // Verificar si el usuario tiene la relación deviceTokens
            if (!method_exists($user, 'deviceTokens')) {
                Log::info("ℹ️ Usuario {$userId} no tiene relación deviceTokens disponible");
                return ['success' => 0, 'failed' => 0];
            }

            $tokens = $user->deviceTokens()->pluck('token')->toArray();
            
            if (empty($tokens)) {
                Log::info("ℹ️ Usuario {$userId} no tiene tokens FCM registrados");
                return ['success' => 0, 'failed' => 0];
            }
        } catch (\Throwable $e) {
            Log::warning("⚠️ Error obteniendo tokens del usuario {$userId}: " . $e->getMessage());
            return ['success' => 0, 'failed' => 0];
        }

        return $this->sendToMultipleTokens($tokens, $title, $body);
    }

    /**
     * Enviar notificación a múltiples tokens
     */
    public function sendToMultipleTokens(array $tokens, string $title, string $body): array
    {
        if (empty($tokens)) {
            return ['success' => 0, 'failed' => 0];
        }

        $success = 0;
        $failed = 0;
        $messaging = app('firebase.messaging');

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withNotification(
                        Notification::create($title, $body)
                    );

                $messaging->send($message);
                $success++;

                // Actualizar timestamp del token
                DeviceToken::where('token', $token)->update(['last_used_at' => now()]);

            } catch (MessagingException $e) {
                $failed++;
                Log::warning("⚠️ Error enviando a token {$token}: " . $e->getMessage());
                
                // Si el token es inválido, eliminarlo
                if (str_contains($e->getMessage(), 'invalid') || str_contains($e->getMessage(), 'not found')) {
                    DeviceToken::where('token', $token)->delete();
                    Log::info("🗑️ Token inválido eliminado: {$token}");
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("❌ Error inesperado enviando notificación: " . $e->getMessage());
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Enviar notificación a todos los usuarios (broadcast)
     */
    public function sendToAllUsers(string $title, string $body): array
    {
        $tokens = DeviceToken::pluck('token')->toArray();
        return $this->sendToMultipleTokens($tokens, $title, $body);
    }
}