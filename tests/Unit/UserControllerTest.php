<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\UserController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    // Limpia la base de datos interna de pruebas en cada iteración
    use RefreshDatabase;

    /** @test */
    public function test_index_retorna_la_estructura_de_usuarios_correcta()
    {
        $controller = new UserController();

        $response = $controller->index();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Lista de usuarios', $data['message']);
        $this->assertArrayHasKey('data', $data);
    }

    /** @test */
    public function test_show_retorna_404_si_el_usuario_no_existe()
    {
        $controller = new UserController();
        // Intentamos mostrar un usuario inexistente
        $response = $controller->show('999');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Usuario no encontrado', $data['message']);
    }

    /** @test */
    public function test_update_retorna_404_si_el_usuario_a_editar_no_existe()
    {
        $controller = new UserController();
        $request = new Request();

        // Intentamos actualizar un usuario inexistente
        $response = $controller->update($request, '999');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }

    /** @test */
    public function test_destroy_retorna_404_si_el_usuario_a_eliminar_no_existe()
    {
        $controller = new UserController();
        $request = new Request();

        // Intentamos eliminar un usuario inexistente
        $response = $controller->destroy($request, '999');
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }
}