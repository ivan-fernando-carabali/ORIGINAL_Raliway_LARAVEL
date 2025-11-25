<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Para API con Sanctum, mejor devolver JSON
        if (!$request->expectsJson()) {
            return route('login');  // Si tienes ruta para login
        }

        return null;
    }
}
