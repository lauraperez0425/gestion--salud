<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenApi\Attributes as OA;

class CitaController extends Controller
{
    #[OA\Get(
        path: '/api/citas',
        summary: 'Lista de citas',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'No autenticado')
        ]
    )]
    public function index(Request $request)
    {
        $query = Cita::with([
            'paciente:id,nombre,apellido,segundo_apellido,ci',
            'medico:id,name,email',
        ])->orderBy('fecha')->orderBy('hora');

        if ($request->filled('fecha')) {
            $query->whereDate('fecha', $request->string('fecha')->toString());
        }

        if ($request->filled('medico_id')) {
            $query->where('medico_id', $request->integer('medico_id'));
        }

        if ($request->filled('paciente_id')) {
            $query->where('paciente_id', $request->integer('paciente_id'));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->string('estado')->toString());
        }

        return response()->json([
            'success' => true,
            'message' => 'Lista de citas',
            'data' => $query->get(),
        ]);
    }

    #[OA\Post(
        path: '/api/citas',
        summary: 'Agendar cita',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            description: 'Datos para agendar una cita',
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['paciente_id', 'medico_id', 'fecha', 'hora'],
                properties: [
                    new OA\Property(property: 'paciente_id', type: 'integer', description: 'ID del paciente'),
                    new OA\Property(property: 'medico_id', type: 'integer', description: 'ID del médico'),
                    new OA\Property(property: 'fecha', type: 'string', format: 'date', description: 'Fecha de la cita (Y-m-d)'),
                    new OA\Property(property: 'hora', type: 'string', format: 'time', description: 'Hora de la cita (H:i)'),
                    new OA\Property(property: 'estado', type: 'string', enum: ['pendiente', 'confirmada', 'cancelada', 'completada'], description: 'Estado de la cita (opcional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cita creada'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 409, description: 'Horario no disponible'),
            new OA\Response(response: 422, description: 'Datos invalidos')
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'medico_id' => 'required|exists:users,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'estado' => 'nullable|in:pendiente,confirmada,cancelada,completada',
        ]);

        $medico = User::with('role')->find($validated['medico_id']);
        if (!$this->esMedico($medico)) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no tiene rol de medico',
            ], 422);
        }

        if ($this->hayConflicto(null, $validated['medico_id'], $validated['paciente_id'], $validated['fecha'], $validated['hora'])) {
            return response()->json([
                'success' => false,
                'message' => 'El horario solicitado no esta disponible',
            ], 409);
        }

        $cita = Cita::create([
            ...$validated,
            'estado' => $validated['estado'] ?? 'pendiente',
        ]);

        $cita->load([
            'paciente:id,nombre,apellido,segundo_apellido,ci',
            'medico:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cita agendada correctamente',
            'data' => $cita,
        ], 201);
    }

    #[OA\Get(
        path: '/api/citas/{id}',
        summary: 'Detalle de cita',
        tags: ['Citas'],
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
        $cita = Cita::with([
            'paciente:id,nombre,apellido,segundo_apellido,ci',
            'medico:id,name,email',
        ])->find($id);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detalle de la cita',
            'data' => $cita,
        ]);
    }

    #[OA\Put(
        path: '/api/citas/{id}',
        summary: 'Reprogramar o actualizar cita',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cita actualizada'),
            new OA\Response(response: 404, description: 'No encontrado'),
            new OA\Response(response: 409, description: 'Horario no disponible'),
            new OA\Response(response: 422, description: 'Datos invalidos')
        ]
    )]
    public function update(Request $request, string $id)
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        $validated = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'medico_id' => 'required|exists:users,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
            'hora' => 'required|date_format:H:i',
            'estado' => 'required|in:pendiente,confirmada,cancelada,completada',
        ]);

        $medico = User::with('role')->find($validated['medico_id']);
        if (!$this->esMedico($medico)) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no tiene rol de medico',
            ], 422);
        }

        if ($this->hayConflicto($cita->id, $validated['medico_id'], $validated['paciente_id'], $validated['fecha'], $validated['hora'])) {
            return response()->json([
                'success' => false,
                'message' => 'El horario solicitado no esta disponible',
            ], 409);
        }

        $cita->update($validated);
        $cita->load([
            'paciente:id,nombre,apellido,segundo_apellido,ci',
            'medico:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cita actualizada correctamente',
            'data' => $cita,
        ]);
    }

    #[OA\Delete(
        path: '/api/citas/{id}',
        summary: 'Cancelar cita',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cita cancelada'),
            new OA\Response(response: 404, description: 'No encontrado')
        ]
    )]
    public function destroy(string $id)
    {
        $cita = Cita::find($id);

        if (!$cita) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada',
            ], 404);
        }

        $cita->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cita cancelada correctamente',
        ]);
    }

    #[OA\Get(
        path: '/api/citas/disponibilidad',
        summary: 'Horarios disponibles por medico y fecha',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 422, description: 'Datos invalidos')
        ]
    )]
    public function disponibilidad(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $medico = User::with('role')->find($validated['medico_id']);
        if (!$this->esMedico($medico)) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario seleccionado no tiene rol de medico',
            ], 422);
        }

        $ocupadas = Cita::query()
            ->where('medico_id', $validated['medico_id'])
            ->whereDate('fecha', $validated['fecha'])
            ->where('estado', '!=', 'cancelada')
            ->pluck('hora')
            ->map(fn ($hora) => Carbon::createFromFormat('H:i:s', $hora)->format('H:i'))
            ->values();

        $inicio = Carbon::createFromFormat('H:i', '08:00');
        $fin = Carbon::createFromFormat('H:i', '17:00');

        $horarios = [];
        while ($inicio->lt($fin)) {
            $hora = $inicio->format('H:i');
            $horarios[] = [
                'hora' => $hora,
                'disponible' => !$ocupadas->contains($hora),
            ];
            $inicio->addMinutes(30);
        }

        return response()->json([
            'success' => true,
            'message' => 'Horarios disponibles',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha' => $validated['fecha'],
                'horarios' => $horarios,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/medicos/{medico_id}/citas',
        summary: 'Citas de un médico específico',
        tags: ['Citas'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 404, description: 'Médico no encontrado')
        ]
    )]
    public function citasPorMedico(string $medico_id)
    {
        $medico = User::with('role')->find($medico_id);
        
        if (!$medico || !$this->esMedico($medico)) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);
        }

        $citas = Cita::with([
            'paciente:id,nombre,apellido,segundo_apellido,ci',
            'medico:id,name,email',
        ])
            ->where('medico_id', $medico_id)
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Citas del médico',
            'data' => $citas,
        ]);
    }

    private function hayConflicto(?int $citaId, int $medicoId, int $pacienteId, string $fecha, string $hora): bool
    {
        $porMedico = Cita::query()
            ->when($citaId, fn ($q) => $q->where('id', '!=', $citaId))
            ->where('medico_id', $medicoId)
            ->whereDate('fecha', $fecha)
            ->whereTime('hora', $hora)
            ->where('estado', '!=', 'cancelada')
            ->exists();

        $porPaciente = Cita::query()
            ->when($citaId, fn ($q) => $q->where('id', '!=', $citaId))
            ->where('paciente_id', $pacienteId)
            ->whereDate('fecha', $fecha)
            ->whereTime('hora', $hora)
            ->where('estado', '!=', 'cancelada')
            ->exists();

        return $porMedico || $porPaciente;
    }

    private function esMedico(?User $user): bool
    {
        if (!$user || !$user->role) {
            return false;
        }

        $nombreRol = strtolower(trim($user->role->nombre));

        return in_array($nombreRol, ['medico', 'medico/a', 'medico(a)', 'médico', 'doctor'], true);
    }
}
