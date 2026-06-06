<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas_medicas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')->constrained('citas')->cascadeOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos')->cascadeOnDelete();

            $table->string('dosis')->nullable();
            $table->string('frecuencia')->nullable();
            $table->string('duracion')->nullable();
            $table->text('indicaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cita_id', 'medicamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas_medicas');
    }
};