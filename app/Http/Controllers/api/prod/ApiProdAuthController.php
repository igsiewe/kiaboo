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
     * summary="Login  user",
     * description="Login user",
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="user informations",
     *    @OA\JsonContent(
     *       required={"login","password"},
     *       @OA\Property(property="login", type="email",format="email", example="alain.kamdem@gmail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="password"),
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
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|min:12|email|max:255',
            'password' => 'required|string|min:6|max:255',
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
            'login' => $request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
         //   'type_user_id' => UserRolesEnum::DISTRIBUTEUR->value
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
                //dd(\auth()->user());
                Log::info([
                    'user_id'=>Auth::user()->id,
                    'name'=>Auth::user()->name." ".Auth::user()->surname,
                    'Description'=>'Connexion'
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

    /**
     * @OA\Post(
     * path="/api/v1/authenticate/changepassword",
     * summary="Change password user",
     * description="Change password user",
     * security={{"bearerAuth":{}}},
     * tags={"Auth"},
     * @OA\RequestBody(
     *    required=true,
     *    description="change password user connected",
     *    @OA\JsonContent(
     *       required={"old_password","new_password","confirm_password"},
     *       @OA\Property(property="old_password", type="string", example="pasKio@_#85l24"),
     *       @OA\Property(property="new_password", type="string", example="NFt@_#85lop24"),
     *       @OA\Property(property="confirm_password", type="string", example="NFt@_#85lop24"),
     *    ),
     * ),
     * @OA\Response(
     *    response=400,
     *    description="old password are invalid",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="false"),
     *       @OA\Property(property="statusCode", type="string", example="ERR-OLD_PASSWORD-INVALID"),
     *       @OA\Property(property="message", type="string", example="old password are invalid"),
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
     *    description="password changed successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example="true"),
     *       @OA\Property(property="statusCode", type="string", example="PASSWORD-CHANGED-SUCCESSFULLY"),
     *       @OA\Property(property="message", type="string", example="password changed successfully"),
     *    ),
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
    public function changePasswordSwagger(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6|max:255',
            'new_password' => 'required|string|min:6|max:255',
            'confirm_password' => 'required|string|min:6|max:255|same:new_password',
        ]);

        if ($validator->fails()) {

            return response(
                [
                    'success' => false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->all()

                ], 422);
        }
        try {
            $user = Auth::user();
            if (!password_verify($request->old_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'statusCode' => 'ERR-OLD_PASSWORD-INVALID', // 'ERR-CREDENTIALS-INVALID
                    'message' => 'old password is invalid',
                ], 400);
            }

            $user->password = bcrypt($request->new_password);
            $user->save();
            return response()->json(
                [
                    'success' => true,
                    'statusCode' => 'PASSWORD-CHANGED-SUCCESSFULLY',
                    'message' => 'password changed successfully',
                ],
                200
            );

        } catch (\Exception $err) {
            Log::error($err);
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => 'ERR-UNAVAILABLE',
                    'message' => $err->getMessage(),
                ],
                500
            );
        }

    }

    public function CreatedNewAgentSwagger(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3|string|max:255',
            'surname' => 'required|string|max:255',
            'telephone' => 'required|string|unique:users',
            'email' => 'required|string|email|unique:users',
        ]);

        if(Auth::user()->status == 0){
            return response()->json([
                'success'=>false,
                'statusCode' => 'ERR-USE-NOT-AUTHORIZE', // 'ERR-CREDENTIALS-INVALID
                'message' => 'You cannot authorize to perform this operation',
            ], 400);
        }

        if ($validator->fails()) {
            return response(
                [
                    'success' => false,
                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
                    'message' => $validator->errors()->first()
                ], 422);
        }

        if (Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
            return response()->json([
                'success'=>false,
                'statusCode' => 'ERR-PROFIL-NOT-AUTHORIZE', // 'ERR-CREDENTIALS-INVALID
                'message' => 'Your profil don\'t allow to perfom this operation.',
            ], 400);
        }

        try {
            DB::beginTransaction();
            $user = new User();
            $newPassword = $this->genererChaineAleatoire(8);
            $user->name = $request->name;
            $user->surname = strtoupper($request->surname);
            $user->email = $request->email;
            $user->telephone = $request->telephone;
            $user->login ="+237".$request->telephone;
            $user->status = 1;
            $user->countrie_id = Auth::user()->countrie_id;
            $user->type_user_id = UserRolesEnum::AGENT->value;
            $user->password = bcrypt($newPassword);
            $user->email_verified_at = Carbon::now();
            $user->created_by = Auth::user()->id;
            $user->distributeur_id = $request->distributeur;

            $result = $user->save();
            if ($result) {
                DB::commit();
                return response()->json(
                    [
                        'success' => true,
                        'statusCode' => 'USER-CREATED-SUCCESSFULLY',
                        'message' => 'user created successfully',
                    ],
                    200
                );
            } else {
                DB::rollBack();
                return response()->json([
                    'success'=>false,
                    'statusCode' => 'ERR-UNKNOW', // 'ERR-CREDENTIALS-INVALID
                    'message' => 'User don\t added',
                ], 400);
            }
        } catch (\Exception $err) {
            DB::rollBack();
            Log::error($err);
            return response()->json(
                [
                    'success' => false,
                    'statusCode' => 'ERR-UNAVAILABLE',
                    'message' => $err->getMessage(),
                ],
                500
            );
        }

    }
}
