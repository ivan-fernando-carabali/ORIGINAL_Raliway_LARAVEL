<?php

namespace App\Http\Controllers;

use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeviceTokenController extends Controller
{
    /**
     * 📱 Registrar o actualizar token FCM del dispositivo
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:500',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $token = $request->input('token');
            $deviceType = $request->input('device_type', 'android');
            $deviceId = $request->input('device_id');

            // Buscar si ya existe un token para este usuario y dispositivo
            $deviceToken = DeviceToken::where('user_id', $user->id)
                ->where('token', $token)
                ->first();

            if ($deviceToken) {
                // Actualizar timestamp de uso
                $deviceToken->markAsUsed();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Token actualizado correctamente',
                    'data' => $deviceToken,
                ]);
            }

            // Crear nuevo token
            $deviceToken = DeviceToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'device_type' => $deviceType,
                'device_id' => $deviceId,
                'last_used_at' => now(),
            ]);

            Log::info("✅ Token FCM registrado para usuario {$user->id}: {$token}");

            return response()->json([
                'status' => 'success',
                'message' => 'Token registrado correctamente',
                'data' => $deviceToken,
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al registrar token FCM: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el token',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar token (logout o desinstalar app)
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token requerido',
            ], 422);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $token = $request->input('token');
            
            DeviceToken::where('user_id', $user->id)
                ->where('token', $token)
                ->delete();

            Log::info("🗑️ Token FCM eliminado para usuario {$user->id}");

            return response()->json([
                'status' => 'success',
                'message' => 'Token eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar token FCM: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el token',
            ], 500);
        }
    }
}










