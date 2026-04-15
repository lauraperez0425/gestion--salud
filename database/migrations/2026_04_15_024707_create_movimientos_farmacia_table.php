<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_farmacia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medicamento_id')->constrained('medicamentos')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->integer('cantidad');
            $table->text('detalle')->nullable();
            $table->date('fecha');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_farmacia');
    }
};