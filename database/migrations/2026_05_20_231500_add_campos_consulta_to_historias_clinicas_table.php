<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->text('motivo_consulta')->nullable()->after('paciente_id');
            $table->text('enfermedad_actual')->nullable()->after('motivo_consulta');
            $table->decimal('peso', 5, 2)->nullable()->after('enfermedad_actual');
            $table->decimal('talla', 3, 2)->nullable()->after('peso');
            $table->string('presion_arterial', 20)->nullable()->after('talla');
            $table->unsignedTinyInteger('saturacion')->nullable()->after('presion_arterial');
            $table->decimal('temperatura', 4, 1)->nullable()->after('saturacion');
        });
    }

    public function down(): void
    {
        Schema::table('historias_clinicas', function (Blueprint $table) {
            $table->dropColumn([
                'motivo_consulta',
                'enfermedad_actual',
                'peso',
                'talla',
                'presion_arterial',
                'saturacion',
                'temperatura',
            ]);
        });
    }
};
