<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('nombre', 'Administrador')->first();

        if ($adminRole) {
            User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'name' => 'Administrador',
                    'password' => Hash::make('123456'),
                    'rol_id' => $adminRole->id,
                ]
            );
        }
    }
}