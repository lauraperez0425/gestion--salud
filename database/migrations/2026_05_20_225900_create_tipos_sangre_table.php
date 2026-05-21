<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_sangre', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 5)->unique();
            $table->timestamps();
        });

        DB::table('tipos_sangre')->insert([
            ['nombre' => 'A+'],
            ['nombre' => 'A-'],
            ['nombre' => 'B+'],
            ['nombre' => 'B-'],
            ['nombre' => 'AB+'],
            ['nombre' => 'AB-'],
            ['nombre' => 'O+'],
            ['nombre' => 'O-'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_sangre');
    }
};
