<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CitaController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\HistoriaClinicaController;
use App\Http\Controllers\Api\TipoSangreController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/medicos', [UserController::class, 'medicos']);
    Route::get('/tipos-sangre', [TipoSangreController::class, 'index']);

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
    
    // Solo médico y administrador pueden listar TODAS las citas
    Route::middleware('role:Medico,Administrador')->group(function () {
        Route::get('/citas', [CitaController::class, 'index']);
    });
    
    // Otros usuarios autenticados pueden crear, ver, actualizar, cancelar citas
    Route::post('/citas', [CitaController::class, 'store']);
    Route::get('/citas/{id}', [CitaController::class, 'show']);
    Route::put('/citas/{id}', [CitaController::class, 'update']);
    Route::delete('/citas/{id}', [CitaController::class, 'destroy']);

    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    });
});