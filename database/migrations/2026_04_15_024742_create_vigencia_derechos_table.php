<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vigencia_derechos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado')->default('vigente');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vigencia_derechos');
    }
};