<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LogController extends Controller
{
    #[OA\Get(
        path: '/api/logs/seguridad',
        summary: 'Listar logs de seguridad',
        tags: ['Logs'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'tipo_evento', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', enum: [
                    'login_exitoso', 'login_fallido', 'logout',
                    'acceso_denegado', 'cambio_password_exitoso', 'cambio_password_fallido',
                    'usuario_creado', 'usuario_actualizado', 'usuario_eliminado'
                ])
            ),
            new OA\Parameter(name: 'user_id', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')
            ),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de logs de seguridad'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function logsSeguridad(Request $request)
    {
        $query = SecurityLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        if ($request->has('tipo_evento')) {
            $query->where('tipo_evento', $request->tipo_evento);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('fecha_desde')) {
            $query->whereDate('created_at', '>=', $request->fecha_desde);
        }

        if ($request->has('fecha_hasta')) {
            $query->whereDate('created_at', '<=', $request->fecha_hasta);
        }

        $logs = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}