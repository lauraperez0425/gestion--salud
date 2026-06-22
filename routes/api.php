<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\RecetaMedicaController;
use App\Http\Controllers\Api\HistoriaClinicaController;
use App\Http\Controllers\Api\MedicamentoController;
use App\Http\Controllers\Api\MovimientoFarmaciaController;
use App\Http\Controllers\Api\TipoSangreController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\LogController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/medicos', [UserController::class, 'medicos']);
    Route::get('/tipos-sangre', [TipoSangreController::class, 'index']);

    // Farmacia - Medicamentos (cualquier autenticado puede consultar)
    Route::get('/medicamentos', [MedicamentoController::class, 'index']);
    Route::get('/medicamentos/{id}', [MedicamentoController::class, 'show']);
    Route::get('/medicamentos/{medicamento_id}/movimientos', [MovimientoFarmaciaController::class, 'porMedicamento']);

    // Farmacia - solo Médico y Administrador pueden gestionar
    Route::middleware('role:Medico,Administrador,Farmaceutico')->group(function () {
        Route::post('/medicamentos', [MedicamentoController::class, 'store']);
        Route::put('/medicamentos/{id}', [MedicamentoController::class, 'update']);
        Route::delete('/medicamentos/{id}', [MedicamentoController::class, 'destroy']);
        Route::get('/movimientos-farmacia', [MovimientoFarmaciaController::class, 'index']);
        Route::post('/movimientos-farmacia', [MovimientoFarmaciaController::class, 'store']);
        Route::get('/movimientos-farmacia/{id}', [MovimientoFarmaciaController::class, 'show']);

        // Recetas médicas
        Route::get('/recetas-medicas', [RecetaMedicaController::class, 'index']);
        Route::post('/recetas-medicas', [RecetaMedicaController::class, 'store']);
        Route::get('/recetas-medicas/{id}', [RecetaMedicaController::class, 'show']);
        Route::put('/recetas-medicas/{id}', [RecetaMedicaController::class, 'update']);
        Route::delete('/recetas-medicas/{id}', [RecetaMedicaController::class, 'destroy']);
    });

    Route::apiResource('pacientes', PacienteController::class);
    // Historias Clínicas
    Route::get('/pacientes/{paciente_id}/historias-clinicas', [HistoriaClinicaController::class, 'porPaciente']);
    Route::middleware('role:Medico,Administrador')->group(function () {
        Route::get('/historias-clinicas', [HistoriaClinicaController::class, 'index']);
        Route::post('/historias-clinicas', [HistoriaClinicaController::class, 'store']);
        Route::get('/historias-clinicas/{id}', [HistoriaClinicaController::class, 'show']);
        Route::put('/historias-clinicas/{id}', [HistoriaClinicaController::class, 'update']);
        Route::delete('/historias-clinicas/{id}', [HistoriaClinicaController::class, 'destroy']);
    });

    Route::get('/citas/disponibilidad', [CitaController::class, 'disponibilidad']);
    Route::get('/medicos/{medico_id}/citas', [CitaController::class, 'citasPorMedico']);
    Route::get('/pacientes/{paciente_id}/citas', [CitaController::class, 'citasPorPaciente']);
    Route::get('/medicos/{medico_id}/recetas-medicas', [RecetaMedicaController::class, 'porMedico']);
    Route::get('/pacientes/{paciente_id}/recetas-medicas', [RecetaMedicaController::class, 'porPaciente']);
    
    // Solo médico y administrador pueden listar TODAS las citas
    Route::middleware('role:Medico,Administrador,Soporte')->group(function () {
        Route::get('/citas', [CitaController::class, 'index']);
    });
    
    // Otros usuarios autenticados pueden crear, ver, actualizar, cancelar citas
    Route::post('/citas', [CitaController::class, 'store']);
    Route::get('/citas/{id}', [CitaController::class, 'show']);
    Route::put('/citas/{id}', [CitaController::class, 'update']);
    Route::delete('/citas/{id}', [CitaController::class, 'destroy']);

    Route::middleware('role:Administrador,Soporte')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::middleware('role:Administrador')->group(function () {
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Logs
    Route::get('/logs/seguridad', [LogController::class, 'logsSeguridad']);
    Route::get('/logs/aplicacion', [LogController::class, 'logsAplicacion']);
    });

    Route::middleware('role:Medico,Administrador')->prefix('reportes')->group(function () {
        Route::get('/pacientes-atendidos-por-medico', [ReporteController::class, 'pacientesAtendidosPorMedico']);
        Route::get('/total-citas-por-medico', [ReporteController::class, 'totalCitasPorMedico']);
        Route::get('/tasa-cancelacion-por-medico', [ReporteController::class, 'tasaCancelacionPorMedico']);
        Route::get('/recetas-emitidas-por-medico', [ReporteController::class, 'recetasEmitidasPorMedico']);
        Route::get('/recetas-por-paciente-historico', [ReporteController::class, 'recetasPorPacienteHistorico']);
        Route::get('/balance-recetas-estado-despacho', [ReporteController::class, 'balanceRecetasEstadoDespacho']);
        Route::get('/recetas-despachadas-por-farmaceutico', [ReporteController::class, 'recetasDespachadasPorFarmaceutico']);
        Route::get('/medicamentos-mas-prescritos-por-medico', [ReporteController::class, 'medicamentosMasPrescritosPorMedico']);
        Route::get('/medicamentos-mas-prescritos-por-periodo', [ReporteController::class, 'medicamentosMasPrescritosPorPeriodo']);
        Route::get('/alertas-medicamentos-stock-bajo', [ReporteController::class, 'alertasMedicamentosStockBajo']);
        Route::get('/productividad-medica', [ReporteController::class, 'productividadMedica']);
    });
    
});