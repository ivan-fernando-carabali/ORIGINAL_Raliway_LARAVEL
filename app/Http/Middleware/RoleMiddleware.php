<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // Si no está autenticado
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Si no tiene rol asignado o el rol no coincide
        if (!$user->role || $user->role->name !== $role) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
