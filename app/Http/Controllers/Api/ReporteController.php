<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cita;
use App\Models\Medicamento;
use App\Models\MovimientoFarmacia;
use App\Models\RecetaMedica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ReporteController extends Controller
{
    #[OA\Get(
        path: '/api/reportes/pacientes-atendidos-por-medico',
        summary: 'Pacientes atendidos por medico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function pacientesAtendidosPorMedico(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = Cita::query()
            ->where('medico_id', $validated['medico_id'])
            ->where('estado', 'completada');

        $this->applyDateRangeToCitas($query, $validated);

        $totalPacientesUnicos = (clone $query)->distinct('paciente_id')->count('paciente_id');
        $totalCitasCompletadas = (clone $query)->count();

        return response()->json([
            'success' => true,
            'message' => 'Reporte de pacientes atendidos por medico',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'total_pacientes_unicos' => $totalPacientesUnicos,
                'total_citas_completadas' => $totalCitasCompletadas,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/total-citas-por-medico',
        summary: 'Total de citas por medico en rango de fechas',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function totalCitasPorMedico(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha_desde' => 'required|date_format:Y-m-d',
            'fecha_hasta' => 'required|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = Cita::query()->where('medico_id', $validated['medico_id']);
        $this->applyDateRangeToCitas($query, $validated);

        $totalCitas = (clone $query)->count();
        $porEstado = (clone $query)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return response()->json([
            'success' => true,
            'message' => 'Reporte total de citas por medico',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'],
                'fecha_hasta' => $validated['fecha_hasta'],
                'total_citas' => $totalCitas,
                'por_estado' => $porEstado,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/tasa-cancelacion-por-medico',
        summary: 'Tasa de cancelacion de citas por medico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function tasaCancelacionPorMedico(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha_desde' => 'required|date_format:Y-m-d',
            'fecha_hasta' => 'required|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = Cita::query()->where('medico_id', $validated['medico_id']);
        $this->applyDateRangeToCitas($query, $validated);

        $totalCitas = (clone $query)->count();
        $canceladas = (clone $query)->where('estado', 'cancelada')->count();
        $tasa = $totalCitas > 0 ? round(($canceladas / $totalCitas) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Reporte de tasa de cancelacion por medico',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'],
                'fecha_hasta' => $validated['fecha_hasta'],
                'total_citas' => $totalCitas,
                'citas_canceladas' => $canceladas,
                'tasa_cancelacion_porcentaje' => $tasa,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/recetas-emitidas-por-medico',
        summary: 'Recetas emitidas por medico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function recetasEmitidasPorMedico(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = RecetaMedica::query()->whereHas('cita', function ($q) use ($validated) {
            $q->where('medico_id', $validated['medico_id']);
        });

        $this->applyDateRangeToRecetasPorFechaCita($query, $validated);

        $totalRecetas = (clone $query)->count();
        $porEstadoDespacho = (clone $query)
            ->select('estado_despacho', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_despacho')
            ->pluck('total', 'estado_despacho');

        return response()->json([
            'success' => true,
            'message' => 'Reporte de recetas emitidas por medico',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'total_recetas_emitidas' => $totalRecetas,
                'por_estado_despacho' => $porEstadoDespacho,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/recetas-por-paciente-historico',
        summary: 'Recetas por paciente historico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'paciente_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function recetasPorPacienteHistorico(Request $request)
    {
        $validated = $request->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = RecetaMedica::with([
            'cita:id,paciente_id,medico_id,fecha,hora,estado',
            'medicamento:id,nombre,estado',
        ])->whereHas('cita', function ($q) use ($validated) {
            $q->where('paciente_id', $validated['paciente_id']);
        });

        $this->applyDateRangeToRecetasPorFechaCita($query, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Reporte historico de recetas por paciente',
            'data' => [
                'paciente_id' => (int) $validated['paciente_id'],
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'total_recetas' => (clone $query)->count(),
                'recetas' => $query->latest()->get(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/balance-recetas-estado-despacho',
        summary: 'Balance de recetas por estado de despacho',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function balanceRecetasEstadoDespacho(Request $request)
    {
        $validated = $request->validate([
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = RecetaMedica::query();
        $this->applyDateRangeToRecetasPorFechaCita($query, $validated);

        $balance = [
            'pendiente' => 0,
            'parcial' => 0,
            'despachada' => 0,
        ];

        $result = (clone $query)
            ->select('estado_despacho', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_despacho')
            ->pluck('total', 'estado_despacho')
            ->toArray();

        foreach ($result as $estado => $total) {
            $balance[$estado] = (int) $total;
        }

        return response()->json([
            'success' => true,
            'message' => 'Balance de recetas por estado de despacho',
            'data' => [
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'total_recetas' => array_sum($balance),
                'balance' => $balance,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/recetas-despachadas-por-farmaceutico',
        summary: 'Recetas despachadas por farmaceutico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'farmaceutico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function recetasDespachadasPorFarmaceutico(Request $request)
    {
        $validated = $request->validate([
            'farmaceutico_id' => 'required|exists:users,id',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $query = MovimientoFarmacia::query()
            ->where('farmaceutico_id', $validated['farmaceutico_id'])
            ->where('tipo', 'salida')
            ->whereNotNull('receta_medica_id');

        if (!empty($validated['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $validated['fecha_desde']);
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $validated['fecha_hasta']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reporte de recetas despachadas por farmaceutico',
            'data' => [
                'farmaceutico_id' => (int) $validated['farmaceutico_id'],
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'total_movimientos_despacho' => (clone $query)->count(),
                'total_recetas_unicas_despachadas' => (clone $query)->distinct('receta_medica_id')->count('receta_medica_id'),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/medicamentos-mas-prescritos-por-medico',
        summary: 'Medicamentos mas prescritos por medico',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'top', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function medicamentosMasPrescritosPorMedico(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'top' => 'nullable|integer|min:1|max:100',
            'fecha_desde' => 'nullable|date_format:Y-m-d',
            'fecha_hasta' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $top = (int) ($validated['top'] ?? 10);

        $query = RecetaMedica::query()
            ->join('citas', 'citas.id', '=', 'recetas_medicas.cita_id')
            ->join('medicamentos', 'medicamentos.id', '=', 'recetas_medicas.medicamento_id')
            ->where('citas.medico_id', $validated['medico_id']);

        if (!empty($validated['fecha_desde'])) {
            $query->whereDate('citas.fecha', '>=', $validated['fecha_desde']);
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereDate('citas.fecha', '<=', $validated['fecha_hasta']);
        }

        $ranking = $query
            ->select(
                'medicamentos.id as medicamento_id',
                'medicamentos.nombre as medicamento_nombre',
                DB::raw('COUNT(recetas_medicas.id) as total_prescripciones')
            )
            ->groupBy('medicamentos.id', 'medicamentos.nombre')
            ->orderByDesc('total_prescripciones')
            ->limit($top)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Reporte de medicamentos mas prescritos por medico',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'] ?? null,
                'fecha_hasta' => $validated['fecha_hasta'] ?? null,
                'top' => $top,
                'ranking' => $ranking,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/medicamentos-mas-prescritos-por-periodo',
        summary: 'Medicamentos mas prescritos por periodo',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'top', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function medicamentosMasPrescritosPorPeriodo(Request $request)
    {
        $validated = $request->validate([
            'fecha_desde' => 'required|date_format:Y-m-d',
            'fecha_hasta' => 'required|date_format:Y-m-d|after_or_equal:fecha_desde',
            'top' => 'nullable|integer|min:1|max:100',
        ]);

        $top = (int) ($validated['top'] ?? 10);

        $ranking = RecetaMedica::query()
            ->join('citas', 'citas.id', '=', 'recetas_medicas.cita_id')
            ->join('medicamentos', 'medicamentos.id', '=', 'recetas_medicas.medicamento_id')
            ->whereDate('citas.fecha', '>=', $validated['fecha_desde'])
            ->whereDate('citas.fecha', '<=', $validated['fecha_hasta'])
            ->select(
                'medicamentos.id as medicamento_id',
                'medicamentos.nombre as medicamento_nombre',
                DB::raw('COUNT(recetas_medicas.id) as total_prescripciones')
            )
            ->groupBy('medicamentos.id', 'medicamentos.nombre')
            ->orderByDesc('total_prescripciones')
            ->limit($top)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Reporte de medicamentos mas prescritos por periodo',
            'data' => [
                'fecha_desde' => $validated['fecha_desde'],
                'fecha_hasta' => $validated['fecha_hasta'],
                'top' => $top,
                'ranking' => $ranking,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/alertas-medicamentos-stock-bajo',
        summary: 'Alertas de medicamentos con stock bajo',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'umbral', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function alertasMedicamentosStockBajo(Request $request)
    {
        $validated = $request->validate([
            'umbral' => 'nullable|integer|min:0',
        ]);

        $umbral = (int) ($validated['umbral'] ?? 10);

        $medicamentos = Medicamento::query()
            ->where('stock', '<=', $umbral)
            ->where('estado', 'activo')
            ->orderBy('stock')
            ->get(['id', 'nombre', 'stock', 'precio', 'estado']);

        return response()->json([
            'success' => true,
            'message' => 'Alerta de medicamentos con stock bajo',
            'data' => [
                'umbral' => $umbral,
                'total_alertas' => $medicamentos->count(),
                'medicamentos' => $medicamentos,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/reportes/productividad-medica',
        summary: 'Productividad medica (citas completadas + recetas emitidas)',
        tags: ['Reportes'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'medico_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'fecha_desde', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_hasta', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado'),
            new OA\Response(response: 422, description: 'Datos invalidos'),
        ]
    )]
    public function productividadMedica(Request $request)
    {
        $validated = $request->validate([
            'medico_id' => 'required|exists:users,id',
            'fecha_desde' => 'required|date_format:Y-m-d',
            'fecha_hasta' => 'required|date_format:Y-m-d|after_or_equal:fecha_desde',
        ]);

        $citasCompletadas = Cita::query()
            ->where('medico_id', $validated['medico_id'])
            ->where('estado', 'completada')
            ->whereDate('fecha', '>=', $validated['fecha_desde'])
            ->whereDate('fecha', '<=', $validated['fecha_hasta'])
            ->count();

        $recetasEmitidas = RecetaMedica::query()
            ->whereHas('cita', function ($q) use ($validated) {
                $q->where('medico_id', $validated['medico_id'])
                    ->whereDate('fecha', '>=', $validated['fecha_desde'])
                    ->whereDate('fecha', '<=', $validated['fecha_hasta']);
            })
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Reporte de productividad medica',
            'data' => [
                'medico_id' => (int) $validated['medico_id'],
                'fecha_desde' => $validated['fecha_desde'],
                'fecha_hasta' => $validated['fecha_hasta'],
                'citas_completadas' => $citasCompletadas,
                'recetas_emitidas' => $recetasEmitidas,
                'indice_productividad' => $citasCompletadas + $recetasEmitidas,
            ],
        ]);
    }

    private function applyDateRangeToCitas($query, array $validated): void
    {
        if (!empty($validated['fecha_desde'])) {
            $query->whereDate('fecha', '>=', $validated['fecha_desde']);
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereDate('fecha', '<=', $validated['fecha_hasta']);
        }
    }

    private function applyDateRangeToRecetasPorFechaCita($query, array $validated): void
    {
        if (!empty($validated['fecha_desde'])) {
            $query->whereHas('cita', function ($q) use ($validated) {
                $q->whereDate('fecha', '>=', $validated['fecha_desde']);
            });
        }

        if (!empty($validated['fecha_hasta'])) {
            $query->whereHas('cita', function ($q) use ($validated) {
                $q->whereDate('fecha', '<=', $validated['fecha_hasta']);
            });
        }
    }
}