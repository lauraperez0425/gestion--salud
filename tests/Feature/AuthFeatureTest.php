<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_puede_iniciar_sesion_y_genera_un_log_de_auditoria()
    {
        // 1. Preparamos el escenario
        $role = Role::create(['nombre' => 'administrador']);
        
        $user = User::create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('123456'),
            'rol_id' => $role->id
        ]);

        // 2. Ejecutamos la acción
        $response = $this->postJson('/api/login', [
            'email' => 'admin@admin.com',
            'password' => '123456'
        ]);

        // 3. Verificaciones
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => ['user', 'token']
                 ]);

        $this->assertDatabaseHas('security_logs', [
            'user_id' => $user->id,
            'tipo_evento' => 'login_exitoso'
        ]);
    }
}