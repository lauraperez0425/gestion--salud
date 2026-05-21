<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HistoriaClinica;
use App\Models\Paciente;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class HistoriaClinicaController extends Controller
{
    #[OA\Get(
        path: '/api/historias-clinicas',
        summary: 'Listar todas las historias clínicas',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'paciente_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de historias clínicas'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(Request $request)
    {
        $query = HistoriaClinica::with('paciente:id,nombre,apellido,segundo_apellido,ci')
            ->orderByDesc('fecha');

        if ($request->filled('paciente_id')) {
            $query->where('paciente_id', $request->integer('paciente_id'));
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->string('fecha_desde')->toString());
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->string('fecha_hasta')->toString());
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de historias clínicas',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/historias-clinicas',
        summary: 'Crear una historia clínica',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['paciente_id', 'motivo_consulta', 'enfermedad_actual', 'diagnostico', 'fecha'],
                properties: [
                    new OA\Property(property: 'paciente_id', type: 'integer', description: 'ID del paciente'),
                    new OA\Property(property: 'motivo_consulta', type: 'string', description: 'Motivo de consulta'),
                    new OA\Property(property: 'enfermedad_actual', type: 'string', description: 'Enfermedad actual / síntomas'),
                    new OA\Property(property: 'peso', type: 'number', format: 'float', description: 'Peso en kg (opcional)'),
                    new OA\Property(property: 'talla', type: 'number', format: 'float', description: 'Talla en metros (opcional)'),
                    new OA\Property(property: 'presion_arterial', type: 'string', description: 'Presión arterial, ej. 120/80 (opcional)'),
                    new OA\Property(property: 'saturacion', type: 'integer', description: 'Saturación de oxígeno % (opcional)'),
                    new OA\Property(property: 'temperatura', type: 'number', format: 'float', description: 'Temperatura corporal (opcional)'),
                    new OA\Property(property: 'diagnostico', type: 'string', description: 'Diagnóstico'),
                    new OA\Property(property: 'tratamiento', type: 'string', description: 'Tratamiento indicado (opcional)'),
                    new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones adicionales (opcional)'),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date', description: 'Fecha del registro (Y-m-d)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Historia clínica creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id'   => 'required|exists:pacientes,id',
            'motivo_consulta' => 'required|string',
            'enfermedad_actual' => 'required|string',
            'peso' => 'nullable|numeric|min:0|max:999.99',
            'talla' => 'nullable|numeric|min:0|max:3',
            'presion_arterial' => 'nullable|string|max:20',
            'saturacion' => 'nullable|integer|min:0|max:100',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'diagnostico'   => 'required|string',
            'tratamiento'   => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fecha'         => 'required|date_format:Y-m-d',
        ]);

        $historia = HistoriaClinica::create($validated);
        $historia->load('paciente:id,nombre,apellido,segundo_apellido,ci');

        return response()->json([
            'success' => true,
            'message' => 'Historia clínica creada correctamente',
            'data'    => $historia,
        ], 201);
    }

    #[OA\Get(
        path: '/api/historias-clinicas/{id}',
        summary: 'Ver una historia clínica',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historia clínica encontrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ]
    )]
    public function show(int $id)
    {
        $historia = HistoriaClinica::with('paciente:id,nombre,apellido,segundo_apellido,ci')
            ->find($id);

        if (!$historia) {
            return response()->json([
                'success' => false,
                'message' => 'Historia clínica no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Historia clínica encontrada',
            'data'    => $historia,
        ]);
    }

    #[OA\Put(
        path: '/api/historias-clinicas/{id}',
        summary: 'Actualizar una historia clínica',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'motivo_consulta', type: 'string'),
                    new OA\Property(property: 'enfermedad_actual', type: 'string'),
                    new OA\Property(property: 'peso', type: 'number', format: 'float'),
                    new OA\Property(property: 'talla', type: 'number', format: 'float'),
                    new OA\Property(property: 'presion_arterial', type: 'string'),
                    new OA\Property(property: 'saturacion', type: 'integer'),
                    new OA\Property(property: 'temperatura', type: 'number', format: 'float'),
                    new OA\Property(property: 'diagnostico', type: 'string'),
                    new OA\Property(property: 'tratamiento', type: 'string'),
                    new OA\Property(property: 'observaciones', type: 'string'),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Historia clínica actualizada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrada'),
            new OA\Response(response: 422, description: 'Datos inválidos'),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $historia = HistoriaClinica::find($id);

        if (!$historia) {
            return response()->json([
                'success' => false,
                'message' => 'Historia clínica no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'motivo_consulta' => 'sometimes|required|string',
            'enfermedad_actual' => 'sometimes|required|string',
            'peso' => 'nullable|numeric|min:0|max:999.99',
            'talla' => 'nullable|numeric|min:0|max:3',
            'presion_arterial' => 'nullable|string|max:20',
            'saturacion' => 'nullable|integer|min:0|max:100',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'diagnostico'   => 'sometimes|required|string',
            'tratamiento'   => 'nullable|string',
            'observaciones' => 'nullable|string',
            'fecha'         => 'sometimes|required|date_format:Y-m-d',
        ]);

        $historia->update($validated);
        $historia->load('paciente:id,nombre,apellido,segundo_apellido,ci');

        return response()->json([
            'success' => true,
            'message' => 'Historia clínica actualizada correctamente',
            'data'    => $historia,
        ]);
    }

    #[OA\Delete(
        path: '/api/historias-clinicas/{id}',
        summary: 'Eliminar una historia clínica',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historia clínica eliminada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'No encontrada'),
        ]
    )]
    public function destroy(int $id)
    {
        $historia = HistoriaClinica::find($id);

        if (!$historia) {
            return response()->json([
                'success' => false,
                'message' => 'Historia clínica no encontrada',
            ], 404);
        }

        $historia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Historia clínica eliminada correctamente',
        ]);
    }

    #[OA\Get(
        path: '/api/pacientes/{paciente_id}/historias-clinicas',
        summary: 'Listar historias clínicas de un paciente específico',
        tags: ['Historias Clínicas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'paciente_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historias clínicas del paciente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Paciente no encontrado'),
        ]
    )]
    public function porPaciente(int $paciente_id)
    {
        $paciente = Paciente::find($paciente_id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }

        $historias = HistoriaClinica::where('paciente_id', $paciente_id)
            ->orderByDesc('fecha')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historias clínicas del paciente',
            'data'    => [
                'paciente'  => $paciente->only(['id', 'nombre', 'apellido', 'segundo_apellido', 'ci']),
                'historias' => $historias,
            ],
        ]);
    }
}
