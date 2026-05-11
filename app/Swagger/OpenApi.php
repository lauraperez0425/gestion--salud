<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Gestion Salud API",
 *         version="1.0.0",
 *         description="Documentación de la API de Gestión de Salud"
 *     ),
 *     @OA\Server(
 *         url="http://127.0.0.1:8000",
 *         description="Servidor local"
 *     ),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="sanctum",
 *             type="http",
 *             scheme="bearer",
 *             bearerFormat="Token",
 *             description="Token Bearer obtenido desde /api/login"
 *         )
 *     )
 * )
 */
class OpenApi
{
}
