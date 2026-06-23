<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Paciente;
use App\Models\HistoriaClinica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoriaClinicaFeatureTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    protected function setUp(): void
    {
        parent::setUp();
        
        $role = Role::create(['nombre' => 'Medico']);
        $this->user = User::create([
            'name' => 'laura yahuita',
            'email' => 'lauyahuita@gmail.com',
            'password' => bcrypt('password'),
            'rol_id' => $role->id
        ]);
    }

    public function test_un_medico_puede_crear_una_historia_clinica_con_signos_vitales()
    {
        $paciente = Paciente::create([
            'nombre' => 'Francisco',
            'apellido' => 'Yahuita',
            'segundo_apellido' => 'Quisbert',
            'ci' => '2470918'
        ]);

        $response = $this->actingAs($this->user)
                         ->postJson('/api/historias-clinicas', [
                             'paciente_id' => $paciente->id,
                             'motivo_consulta' => 'Control de rutina',
                             'enfermedad_actual' => 'Ninguna, paciente asintomático.',
                             'saturacion' => 98,
                             'temperatura' => 36.5,
                             'diagnostico' => 'Paciente saludable',
                             'fecha' => '2026-06-23'
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.saturacion', 98);

        $this->assertDatabaseHas('historias_clinicas', [
            'paciente_id' => $paciente->id,
            'diagnostico' => 'Paciente saludable'
        ]);
    }

    public function test_el_filtro_de_fechas_en_el_index_funciona_correctamente()
    {
        $paciente = Paciente::create([
            'nombre' => 'Francisco', 'apellido' => 'Yahuita', 'ci' => '2470918'
        ]);

        HistoriaClinica::create([
            'paciente_id' => $paciente->id, 'motivo_consulta' => 'Vieja', 'enfermedad_actual' => 'X',
            'diagnostico' => 'Ok', 'fecha' => '2026-01-01'
        ]);
        
        HistoriaClinica::create([
            'paciente_id' => $paciente->id, 'motivo_consulta' => 'Nueva', 'enfermedad_actual' => 'Y',
            'diagnostico' => 'Ok', 'fecha' => '2026-06-01'
        ]);

        $response = $this->actingAs($this->user)
                         ->getJson('/api/historias-clinicas?fecha_desde=2026-05-01');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data') 
                 ->assertJsonPath('data.0.motivo_consulta', 'Nueva');
    }

    public function test_falla_la_creacion_si_la_saturacion_u_otros_datos_son_invalidos()
    {
        $paciente = Paciente::create([
            'nombre' => 'Luis', 'apellido' => 'Silva', 'ci' => '9876543'
        ]);
        $response = $this->actingAs($this->user)
                         ->postJson('/api/historias-clinicas', [
                             'paciente_id' => $paciente->id,
                             'motivo_consulta' => 'Error',
                             'enfermedad_actual' => 'Error',
                             'saturacion' => 150, // Inválido
                             'diagnostico' => 'Error',
                             'fecha' => '2026-06-23'
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['saturacion']);
    }
}