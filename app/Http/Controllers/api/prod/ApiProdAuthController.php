<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use App\Http\Enums\UserRolesEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApiProdAuthController extends BaseController
{

    /**
     * @OA\Post(
     * path="/api/v1/authenticate/auth",
     * summary="login  user",
     * description="login user",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="user informations",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="email",format="email", example="contact@kiaboo.net"),
     *       @OA\Property(property="password", type="string", format="password", example="password123"),
     *    ),
     * ),
     * @OA\Response(
     *    response=400,
     *    description="login credentials are invalid",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-CREDENTIALS-INVALID"),
     *       @OA\Property(property="message", type="string", example="login credentials are invalid"),
     *    )
     * ),
     * @OA\Response(
     *     response=422,
     *     description="attribute invalid",
     *     @OA\JsonContent(
     *        @OA\Property(property="success", type="boolean", example="false"),
     *        @OA\Property(property="statusCode", type="string", example="ERR-ATTRIBUTES-INVALID"),
     *        @OA\Property(property="message", type="string", example="attribute not valid"),
     *     )
     *  ),
     * @OA\Response(
     *    response=200,
     *    description="successful login user",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="statusCode", type="string", example="LOGIN-SUCCESS"),
     *       @OA\Property(property="message", type="string", example="successful login user"),
     *       @OA\Property(property="access_token", type="string", example="xxxxxxxxxxxxxxxxxxxx"),
     *       @OA\Property(
     *                  property="user",
     *                  type="object",
     *                  @OA\Property(property="name", type="string", example="houvre"),
     *                  @OA\Property(property="surname", type="string", example="autre"),
     *                ),
     *      ),
     * ),
     * @OA\Response(
     *    response=500,
     *    description="an error occurred",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
     *       @OA\Property(property="message", type="string", example="an error occurred"),
     *    )
     *  )
     * )
     */
    public function loginSwagger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|min:3|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {

            return response(
                [
                    'success'=>false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->all()

                ], 422);
        }

        // Here, we get the user credentials from the request
        $credentials = [
            'login' => "+237".$request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
            'type_user_id' => UserRolesEnum::AGENT->value
        ];
        try {
            if (Auth::attempt($credentials)) {
                $users = Auth::user();
                $user = User::where('id', $users->id)->select('name', 'surname')->first();
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
                $token = $user->createToken('kiaboo');
                $access_token = $token->accessToken;

                $user->last_connexion = Carbon::now();
                $user->save();
                Log::info([
                    'user_id'=>Auth::user()->id,
                    'name'=>Auth::user()->name." ".Auth::user()->surname,
                    'Desciption'=>'Connexion'
                ]);
                return $this->respondWithTokenSwagger($access_token, $user);
            }
            return response()->json([
                'success'=>false,
                'statusCode' => 'ERR-CREDENTIALS-INVALID', // 'ERR-CREDENTIALS-INVALID
                'message' => 'login credentials are invalid',
            ], 400);
        } catch (\Exception $err) {
            Log::error($err);
            return  response()->json(
                [
                    'success'=>false,
                    'statusCode' => 'ERR-UNAVAILABLE',
                    'message' => 'an error occurred',
                ],
                500
            );
        }



    }


}
