<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !$user->role) {
            SecurityLog::create([
                'user_id'     => $user?->id,
                'tipo_evento' => 'acceso_denegado',
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'endpoint'    => $request->path(),
                'metodo'      => $request->method(),
                'descripcion' => 'Acceso denegado: usuario sin rol definido',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        if (!in_array($user->role->nombre, $roles)) {
            SecurityLog::create([
                'user_id'     => $user->id,
                'tipo_evento' => 'acceso_denegado',
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'endpoint'    => $request->path(),
                'metodo'      => $request->method(),
                'descripcion' => 'Acceso denegado: ' . $user->email . ' (rol: ' . $user->role->nombre . ') intentó acceder a ' . $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para esta acción'
            ], 403);
        }

        return $next($request);
    }
}