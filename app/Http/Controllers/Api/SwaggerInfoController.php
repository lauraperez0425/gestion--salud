<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: 'Gestion Salud API',
	version: '1.0.0',
	description: 'Documentación de la API de Gestión de Salud'
)]
#[OA\Server(
	url: 'http://127.0.0.1:8000',
	description: 'Servidor local'
)]
#[OA\SecurityScheme(
	securityScheme: 'sanctum',
	type: 'apiKey',
	in: 'header',
	name: 'Authorization',
	description: 'Usa el formato Bearer {token}'
)]
class SwaggerInfoController
{
}
