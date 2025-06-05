<?php

namespace App\Http\Controllers\api\prod;

use App\Http\Controllers\api\ApiAuthController;
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
use OpenApi\Annotations as OA;
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
     *       @OA\Property(property="login", type="string",format="string", example="+237659657424"),
     *       @OA\Property(property="password", type="string", format="password", example="oLpIKO521@12"),
     *    ),
     * ),
     * @OA\Response(
     *     response=200,
     *     description="successful login user",
     *     @OA\JsonContent(
     *        @OA\Property(property="success", type="boolean", example="true"),
     *        @OA\Property(property="statusCode", type="string", example="LOGIN-SUCCESS"),
     *        @OA\Property(property="message", type="string", example="successful login user"),
     *        @OA\Property(property="access_token", type="string", example="xxxxxxxxxxxxxxxxxxxx"),
     *        @OA\Property(
     *                   property="user",
     *                   type="object",
     *                   @OA\Property(property="name", type="string", example="houvre"),
     *                   @OA\Property(property="surname", type="string", example="autre"),
     *                 ),
     *       ),
     *  ),
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
                    'message' => $validator->errors()->first()

                ], 422);
        }

        // Here, we get the user credentials from the request
        $credentials = [
            'login' => $request->login,
            'password' => $request->password,
            'status' => 1,
            'status_delete'=>0,
            'type_user_id' => UserRolesEnum::AGENT->value,
         //   'application'=>2,
        ];

        if (Auth::attempt($credentials)) {
            $users = Auth::user();
            $user = User::where('id', $users->id)->select('id', 'name', 'surname', 'telephone', 'login', 'email','balance_before', 'balance_after','total_commission', 'last_amount','sous_distributeur_id','date_last_transaction','moncodeparrainage')->first();
            DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
            $token = $user->createToken('kiaboo');
            $access_token = $token->accessToken;
            $user->last_connexion = Carbon::now();
            $user->save();
            $delay=Carbon::parse($token->token->expires_at)->diffInSeconds(Carbon::now());
            return $this->respondWithTokenSwagger($access_token, $user,$delay);
        }

        return response()->json([
            'success'=>false,
            'statusCode' => 'ERR-CREDENTIALS-INVALID', // 'ERR-CREDENTIALS-INVALID
            'message' => 'login credentials are invalid',
        ], 400);
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
            $user->updated_at = Carbon::now();
            $user->updated_by = Auth::user()->id;
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
//
//    /**
//     * @OA\Post(
//     * path="/api/v1/agent/add",
//     * summary="Add new agent",
//     * description="Create a new agent to carry out the transaction",
//     * security={{"bearerAuth":{}}},
//     * tags={"Agent"},
//     * @OA\RequestBody(
//     *    required=true,
//     *    description="Data of new agent",
//     *    @OA\JsonContent(
//     *       required={"name","surname","telephone","email"},
//     *       @OA\Property(property="name", type="string", example="DUPONT"),
//     *       @OA\Property(property="surname", type="string", example="Henry"),
//     *       @OA\Property(property="telephone", type="string", example="659657424"),
//     *       @OA\Property(property="email", type="email", example="devops@kiaboo.net"),
//     *    ),
//     * ),
//     *
//     * @OA\Response(
//     *     response=422,
//     *     description="attribute invalid or mistyped",
//     *     @OA\JsonContent(
//     *        @OA\Property(property="success", type="boolean", example="false"),
//     *        @OA\Property(property="statusCode", type="string", example="ERR-ATTRIBUTES-INVALID"),
//     *        @OA\Property(property="message", type="string", example="attribute not valid"),
//     *     )
//     *  ),
//     * @OA\Response(
//     *    response=200,
//     *    description="Agent created successfully",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="true"),
//     *       @OA\Property(property="statusCode", type="string", example="AGENT-CREATED-SUCCESSFULLY"),
//     *       @OA\Property(property="message", type="string", example="Agent created successfully"),
//     *    ),
//     * ),
//     * @OA\Response(
//     *    response=500,
//     *    description="an error occurred",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
//     *       @OA\Property(property="message", type="string", example="an error occurred"),
//     *    )
//     *  )
//     * )
//     */
//    public function CreatedNewAgentSwagger(Request $request)
//    {
//        $validator = Validator::make($request->all(), [
//            'name' => 'required|min:3|string|max:255',
//            'surname' => 'required|string|max:255',
//            'telephone' => 'required|string|unique:users',
//            'email' => 'required|string|email|unique:users',
//        ]);
//
//        if(Auth::user()->status == 0){
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-USE-NOT-AUTHORIZE', // 'ERR-CREDENTIALS-INVALID
//                'message' => 'You cannot authorize to perform this operation',
//            ], 400);
//        }
//
//        if ($validator->fails()) {
//            return response(
//                [
//                    'success' => false,
//                    'statusCode' => 'ERR-ATTRIBUTES-INVALID',
//                    'message' => $validator->errors()->first()
//                ], 422);
//        }
//
//        if (Auth::user()->type_user_id != UserRolesEnum::DISTRIBUTEUR->value) {
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-PROFIL-NOT-AUTHORIZE', // 'ERR-CREDENTIALS-INVALID
//                'message' => 'Your profil don\'t allow to perfom this operation.',
//            ], 400);
//        }
//
//        try {
//            DB::beginTransaction();
//
//            $user = new User();
//
//            $file = new ApiAuthController();
//            $newPassword = $file->genererChaineAleatoire(8);
//            $codeParrainage ="KI".strtoupper($file->genererChaineAleatoire(12));
//            $user->name = strtoupper($request->name);
//            $user->surname = $request->surname;
//            $user->email = $request->email;
//            $user->telephone = $request->telephone;
//            $user->login ="+237".$request->telephone;
//            $user->status = 1;
//            $user->countrie_id = Auth::user()->countrie_id;
//            $user->type_user_id = UserRolesEnum::AGENT->value;
//            $user->password = bcrypt($newPassword);
//            $user->email_verified_at = Carbon::now();
//            $user->created_by = Auth::user()->id;
//            $user->distributeur_id =Auth::user()->distributeur_id;// $request->distributeur;
//            $user->codeparrainage = $codeParrainage;
//            $user->moncodeparrainage = $codeParrainage;
//            $user->quartier= Auth::user()->quartier;
//            $user->ville_id= Auth::user()->ville_id;
//            $user->adresse= Auth::user()->adresse;
//            $user->application =Auth::user()->application;
//            $result = $user->save();
//            if ($result) {
//                DB::commit();
//                return response()->json(
//                    [
//                        'success' => true,
//                        'statusCode' => 'USER-CREATED-SUCCESSFULLY',
//                        'message' => 'user created successfully',
//                    ],
//                    200
//                );
//            } else {
//                DB::rollBack();
//                return response()->json([
//                    'success'=>false,
//                    'statusCode' => 'ERR-UNKNOW', // 'ERR-CREDENTIALS-INVALID
//                    'message' => 'User don\t added',
//                ], 400);
//            }
//        } catch (\Exception $err) {
//            DB::rollBack();
//            Log::error($err);
//            return response()->json(
//                [
//                    'success' => false,
//                    'statusCode' => 'ERR-UNAVAILABLE',
//                    'message' => $err->getMessage(),
//                ],
//                500
//            );
//        }
//
//    }
//
//    /**
//     * @OA\Get(
//     * path="/api/v1/agent/list",
//     * summary="list of all agents",
//     * description="list of all agents",
//     * tags={"Agent"},
//     * security={{"bearerAuth":{}}},
//     * @OA\Response(
//     *    response=200,
//     *    description="agent list successful",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="true"),
//     *       @OA\Property(property="statusCode", type="string", example="SUCCESS-LIST-AGENT"),
//     *       @OA\Property(property="message", type="string", example="agent list successful"),
//     *       @OA\Property(property="number", type="string", example="number of agent found"),
//     *       @OA\Property(property="totalBalance", type="integer", example="balance"),
//     *       @OA\Property(property="data", type="object", example="list of agents"),
//     *    )
//     * ),
//     * @OA\Response(
//     *    response=404,
//     *    description="agent not found",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-AGENT-NOT-FOUND"),
//     *       @OA\Property(property="message", type="string", example="agent not found"),
//     *    )
//     *  ),
//     * @OA\Response(
//     *    response=500,
//     *    description="an error occurred",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
//     *       @OA\Property(property="message", type="string", example="an error occurred"),
//     *    )
//     *  ),
//     * )
//     * )
//     */
//
//    public function listAgentSwagger(){
//        try{
//            $listAgent = User::where("type_user_id", UserRolesEnum::AGENT->value)->where("distributeur_id", Auth::user()->distributeur_id)
//            ->select('id', 'name', 'surname', 'telephone', 'email', 'created_at', 'status', 'balance_after as balance')
//                ->orderBy("name", "ASC")
//                ->orderBy("surname", "ASC")
//                ->get();
//            if($listAgent->count() == 0){
//                return response()->json([
//                    'success'=>false,
//                    'statusCode' => 'ERR-AGENT-NOT-FOUND', // 'ERR-CREDENTIALS-INVALID
//                    'message' => 'Agent not found',
//                ], 404);
//            }
//            return response()->json([
//                'success'=>true,
//                'statusCode' => 'SUCCESS-LIST-AGENT', // 'ERR-CREDENTIALS-INVALID
//                'message' => 'Agent list successful',
//                'number'=>$listAgent->count(),
//                'totalBalance'=>$listAgent->sum('balance'),
//                'data'=>$listAgent
//            ], 200);
//        }catch(\Exception $err){
//            Log::error($err);
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-UNAVAILABLE', // 'ERR-CREDENTIALS-INVALID
//                'message' => $err->getMessage(),
//            ], 500);
//        }
//
//    }
//
//    /**
//     * @OA\Put(
//     * path="/api/v1/agent/block/{phone}",
//     * summary="Blocked an agent ",
//     * description="Blocked an agent  ",
//     * tags={"Agent"},
//     * security={{"bearerAuth":{}}},
//     * @OA\Parameter(
//     *     name="phone",
//     *     description="Login or phone number of agent",
//     *     required=true,
//     *     in="path",
//     *     @OA\Schema(
//     *        type="string"
//     *     )
//     * ),
//     * @OA\Response(
//     *    response=200,
//     *    description="Agent blocked successfuly",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="true"),
//     *       @OA\Property(property="statusCode", type="string", example="SUCCESS-AGENT-BLOCKED"),
//     *       @OA\Property(property="message", type="string", example="user successfuly delete"),
//     *    )
//     * ),
//     * @OA\Response(
//     *    response=400,
//     *    description="Agent is already blocked",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-AGENT-ALREADY-BLOCKED"),
//     *       @OA\Property(property="message", type="string", example="This agent is already blocked"),
//     *    )
//     *  ),
//     *  @OA\Response(
//     *     response=403,
//     *     description="you do not have the necessary permissions",
//     *     @OA\JsonContent(
//     *        @OA\Property(property="success", type="boolean", example="false"),
//     *        @OA\Property(property="statusCode", type="string", example="ERR-NOT-PERMISSION"),
//     *        @OA\Property(property="message", type="string", example="you do not have the necessary permissions"),
//     *     )
//     *   ),
//     * @OA\Response(
//     *    response=404,
//     *    description="agent not found ",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-AGENT-NOT-FOUND"),
//     *       @OA\Property(property="message", type="string", example="agent not found "),
//     *    )
//     *  ),
//     * @OA\Response(
//     *    response=500,
//     *    description="an error occurred",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
//     *       @OA\Property(property="message", type="string", example="an error occurred"),
//     *    )
//     *  ),
//     * )
//     * )
//     */
//    public function blockAgentSwagger($phone){
//        try{
//            $agent = User::where("telephone", $phone)->where("type_user_id",UserRolesEnum::AGENT->value)->where('distributeur_id',Auth::user()->distributeur_id);
//            if($agent->count()>0){
//                if($agent->first()->status==0){
//                    return response()->json([
//                        'success'=>false,
//                        'statusCode' => 'ERR-AGENT-ALREADY-BLOCKED', // 'ERR-CREDENTIALS-INVALID
//                        'message' => 'This agent is already blocked',
//                    ], 400);
//                }
//                if($agent->first()->distributeur_id !=Auth::user()->distributeur_id){
//                    return response()->json([
//                        'success'=>false,
//                        'statusCode' => 'ERR-PERMISSION-DENIED', // 'ERR-CREDENTIALS-INVALID
//                        'message' => 'you do not have the necessary permissions',
//                    ], 403);
//                }
//                $update = $agent->update([
//                    "status"=>0,
//                    "updated_at"=>Carbon::now(),
//                    "updated_by"=>Auth::user()->id
//                ]);
//                return response()->json([
//                    'success'=>true,
//                    'statusCode' => 'SUCCESS-AGENT-BLOCKED',
//                    'message' => 'Agent blocked successfully',
//                ], 200);
//            }
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-AGENT-NOT-FOUND', // 'ERR-CREDENTIALS-INVALID
//                'message' => 'Agent not found',
//            ], 404);
//
//        }catch(\Exception $err){
//            Log::error($err);
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-UNAVAILABLE', // 'ERR-CREDENTIALS-INVALID
//                'message' => $err->getMessage(),
//            ], 500);
//        }
//
//    }
//
//
//    /**
//     * @OA\Put(
//     * path="/api/v1/agent/unblock/{phone}",
//     * summary="Unblocked an agent ",
//     * description="Unblocked an agent  ",
//     * tags={"Agent"},
//     * security={{"bearerAuth":{}}},
//     * @OA\Parameter(
//     *     name="phone",
//     *     description="Login or phone number of agent",
//     *     required=true,
//     *     in="path",
//     *     @OA\Schema(
//     *        type="string"
//     *     )
//     * ),
//     * @OA\Response(
//     *    response=200,
//     *    description="Agent unblocked successfuly",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="true"),
//     *       @OA\Property(property="statusCode", type="string", example="SUCCESS-AGENT-UNBLOCKED"),
//     *       @OA\Property(property="message", type="string", example="user successfuly delete"),
//     *    )
//     * ),
//     * @OA\Response(
//     *    response=400,
//     *    description="Agent is already unblocked",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-AGENT-ALREADY-UNBLOCKED"),
//     *       @OA\Property(property="message", type="string", example="This agent is already unblocked"),
//     *    )
//     *  ),
//     *  @OA\Response(
//     *     response=403,
//     *     description="you do not have the necessary permissions",
//     *     @OA\JsonContent(
//     *        @OA\Property(property="success", type="boolean", example="false"),
//     *        @OA\Property(property="statusCode", type="string", example="ERR-NOT-PERMISSION"),
//     *        @OA\Property(property="message", type="string", example="you do not have the necessary permissions"),
//     *     )
//     *   ),
//     * @OA\Response(
//     *    response=404,
//     *    description="agent not found ",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-AGENT-NOT-FOUND"),
//     *       @OA\Property(property="message", type="string", example="agent not found "),
//     *    )
//     *  ),
//     * @OA\Response(
//     *    response=500,
//     *    description="an error occurred",
//     *    @OA\JsonContent(
//     *       @OA\Property(property="success", type="boolean", example="false"),
//     *       @OA\Property(property="statusCode", type="string", example="ERR-UNAVAILABLE"),
//     *       @OA\Property(property="message", type="string", example="an error occurred"),
//     *    )
//     *  ),
//     * )
//     * )
//     */
//    public function unblockAgentSwagger($phone){
//        try{
//            $agent = User::where("telephone", $phone)->where("type_user_id",UserRolesEnum::AGENT->value)->where('distributeur_id',Auth::user()->distributeur_id);
//            if($agent->count()>0){
//                if($agent->first()->status==1){
//                    return response()->json([
//                        'success'=>false,
//                        'statusCode' => 'ERR-AGENT-ALREADY-UNBLOCKED', // 'ERR-CREDENTIALS-INVALID
//                        'message' => 'This agent is already unblocked',
//                    ], 400);
//                }
//                if($agent->first()->distributeur_id !=Auth::user()->distributeur_id){
//                    return response()->json([
//                        'success'=>false,
//                        'statusCode' => 'ERR-PERMISSION-DENIED', // 'ERR-CREDENTIALS-INVALID
//                        'message' => 'you do not have the necessary permissions',
//                    ], 403);
//                }
//                $update = $agent->update([
//                    "status"=>1,
//                    "updated_at"=>Carbon::now(),
//                    "updated_by"=>Auth::user()->id
//                ]);
//                return response()->json([
//                    'success'=>true,
//                    'statusCode' => 'SUCCESS-AGENT-UNBLOCKED',
//                    'message' => 'Agent unblocked successfully',
//                ], 200);
//            }
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-AGENT-NOT-FOUND', // 'ERR-CREDENTIALS-INVALID
//                'message' => 'Agent not found',
//            ], 404);
//
//        }catch(\Exception $err){
//            Log::error($err);
//            return response()->json([
//                'success'=>false,
//                'statusCode' => 'ERR-UNAVAILABLE', // 'ERR-CREDENTIALS-INVALID
//                'message' => $err->getMessage(),
//            ], 500);
//        }
//
//    }
}
