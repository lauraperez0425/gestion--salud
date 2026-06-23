<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_login_retorna_401_cuando_las_credenciales_son_incorrectas()
    {
        $controller = new AuthController();

        $request = Request::create('/api/login', 'POST', [
            'email' => 'usuario_inexistente@salud.com',
            'password' => 'claveErronea123'
        ]);

        $response = $controller->login($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Credenciales incorrectas', $data['message']);
    }

    /** @test */
    public function test_me_retorna_401_si_el_usuario_no_esta_autenticado()
    {
        $controller = new AuthController();
        
        $request = new Request();

        $response = $controller->me($request);
        $data = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('Usuario no autenticado', $data['message']);
    }

    /** @test */
    public function test_logout_retorna_401_si_no_hay_una_sesion_activa()
    {
        $controller = new AuthController();
        $request = new Request();

        // Intentamos cerrar sesión sin un token ni usuario activo
        $response = $controller->logout($request);
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
        $this->assertEquals('No hay sesión activa', $data['message']);
    }

    /** @test */
    public function test_change_password_retorna_401_si_el_usuario_intenta_cambiarla_sin_autenticarse()
    {
        $controller = new AuthController();
        $request = new Request();

        $response = $controller->changePassword($request);
        $data = json_decode($response->getContent(), true);

        // Validamos la condición de entrada if (!$user)
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($data['success']);
    }
}