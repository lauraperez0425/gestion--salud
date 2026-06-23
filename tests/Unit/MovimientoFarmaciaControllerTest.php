<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\MovimientoFarmaciaController;
use App\Models\Medicamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class MovimientoFarmaciaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_store_retorna_409_si_el_stock_es_insuficiente_en_una_salida()
    {
        $controller = new MovimientoFarmaciaController();

        // Creamos un medicamento con poco stock (ej: 5 unidades)
        $medicamento = Medicamento::create([
            'id' => 1,
            'nombre' => 'Paracetamol 500mg',
            'stock' => 5,
            'precio' => 1500,
            'estado' => 'activo'
        ]);

        // Simulamos una petición para retirar MÁS de lo que hay (ej: 10 unidades)
        $request = Request::create('/api/movimientos-farmacia', 'POST', [
            'medicamento_id' => $medicamento->id,
            'tipo' => 'salida',
            'cantidad' => 10,
            'fecha' => '2026-06-23',
        ]);

        $response = $controller->store($request);
        $data = json_decode($response->getContent(), true);

        // Evaluamos que salte el error 409 por lógica de negocio
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Stock insuficiente', $data['message']);
    }

    /** @test */
    public function test_por_medicamento_retorna_404_si_el_medicamento_no_existe()
    {
        $controller = new MovimientoFarmaciaController();

        // Buscamos un ID que no existe
        $response = $controller->porMedicamento(999);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Medicamento no encontrado', $data['message']);
    }
}