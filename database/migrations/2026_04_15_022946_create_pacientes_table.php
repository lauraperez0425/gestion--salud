<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('nombre');
            $table->string('apellido');
            $table->string('ci')->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();

            // Control del sistema
            $table->boolean('seguro')->default(false);
            $table->string('estado')->default('activo');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};