<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PacienteController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/roles', [RoleController::class, 'index']);

    Route::apiResource('pacientes', PacienteController::class);

    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});