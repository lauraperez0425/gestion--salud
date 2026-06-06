<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas_medicas', function (Blueprint $table) {
            $table->enum('estado_despacho', ['pendiente', 'parcial', 'despachada'])
                ->default('pendiente')
                ->after('indicaciones');
        });

        Schema::table('movimientos_farmacia', function (Blueprint $table) {
            $table->foreignId('receta_medica_id')
                ->nullable()
                ->after('medicamento_id')
                ->constrained('recetas_medicas')
                ->nullOnDelete();

            $table->foreignId('farmaceutico_id')
                ->nullable()
                ->after('receta_medica_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_farmacia', function (Blueprint $table) {
            $table->dropForeign(['farmaceutico_id']);
            $table->dropColumn('farmaceutico_id');
            $table->dropForeign(['receta_medica_id']);
            $table->dropColumn('receta_medica_id');
        });

        Schema::table('recetas_medicas', function (Blueprint $table) {
            $table->dropColumn('estado_despacho');
        });
    }
};