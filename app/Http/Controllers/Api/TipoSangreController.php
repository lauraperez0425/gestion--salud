<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoSangre;
use OpenApi\Attributes as OA;

class TipoSangreController extends Controller
{
    #[OA\Get(
        path: '/api/tipos-sangre',
        summary: 'Lista de tipos de sangre',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Lista de tipos de sangre',
            'data' => TipoSangre::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }
}
