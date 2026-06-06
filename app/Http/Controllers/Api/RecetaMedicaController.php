<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RecetaMedica;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RecetaMedicaController extends Controller
{
    #[OA\Get(
        path: '/api/recetas-medicas',
        summary: 'Listar recetas médicas',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'cita_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'medicamento_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'paciente_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'medico_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'estado_despacho', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pendiente', 'parcial', 'despachada'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de recetas médicas'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $query = RecetaMedica::with([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ])->latest();

        if ($request->filled('cita_id')) {
            $query->where('cita_id', $request->integer('cita_id'));
        }

        if ($request->filled('medicamento_id')) {
            $query->where('medicamento_id', $request->integer('medicamento_id'));
        }

        if ($request->filled('paciente_id')) {
            $query->whereHas('cita', function ($q) use ($request) {
                $q->where('paciente_id', $request->integer('paciente_id'));
            });
        }

        if ($request->filled('medico_id')) {
            $query->whereHas('cita', function ($q) use ($request) {
                $q->where('medico_id', $request->integer('medico_id'));
            });
        }

        if ($request->filled('estado_despacho')) {
            $query->where('estado_despacho', $request->string('estado_despacho')->toString());
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de recetas médicas',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/recetas-medicas',
        summary: 'Registrar receta médica',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cita_id', 'medicamento_id'],
                properties: [
                    new OA\Property(property: 'cita_id', type: 'integer', example: 1),
                    new OA\Property(property: 'medicamento_id', type: 'integer', example: 2),
                    new OA\Property(property: 'dosis', type: 'string', example: '500 mg', nullable: true),
                    new OA\Property(property: 'frecuencia', type: 'string', example: 'Cada 8 horas', nullable: true),
                    new OA\Property(property: 'duracion', type: 'string', example: '7 dias', nullable: true),
                    new OA\Property(property: 'indicaciones', type: 'string', example: 'Tomar despues de las comidas', nullable: true),
                    new OA\Property(property: 'estado_despacho', type: 'string', enum: ['pendiente', 'parcial', 'despachada'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Receta médica creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cita_id' => 'required|exists:citas,id',
            'medicamento_id' => 'required|exists:medicamentos,id',
            'dosis' => 'nullable|string|max:100',
            'frecuencia' => 'nullable|string|max:100',
            'duracion' => 'nullable|string|max:100',
            'indicaciones' => 'nullable|string',
            'estado_despacho' => 'nullable|in:pendiente,parcial,despachada',
        ]);

        $receta = RecetaMedica::create($validated);
        $receta->load([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receta médica registrada correctamente',
            'data' => $receta,
        ], 201);
    }

    #[OA\Get(
        path: '/api/recetas-medicas/{id}',
        summary: 'Detalle de receta médica',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Receta médica encontrada'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ]
    )]
    public function show(int $id)
    {
        $receta = RecetaMedica::with([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ])->find($id);

        if (!$receta) {
            return response()->json([
                'success' => false,
                'message' => 'Receta médica no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de la receta médica',
            'data' => $receta,
        ]);
    }

    #[OA\Get(
        path: '/api/pacientes/{paciente_id}/recetas-medicas',
        summary: 'Listar recetas médicas por paciente',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'paciente_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de recetas médicas del paciente'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function porPaciente(int $paciente_id)
    {
        $recetas = RecetaMedica::with([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ])
            ->whereHas('cita', function ($query) use ($paciente_id) {
                $query->where('paciente_id', $paciente_id);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Recetas médicas del paciente',
            'data' => $recetas,
        ]);
    }

    #[OA\Get(
        path: '/api/medicos/{medico_id}/recetas-medicas',
        summary: 'Listar recetas médicas por médico',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de recetas médicas del médico'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function porMedico(int $medico_id)
    {
        $recetas = RecetaMedica::with([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ])
            ->whereHas('cita', function ($query) use ($medico_id) {
                $query->where('medico_id', $medico_id);
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Recetas médicas del médico',
            'data' => $recetas,
        ]);
    }

    #[OA\Put(
        path: '/api/recetas-medicas/{id}',
        summary: 'Actualizar receta médica',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cita_id', type: 'integer'),
                    new OA\Property(property: 'medicamento_id', type: 'integer'),
                    new OA\Property(property: 'dosis', type: 'string', nullable: true),
                    new OA\Property(property: 'frecuencia', type: 'string', nullable: true),
                    new OA\Property(property: 'duracion', type: 'string', nullable: true),
                    new OA\Property(property: 'indicaciones', type: 'string', nullable: true),
                    new OA\Property(property: 'estado_despacho', type: 'string', enum: ['pendiente', 'parcial', 'despachada']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Receta médica actualizada'),
            new OA\Response(response: 404, description: 'No encontrada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $receta = RecetaMedica::find($id);

        if (!$receta) {
            return response()->json([
                'success' => false,
                'message' => 'Receta médica no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'cita_id' => 'sometimes|required|exists:citas,id',
            'medicamento_id' => 'sometimes|required|exists:medicamentos,id',
            'dosis' => 'nullable|string|max:100',
            'frecuencia' => 'nullable|string|max:100',
            'duracion' => 'nullable|string|max:100',
            'indicaciones' => 'nullable|string',
            'estado_despacho' => 'sometimes|required|in:pendiente,parcial,despachada',
        ]);

        $receta->update($validated);
        $receta->load([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receta médica actualizada correctamente',
            'data' => $receta,
        ]);
    }

    #[OA\Delete(
        path: '/api/recetas-medicas/{id}',
        summary: 'Eliminar receta médica',
        tags: ['Recetas Médicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Receta médica eliminada'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ]
    )]
    public function destroy(int $id)
    {
        $receta = RecetaMedica::find($id);

        if (!$receta) {
            return response()->json([
                'success' => false,
                'message' => 'Receta médica no encontrada',
            ], 404);
        }

        $receta->delete();

        return response()->json([
            'success' => true,
            'message' => 'Receta médica eliminada correctamente',
        ]);
    }
}