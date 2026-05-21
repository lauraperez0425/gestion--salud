<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class PacienteController extends Controller
{
    #[OA\Get(
        path: '/api/pacientes',
        summary: 'Lista de pacientes',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index()
    {
        $pacientes = Paciente::with('tipoSangre:id,nombre')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de pacientes',
            'data' => $pacientes
        ]);
    }

    #[OA\Post(
        path: '/api/pacientes',
        summary: 'Registrar paciente',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'apellido', 'ci', 'seguro', 'estado'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2, nullable: true),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellido', type: 'string', example: 'García'),
                    new OA\Property(property: 'segundo_apellido', type: 'string', example: 'López'),
                    new OA\Property(property: 'ci', type: 'string', example: '1234567'),
                    new OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date', example: '1990-01-01'),
                    new OA\Property(property: 'telefono', type: 'string', example: '70000000'),
                    new OA\Property(property: 'direccion', type: 'string', example: 'Av. Principal 123'),
                    new OA\Property(property: 'estatura', type: 'number', format: 'float', example: 1.68, nullable: true),
                    new OA\Property(property: 'peso', type: 'number', format: 'float', example: 65.4, nullable: true),
                    new OA\Property(property: 'tipo_sangre_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'presion_arterial', type: 'string', example: '120/80', nullable: true),
                    new OA\Property(property: 'temperatura', type: 'number', format: 'float', example: 36.6, nullable: true),
                    new OA\Property(property: 'oxigeno_sangre', type: 'integer', example: 98, nullable: true),
                    new OA\Property(property: 'seguro', type: 'boolean', example: true),
                    new OA\Property(property: 'estado', type: 'string', example: 'Activo')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Paciente creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'nullable|exists:users,id',
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'ci'               => 'required|string|max:255|unique:pacientes,ci',
            'fecha_nacimiento' => 'nullable|date',
            'telefono'         => 'nullable|string|max:255',
            'direccion'        => 'nullable|string|max:255',
            'estatura'         => 'nullable|numeric|min:0|max:3',
            'peso'             => 'nullable|numeric|min:0|max:999.99',
            'tipo_sangre_id'   => 'nullable|exists:tipos_sangre,id',
            'presion_arterial' => 'nullable|string|max:20',
            'temperatura'      => 'nullable|numeric|min:30|max:45',
            'oxigeno_sangre'   => 'nullable|integer|min:0|max:100',
            'seguro'           => 'required|boolean',
            'estado'           => 'required|string|max:255',
        ]);

        $paciente = Paciente::create($validated);
        $paciente->load('tipoSangre:id,nombre');

        return response()->json([
            'success' => true,
            'message' => 'Paciente registrado correctamente',
            'data' => $paciente
        ], 201);
    }

    #[OA\Get(
        path: '/api/pacientes/{id}',
        summary: 'Detalle de paciente',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(string $id)
    {
        $paciente = Paciente::with('tipoSangre:id,nombre')->find($id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del paciente',
            'data' => $paciente
        ]);
    }

    #[OA\Put(
        path: '/api/pacientes/{id}',
        summary: 'Actualizar paciente',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'apellido', 'ci', 'seguro', 'estado'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2, nullable: true),
                    new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
                    new OA\Property(property: 'apellido', type: 'string', example: 'García'),
                    new OA\Property(property: 'segundo_apellido', type: 'string', example: 'López'),
                    new OA\Property(property: 'ci', type: 'string', example: '1234567'),
                    new OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date', example: '1990-01-01'),
                    new OA\Property(property: 'telefono', type: 'string', example: '70000000'),
                    new OA\Property(property: 'direccion', type: 'string', example: 'Av. Principal 123'),
                    new OA\Property(property: 'estatura', type: 'number', format: 'float', example: 1.68, nullable: true),
                    new OA\Property(property: 'peso', type: 'number', format: 'float', example: 65.4, nullable: true),
                    new OA\Property(property: 'tipo_sangre_id', type: 'integer', example: 1, nullable: true),
                    new OA\Property(property: 'presion_arterial', type: 'string', example: '120/80', nullable: true),
                    new OA\Property(property: 'temperatura', type: 'number', format: 'float', example: 36.6, nullable: true),
                    new OA\Property(property: 'oxigeno_sangre', type: 'integer', example: 98, nullable: true),
                    new OA\Property(property: 'seguro', type: 'boolean', example: true),
                    new OA\Property(property: 'estado', type: 'string', example: 'Activo')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Paciente actualizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function update(Request $request, string $id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'user_id'          => 'nullable|exists:users,id',
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'segundo_apellido' => 'nullable|string|max:255',
            'ci'               => [
                'required',
                'string',
                'max:255',
                Rule::unique('pacientes', 'ci')->ignore($paciente->id),
            ],
            'fecha_nacimiento' => 'nullable|date',
            'telefono'         => 'nullable|string|max:255',
            'direccion'        => 'nullable|string|max:255',
            'estatura'         => 'nullable|numeric|min:0|max:3',
            'peso'             => 'nullable|numeric|min:0|max:999.99',
            'tipo_sangre_id'   => 'nullable|exists:tipos_sangre,id',
            'presion_arterial' => 'nullable|string|max:20',
            'temperatura'      => 'nullable|numeric|min:30|max:45',
            'oxigeno_sangre'   => 'nullable|integer|min:0|max:100',
            'seguro'           => 'required|boolean',
            'estado'           => 'required|string|max:255',
        ]);

        $paciente->update($validated);
        $paciente->load('tipoSangre:id,nombre');

        return response()->json([
            'success' => true,
            'message' => 'Paciente actualizado correctamente',
            'data' => $paciente
        ]);
    }

    #[OA\Delete(
        path: '/api/pacientes/{id}',
        summary: 'Eliminar paciente',
        tags: ['Pacientes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paciente eliminado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(string $id)
    {
        $paciente = Paciente::find($id);

        if (!$paciente) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado'
            ], 404);
        }

        $paciente->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paciente eliminado correctamente'
        ]);
    }
}