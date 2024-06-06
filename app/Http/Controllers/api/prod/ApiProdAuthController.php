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
     *       required={"login","password"},
     *       @OA\Property(property="login", type="string",format="text", example="699972941"),
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
     *    response=200,
     *    description="successful login user",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="statusCode", type="string", example="LOGIN-SUCCESS"),
     *       @OA\Property(property="message", type="string", example="successful login user"),
     *       @OA\Property(property="token", type="string", example="xxxxxxxxxxxxxxxxxxxx"),
     *       @OA\Property(
     *          type="array",
     *          property="data",
     *          @OA\Items(
     *              @OA\Property(
     *                  property="user",
     *                  type="object",
     *                  @OA\Property(property="first_name", type="string", example="houvre"),
     *                  @OA\Property(property="last_name", type="string", example="autre"),
     *                  @OA\Property(property="date_of_birth", type="string", example="12/02/1884"),
     *                  @OA\Property(property="phone", type="string", example="+1325487568"),
     *                  @OA\Property(property="matricule", type="string", example="xx458547854"),
     *                  @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *                  @OA\Property(property="status", type="string", example="active"),
     *                  @OA\Property(property="gender", type="string", example="female"),
     *                  @OA\Property(property="account_verified_at", type="string", example="null"),
     *                  @OA\Property(property="profile_image", type="string", example="xxxx/oplo.jpeg"),
     *                  @OA\Property(property="station", type="string", example="TOTAL Bijou"),
     *                  @OA\Property(property="role", type="string", example="User"),
     *                  @OA\Property(property="poste", type="string", example="User"),
     *                  @OA\Property(property="quarts", type="array", @OA\Items()),
     *                  @OA\Property(property="actions", type="array",@OA\Items()),
     *                ),
     *             @OA\Property(
     *               property="roles",
     *               type="array",
     *               @OA\Items()
     *            ),
     *           ),
     *         description="data array"
     *    )
     *    )
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
            return response(['errors' => $validator->errors()->all()], 422);
        }

        // Here, we get the user credentials from the request
        $credentials = [
            'login' => "+237".$request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
            'type_user_id' => UserRolesEnum::AGENT->value
        ];

        if (Auth::attempt($credentials)) {
            $users = Auth::user();
            $user = User::where('id', $users->id)->select('id', 'name', 'surname')->first();


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
            return $this->respondWithToken($access_token, $user);
        }
        Log::alert([
            'Login'=>$request->login,
            'Desciption'=>'Connexion->echec'
        ]);
        return response()->json([
            'message' => 'Invalid login details',
        ], 401);
    }


}
