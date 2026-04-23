<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de usuarios',
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $user = User::create($validated);
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data' => $user
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle del usuario',
            'data' => $user
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'rol_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->load('role');

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'data' => $user
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente'
        ]);
    }
}