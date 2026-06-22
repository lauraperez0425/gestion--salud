<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/login',
        summary: 'Iniciar sesión',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@admin.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'password')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login correcto'),
            new OA\Response(response: 401, description: 'Credenciales incorrectas'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function login(Request $request)
    {
        $credentials = $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required|string',
            ],
            [
                'email.required' => 'El correo es obligatorio',
                'email.email' => 'El correo no tiene un formato válido',
                'password.required' => 'La contraseña es obligatoria',
                'password.string' => 'La contraseña debe ser texto válido',
            ]
        );

        if (!Auth::attempt($credentials)) {
            // Log login fallido
            SecurityLog::create([
                'user_id'     => null,
                'tipo_evento' => 'login_fallido',
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'endpoint'    => $request->path(),
                'metodo'      => $request->method(),
                'descripcion' => 'Intento de login fallido para: ' . $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $user = Auth::user()->load('role');
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log login exitoso
        SecurityLog::create([
            'user_id'     => $user->id,
            'tipo_evento' => 'login_exitoso',
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'endpoint'    => $request->path(),
            'metodo'      => $request->method(),
            'descripcion' => 'Login exitoso: ' . $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login correcto',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Usuario autenticado',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function me(Request $request)
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Datos del usuario autenticado',
            'data' => $request->user()->load('role')
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Cerrar sesión',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$user || !$token) {
            return response()->json([
                'success' => false,
                'message' => 'No hay sesión activa'
            ], 401);
        }

        // Log logout
        SecurityLog::create([
            'user_id'     => $user->id,
            'tipo_evento' => 'logout',
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'endpoint'    => $request->path(),
            'metodo'      => $request->method(),
            'descripcion' => 'Logout: ' . $user->email,
        ]);

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    #[OA\Post(
        path: '/api/change-password',
        summary: 'Cambiar contraseña del usuario autenticado',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password', 'new_password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', example: 'password'),
                    new OA\Property(property: 'new_password', type: 'string', example: 'nuevaPassword123'),
                    new OA\Property(property: 'new_password_confirmation', type: 'string', example: 'nuevaPassword123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Contraseña actualizada'),
            new OA\Response(response: 401, description: 'No autenticado o contraseña actual incorrecta'),
            new OA\Response(response: 422, description: 'Datos inválidos')
        ]
    )]
    public function changePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (!Hash::check($validated['current_password'], $user->password)) {
            // Log cambio de contraseña fallido
            SecurityLog::create([
                'user_id'     => $user->id,
                'tipo_evento' => 'cambio_password_fallido',
                'ip'          => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'endpoint'    => $request->path(),
                'metodo'      => $request->method(),
                'descripcion' => 'Intento fallido de cambio de contraseña: ' . $user->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'La contraseña actual es incorrecta'
            ], 401);
        }

        $user->update([
            'password' => $validated['new_password']
        ]);

        // Log cambio de contraseña exitoso
        SecurityLog::create([
            'user_id'     => $user->id,
            'tipo_evento' => 'cambio_password_exitoso',
            'ip'          => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'endpoint'    => $request->path(),
            'metodo'      => $request->method(),
            'descripcion' => 'Cambio de contraseña exitoso: ' . $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }
}