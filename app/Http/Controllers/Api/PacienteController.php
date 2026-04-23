<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    public function index()
    {
        $pacientes = Paciente::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de pacientes',
            'data' => $pacientes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => 'required|string|max:255|unique:pacientes,ci',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'seguro' => 'required|boolean',
            'estado' => 'required|string|max:255',
        ]);

        $paciente = Paciente::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paciente registrado correctamente',
            'data' => $paciente
        ], 201);
    }

    public function show(string $id)
    {
        $paciente = Paciente::find($id);

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
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'ci' => [
                'required',
                'string',
                'max:255',
                Rule::unique('pacientes', 'ci')->ignore($paciente->id),
            ],
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'seguro' => 'required|boolean',
            'estado' => 'required|string|max:255',
        ]);

        $paciente->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Paciente actualizado correctamente',
            'data' => $paciente
        ]);
    }

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