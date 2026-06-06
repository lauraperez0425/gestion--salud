<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicamento;
use App\Models\MovimientoFarmacia;
use App\Models\RecetaMedica;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MovimientoFarmaciaController extends Controller
{
    #[OA\Get(
        path: '/api/movimientos-farmacia',
        summary: 'Listar movimientos de farmacia',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medicamento_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'receta_medica_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'farmaceutico_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tipo', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['entrada', 'salida'])),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de movimientos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $query = MovimientoFarmacia::with([
            'medicamento:id,nombre,stock,precio',
            'recetaMedica:id,cita_id,medicamento_id,estado_despacho',
            'farmaceutico:id,name,email',
        ])
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($request->filled('medicamento_id')) {
            $query->where('medicamento_id', $request->integer('medicamento_id'));
        }

        if ($request->filled('receta_medica_id')) {
            $query->where('receta_medica_id', $request->integer('receta_medica_id'));
        }

        if ($request->filled('farmaceutico_id')) {
            $query->where('farmaceutico_id', $request->integer('farmaceutico_id'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->string('tipo'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->string('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->string('fecha_hasta'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de movimientos de farmacia',
            'data'    => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/movimientos-farmacia',
        summary: 'Registrar movimiento de farmacia (entrada o salida)',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['medicamento_id', 'tipo', 'cantidad', 'fecha'],
                properties: [
                    new OA\Property(property: 'medicamento_id', type: 'integer', example: 1),
                    new OA\Property(property: 'receta_medica_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'farmaceutico_id', type: 'integer', example: 5, nullable: true),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['entrada', 'salida'], example: 'entrada'),
                    new OA\Property(property: 'cantidad', type: 'integer', example: 50),
                    new OA\Property(property: 'detalle', type: 'string', example: 'Compra mensual', nullable: true),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date', example: '2026-05-20'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Movimiento registrado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 409, description: 'Stock insuficiente'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicamento_id' => 'required|exists:medicamentos,id',
            'receta_medica_id' => 'nullable|exists:recetas_medicas,id',
            'farmaceutico_id' => 'nullable|exists:users,id',
            'tipo'           => 'required|in:entrada,salida',
            'cantidad'       => 'required|integer|min:1',
            'detalle'        => 'nullable|string',
            'fecha'          => 'required|date_format:Y-m-d',
        ]);

        if (!isset($validated['farmaceutico_id']) && $request->user()) {
            $validated['farmaceutico_id'] = $request->user()->id;
        }

        if (!empty($validated['receta_medica_id'])) {
            $receta = RecetaMedica::find($validated['receta_medica_id']);

            if (!$receta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Receta médica no encontrada',
                ], 422);
            }

            if ((int) $receta->medicamento_id !== (int) $validated['medicamento_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'La receta médica no corresponde al medicamento seleccionado',
                ], 422);
            }
        }

        $medicamento = Medicamento::find($validated['medicamento_id']);

        if ($validated['tipo'] === 'salida' && $medicamento->stock < $validated['cantidad']) {
            return response()->json([
                'success' => false,
                'message' => 'Stock insuficiente. Stock actual: ' . $medicamento->stock,
            ], 409);
        }

        $movimiento = MovimientoFarmacia::create($validated);

        // Actualizar stock del medicamento
        if ($validated['tipo'] === 'entrada') {
            $medicamento->increment('stock', $validated['cantidad']);
        } else {
            $medicamento->decrement('stock', $validated['cantidad']);
        }

        if (!empty($validated['receta_medica_id']) && $validated['tipo'] === 'salida') {
            RecetaMedica::where('id', $validated['receta_medica_id'])->update([
                'estado_despacho' => 'despachada',
            ]);
        }

        $movimiento->load([
            'medicamento:id,nombre,stock,precio',
            'recetaMedica:id,cita_id,medicamento_id,estado_despacho',
            'farmaceutico:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Movimiento registrado correctamente',
            'data'    => $movimiento,
        ], 201);
    }

    #[OA\Get(
        path: '/api/movimientos-farmacia/{id}',
        summary: 'Detalle de movimiento de farmacia',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Movimiento encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id)
    {
        $movimiento = MovimientoFarmacia::with([
            'medicamento:id,nombre,stock,precio',
            'recetaMedica:id,cita_id,medicamento_id,estado_despacho',
            'farmaceutico:id,name,email',
        ])->find($id);

        if (!$movimiento) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del movimiento',
            'data'    => $movimiento,
        ]);
    }

    #[OA\Get(
        path: '/api/medicamentos/{medicamento_id}/movimientos',
        summary: 'Movimientos de un medicamento específico',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medicamento_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Movimientos del medicamento'),
            new OA\Response(response: 404, description: 'Medicamento no encontrado'),
        ]
    )]
    public function porMedicamento(int $medicamento_id)
    {
        $medicamento = Medicamento::find($medicamento_id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'message' => 'Medicamento no encontrado',
            ], 404);
        }

        $movimientos = MovimientoFarmacia::where('medicamento_id', $medicamento_id)
            ->with([
                'recetaMedica:id,cita_id,medicamento_id,estado_despacho',
                'farmaceutico:id,name,email',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Movimientos del medicamento',
            'data'    => [
                'medicamento' => $medicamento->only(['id', 'nombre', 'stock', 'precio', 'estado']),
                'movimientos' => $movimientos,
            ],
        ]);
    }
}
