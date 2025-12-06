<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Lista de orígenes permitidos
        $allowedOrigins = [
            'http://localhost:4200',
            'http://localhost:8100',
            'http://127.0.0.1:4200',
            'https://smartinventori-production.up.railway.app',
        ];

        $origin = $request->header('Origin');
        
        // Verificar si el origen está permitido
        $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : 'http://localhost:4200';

        // 🔥 MANEJO DE PREFLIGHT (OPTIONS) - CRÍTICO PARA POST/PUT/DELETE
        if ($request->isMethod('OPTIONS')) {
            return response()->json(['message' => 'OK'], 200, [
                'Access-Control-Allow-Origin' => $allowOrigin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        // Procesar la petición normal
        $response = $next($request);

        // Agregar headers CORS a todas las respuestas
        return $response
            ->header('Access-Control-Allow-Origin', $allowOrigin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true');
    }
}