<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Gestiona usuarios, roles y controla el sistema']
        );

        Role::firstOrCreate(
            ['nombre' => 'Medico'],
            ['descripcion' => 'Atiende pacientes y registra historias clínicas']
        );

        Role::firstOrCreate(
            ['nombre' => 'Recepcionista'],
            ['descripcion' => 'Gestiona citas y registro de pacientes']
        );

        Role::firstOrCreate(
            ['nombre' => 'Paciente'],
            ['descripcion' => 'Consulta su información y servicios asignados']
        );
    }
}