<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="E-Commerce API Documentation",
 * description="Laravel ile geliştirilmiş E-Ticaret API projesi dokümantasyonu.",
 * @OA\Contact(
 * email="admin@test.com"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="API Sunucusu"
 * )
 *
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT",
 * description="Token bilginizi buraya girin (Bearer ...)"
 * )
 */

abstract class Controller
{
    //
}
