<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();

            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('estado')->default('activo');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};