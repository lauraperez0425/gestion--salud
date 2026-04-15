<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historias_clinicas', function (Blueprint $table) {
            $table->id();

            // Relación con paciente
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();

            // Información médica
            $table->text('diagnostico');
            $table->text('tratamiento')->nullable();
            $table->text('observaciones')->nullable();

            $table->date('fecha');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historias_clinicas');
    }
};