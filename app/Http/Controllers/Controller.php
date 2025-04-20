<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="KIABOO API Documentation",
 *      description="KIABOO API Documentation for partners",
 *      @OA\Contact(
 *          email="devops@kiaboo.net"
 *     ),
 *     @OA\License(
 *     name="Apache 2.0",
 *     url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *    )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     scheme="bearer",
 *     securityScheme="bearerAuth"
 * )
 *
 * @OA\PathItem(
 *     path="/api/v1/user/login",
 *     )
 *
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
