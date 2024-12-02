<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *   title="Koricha E-commerce API",
 *   version="1.0.0",
 *   description="This is the API documentation for the Koricha E-commerce platform. It provides all the necessary endpoints and models for interacting with the system.",
 *   @OA\Contact(
 *     email="support@koricha-ecommerce.com",
 *     name="Koricha E-commerce Support"
 *   ),
 *   @OA\License(
 *     name="Apache 2.0",
 *     url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *   )
 * ),
 * @OA\Server(
 *   url="https://api.koricha-ecommerce.com",
 *   description="Production Server"
 * ),
 * @OA\Server(
 *   url="https://staging-api.koricha-ecommerce.com",
 *   description="Staging Server"
 * ),
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT",
 *   in="header",
 *   name="Authorization"
 * ),
 * @OA\Schema(
 *   schema="LoginRequest",
 *   type="object",
 *   required={"email", "password"},
 *   @OA\Property(
 *     property="email",
 *     type="string",
 *     format="email",
 *     example="newsolutions@gmail.com",
 *     description="The user's email address"
 *   ),
 *   @OA\Property(
 *     property="password",
 *     type="string",
 *     format="password",
 *     example="secret",
 *     description="The user's password"
 *   )
 * ),
 * @OA\Schema(
 *   schema="ErrorResponse",
 *   type="object",
 *   @OA\Property(
 *     property="message",
 *     type="string",
 *     example="An error occurred",
 *     description="Error message"
 *   ),
 *   @OA\Property(
 *     property="code",
 *     type="integer",
 *     example=400,
 *     description="Error code"
 *   )
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}