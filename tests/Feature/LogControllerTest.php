<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\LogController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_controlador_aplica_el_filtro_de_tipo_de_evento_correctamente()
    {
        $controller = new LogController();

        $request = Request::create('/api/logs/seguridad', 'GET', [
            'tipo_evento' => 'login_fallido'
        ]);

        $response = $controller->logsSeguridad($request);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function test_el_controlador_aplica_los_filtros_de_fecha_correctamente()
    {
        $controller = new LogController();

        $request = Request::create('/api/logs/seguridad', 'GET', [
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31'
        ]);

        $response = $controller->logsSeguridad($request);
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
    }
}