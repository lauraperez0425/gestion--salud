<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CitaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $userAutenticado;
    protected User $medico;
    protected Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $rolMedico = Role::create([
            'nombre' => 'Medico',
            'descripcion' => 'Atiende pacientes'
        ]);

        $this->medico = User::create([
            'name' => 'Dr. House',
            'email' => 'house@salud.com',
            'password' => bcrypt('password123'),
            'rol_id' => $rolMedico->id,
        ]);

        $this->paciente = Paciente::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'segundo_apellido' => 'Soliz',
            'ci' => '1234567',
        ]);

        $this->userAutenticado = User::create([
            'name' => 'Usuario Sistema',
            'email' => 'sistema@salud.com',
            'password' => bcrypt('password123'),
            'rol_id' => $rolMedico->id, 
        ]);
    }

    public function test_un_usuario_autenticado_puede_listar_las_citas()
    {
        Sanctum::actingAs($this->userAutenticado);

        Cita::create([
            'paciente_id' => $this->paciente->id,
            'medico_id' => $this->medico->id,
            'fecha' => '2026-07-15',
            'hora' => '09:00:00',
            'estado' => 'pendiente'
        ]);

        $response = $this->getJson('/api/citas');

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'message', 'data']);
    }

    public function test_no_se_puede_agendar_una_cita_si_el_usuario_no_tiene_el_rol_de_medico()
    {
        $usuarioSinRol = User::create([
            'name' => 'Infiltrado',
            'email' => 'no-medico@salud.com',
            'password' => bcrypt('password123'),
        ]);

        Sanctum::actingAs($this->userAutenticado);

        $payload = [
            'paciente_id' => $this->paciente->id,
            'medico_id' => $usuarioSinRol->id, 
            'fecha' => '2026-07-15',
            'hora' => '10:00',
            'estado' => 'pendiente'
        ];

        $response = $this->postJson('/api/citas', $payload);

        $response->assertStatus(422)
                 ->assertJsonFragment(['message' => 'El usuario seleccionado no tiene rol de medico']);
    }

    public function test_puede_agendar_una_cita_exitosamente()
    {
        Sanctum::actingAs($this->userAutenticado);

        $payload = [
            'paciente_id' => $this->paciente->id,
            'medico_id' => $this->medico->id,
            'fecha' => '2026-07-20',
            'hora' => '11:30',
            'estado' => 'pendiente'
        ];

        $response = $this->postJson('/api/citas', $payload);

        $response->assertStatus(201);
    }

    public function test_puede_cancelar_una_cita_correctamente()
    {
        Sanctum::actingAs($this->userAutenticado);

        $cita = Cita::create([
            'paciente_id' => $this->paciente->id,
            'medico_id' => $this->medico->id,
            'fecha' => '2026-07-15',
            'hora' => '16:00:00',
            'estado' => 'pendiente'
        ]);

        $response = $this->deleteJson("/api/citas/{$cita->id}");

        $response->assertStatus(200);
    }
}