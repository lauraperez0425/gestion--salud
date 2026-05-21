<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->decimal('estatura', 3, 2)->nullable()->after('direccion');
            $table->decimal('peso', 5, 2)->nullable()->after('estatura');
            $table->foreignId('tipo_sangre_id')->nullable()->after('peso')->constrained('tipos_sangre')->nullOnDelete();
            $table->string('presion_arterial', 20)->nullable()->after('tipo_sangre_id');
            $table->decimal('temperatura', 4, 1)->nullable()->after('presion_arterial');
            $table->unsignedTinyInteger('oxigeno_sangre')->nullable()->after('temperatura');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['tipo_sangre_id']);
            $table->dropColumn([
                'estatura',
                'peso',
                'tipo_sangre_id',
                'presion_arterial',
                'temperatura',
                'oxigeno_sangre',
            ]);
        });
    }
};
