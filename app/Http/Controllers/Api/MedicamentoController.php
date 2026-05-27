<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicamento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class MedicamentoController extends Controller
{
    #[OA\Get(
        path: '/api/medicamentos',
        summary: 'Listar medicamentos',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, description: 'Buscar por nombre', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['activo', 'inactivo'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de medicamentos'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Medicamento::orderBy('nombre');

        if ($request->filled('search')) {
            $query->where('nombre', 'like', '%' . $request->string('search') . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de medicamentos',
            'data'    => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/medicamentos',
        summary: 'Registrar medicamento',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Paracetamol'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Analgésico y antipirético', nullable: true),
                    new OA\Property(property: 'stock', type: 'integer', example: 100),
                    new OA\Property(property: 'precio', type: 'number', format: 'float', example: 5.50, nullable: true),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo'], example: 'activo'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Medicamento creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:medicamentos,nombre',
            'descripcion' => 'nullable|string',
            'stock'       => 'nullable|integer|min:0',
            'precio'      => 'nullable|numeric|min:0',
            'estado'      => 'nullable|in:activo,inactivo',
        ]);

        $validated['stock'] = $validated['stock'] ?? 0;
        $validated['estado'] = $validated['estado'] ?? 'activo';

        $medicamento = Medicamento::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Medicamento registrado correctamente',
            'data'    => $medicamento,
        ], 201);
    }

    #[OA\Get(
        path: '/api/medicamentos/{id}',
        summary: 'Detalle de medicamento',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Medicamento encontrado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function show(int $id)
    {
        $medicamento = Medicamento::find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'message' => 'Medicamento no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del medicamento',
            'data'    => $medicamento,
        ]);
    }

    #[OA\Put(
        path: '/api/medicamentos/{id}',
        summary: 'Actualizar medicamento',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nombre', type: 'string'),
                    new OA\Property(property: 'descripcion', type: 'string', nullable: true),
                    new OA\Property(property: 'stock', type: 'integer'),
                    new OA\Property(property: 'precio', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'estado', type: 'string', enum: ['activo', 'inactivo']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Medicamento actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $medicamento = Medicamento::find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'message' => 'Medicamento no encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'nombre'      => ['sometimes', 'required', 'string', 'max:255', Rule::unique('medicamentos', 'nombre')->ignore($medicamento->id)],
            'descripcion' => 'nullable|string',
            'stock'       => 'sometimes|integer|min:0',
            'precio'      => 'nullable|numeric|min:0',
            'estado'      => 'sometimes|in:activo,inactivo',
        ]);

        $medicamento->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Medicamento actualizado correctamente',
            'data'    => $medicamento,
        ]);
    }

    #[OA\Delete(
        path: '/api/medicamentos/{id}',
        summary: 'Eliminar medicamento',
        tags: ['Farmacia'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Medicamento eliminado'),
            new OA\Response(response: 404, description: 'No encontrado'),
        ]
    )]
    public function destroy(int $id)
    {
        $medicamento = Medicamento::find($id);

        if (!$medicamento) {
            return response()->json([
                'success' => false,
                'message' => 'Medicamento no encontrado',
            ], 404);
        }

        $medicamento->delete();

        return response()->json([
            'success' => true,
            'message' => 'Medicamento eliminado correctamente',
        ]);
    }
}
