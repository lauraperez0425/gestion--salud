<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RoleController extends Controller
{
    #[OA\Get(
        path: '/api/roles',
        summary: 'Lista de roles',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index()
    {
        $roles = Role::orderBy('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de roles',
            'data' => $roles
        ]);
    }

    #[OA\Post(
        path: '/api/roles',
        summary: 'Crear rol',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Medico'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Rol para médicos')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rol creado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'      => 'required|string|max:50|unique:roles,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $role = Role::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rol creado correctamente',
            'data'    => $role
        ], 201);
    }

    #[OA\Get(
        path: '/api/roles/{id}',
        summary: 'Detalle de rol',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function show(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del rol',
            'data'    => $role
        ]);
    }

    #[OA\Put(
        path: '/api/roles/{id}',
        summary: 'Actualizar rol',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Medico'),
                    new OA\Property(property: 'descripcion', type: 'string', example: 'Rol para médicos')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rol actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function update(Request $request, string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'nombre'      => 'required|string|max:50|unique:roles,nombre,' . $role->id,
            'descripcion' => 'nullable|string',
        ]);

        $role->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente',
            'data'    => $role
        ]);
    }

    #[OA\Delete(
        path: '/api/roles/{id}',
        summary: 'Eliminar rol',
        tags: ['Roles'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rol eliminado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(string $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado'
            ], 404);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente'
        ]);
    }
}