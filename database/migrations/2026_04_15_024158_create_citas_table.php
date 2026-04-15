<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained('users')->cascadeOnDelete();

            // Datos de la cita
            $table->date('fecha');
            $table->time('hora');
            $table->string('estado')->default('pendiente');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};