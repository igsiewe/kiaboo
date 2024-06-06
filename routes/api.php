<?php

use App\Http\Controllers\api\ApiApproDistributeurController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiOMController;
use App\Http\Controllers\api\ApiOperationAgent;
use App\Http\Controllers\api\ApiParrainageController;
use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\api\ApiTransactionsController;
use App\Http\Controllers\api\ApiUserController;
use App\Http\Controllers\api\prod\ApiProdAuthController;
use App\Http\Controllers\api\prod\ApiProdFactureEneoController;
use App\Http\Controllers\api\prod\ApiProdMoMoMoneyController;
use App\Http\Controllers\api\prod\ApiProdM2UController;
use App\Http\Controllers\api\prod\ApiProduction_MoMo;
use App\Http\Controllers\api\prod\ApiProdYooMeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// These routes, can be accessed without token
Route::group(['middleware' => ['cors', 'json.response']], function () {
    // public routes
    //CallBack
    Route::controller(ApiProdMoMoMoneyController::class)->group(function (){
        Route::post('momo/callback','MomoCallBack')->name("MoMoCallback");
    });

    Route::controller(ApiProdYooMeeController::class)->group(function (){
        Route::post('yoomee/callback','YooMeeCallback')->name("YooMeeCallback");
    });

    Route::group(['prefix' => 'v1'], function () {

         Route::controller(ApiAuthController::class)->group(function () {
            //Route::post('user/login', 'Login')->name("Login");
            Route::post('user/login', 'login');
            Route::post('user/login/recrutement', 'loginRecrutement');
            Route::post('user/register', 'registerUser')->name("registerUser");
            Route::post('/user/phone/verify', 'checkNumeroUser')->name("checkNumeroUser");
            Route::post('/agent/phone/verify', 'checkNumeroAgent')->name("checkNumeroAgent");
            Route::post('/user/verify/update/password', 'updateUserPassword')->name("updateUserPassword");

        });

        Route::controller(ApiSmsController::class)->group(function () {
            //Route::get('sms/{tel}/{msg}', 'SendSMS')->name("SendSMS");
        });

        //Swagger
        Route::controller(ApiProdAuthController::class)->group(function (){
            Route::post('authenticate/auth','loginSwagger')->name("loginSwagger");
        });


});

Route::middleware('auth:api')->group(function () {
    Route::group(['prefix' => 'v1'], function () {

        //Swagger
        Route::controller(ApiProdAuthController::class)->group(function (){
           // Route::post('/authenticate/changepassword','changePasswordSwagger')->name("changePasswordSwagger");
        });

        Route::get('/sms/' ,[ApiSmsController::class,'index']) ;
        Route::controller(ApiUserController::class)->group(function () {
            Route::post('user/phone', 'checkNumero')->name("checkNumero");
        });

        Route::controller(ApiAuthController::class)->group(function () {
            Route::post('user/logout', 'logout')->name("logout_mobile");
            Route::post('user/add', 'setNewUser')->name("setNewUser");
            Route::put('user/deactivated/{idUser}', 'deactivatedUser')->name("deactivatedUser");
            Route::put('user/activated/{idUser}', 'activatedUser')->name("activatedUser");
            Route::get('user/data', 'getUserData')->name("getUserData");
            Route::post('user/update/', 'updateUser')->name("updateUser");
            Route::post('user/update/password', 'updatePassword')->name("updatePassword");
            Route::post('/agent/recrutement', 'recrutement')->name("recrutement");

            Route::post('/authenticate/changepassword','changePasswordSwagger')->name("changePasswordSwagger");
        });

        Route::controller(ApiApproDistributeurController::class)->group(function () {
            Route::post('approvisionnement/distributeur', 'initApproDistributeur')->name("initApproDistributeur");
            Route::post('approvisionnement/distributeur/validate/{reference}', 'validatedApproDistributeur')->name("validatedApproDistributeur");
            Route::post('approvisionnement/agent', 'approAgent')->name("approAgent");
            Route::post('approvisionnement/agent/carte', 'approAgentParCarte')->name("approAgentParCarte");
        });

        Route::controller(ApiTransactionsController::class)->group(function () {
            Route::get('transactions/user/{nbre}', 'getLastTransaction')->name("getLastTransaction");
            Route::post('transactions/list', 'getTransaction')->name("getTransaction");
            Route::get('transactions/{id}', 'getTransactionId')->name("getTransactionId");
            Route::get('transactions/user/pending/all', 'getTransactionPending')->name("getTransactionPending");

        });
        Route::controller(ApiParrainageController::class)->group(function () {
            Route::post('parrainage/new/', 'setNewParrainage')->name("setNewParrainage");
        });

        //Commission
        Route::controller(ApiCommissionController::class)->group(function () {
            Route::get('commission/remboursement/agent', 'commissionAgentRembourse')->name("commissionAgentRembourse");
            Route::post('commission/remboursement/agent/filtre', 'commissionAgentRembourseFiltre')->name("commissionAgentRembourseFiltre");
            Route::post('/commission/remboursement/execute', 'setRemboursementCommission')->name("setRemboursementCommission");
        });

        //Orange Money
        Route::controller(ApiOMController::class)->group(function () {
            Route::get('om/custumer/name/{CustomerNumber}', 'OM_NameCustomer')->name("OM_NameCustomer");
            Route::post('operation/om/depot', 'OM_Depot')->name("OM_Depot");
        });

        //SandBox
        Route::group(['prefix' => 'sandbox'], function () {

        });

        //Production

        Route::group(['prefix' => 'prod'], function () {
            //ORANGE
            Route::group(['prefix' => 'om'], function () {
                Route::controller(ApiOperationAgent::class)->group(function () {
                   Route::post('retrait', 'setTransactionRetraitOM')->name("OM_retrait");
                });
            });
            //MTN Mobile Money
            Route::group(['prefix' => 'momo'], function () {
                Route::controller(ApiProdMoMoMoneyController::class)->group(function () {
                    Route::post('depot', 'MOMO_Depot')->name("MOMO_Depot");
                    Route::post('retrait', 'MOMO_Retrait')->name("MOMO_Retrait");

                    Route::get('retrait/status/{referenceID}', 'MOMO_Retrait_Status')->name("MOMO_Retrait_Status");
                    Route::get('depot/status/{referenceID}', 'MOMO_Depot_Status_Api')->name("MOMO_Depot_Status_Api");

                  //  Route::get('retrait/status/trans/{referenceID}', 'MOMO_Retrait_Status_Api')->name("MOMO_Retrait_Status_Api");

                    Route::post('transfert', 'MOMO_Transfert')->name("MOMO_Transfert");
                    Route::get('customer/name/{customerPhone}', 'MOMO_CustomerName')->name("MOMO_CustomerName");

                });
            });
            //M2U
            Route::group(['prefix' => 'm2u'], function () {
                Route::controller(ApiProdM2UController::class)->group(function () {
                    Route::get('custumer/name/{CustomerNumber}', 'M2U_NameCustomer')->name("M2U_PROD_NameCustomer");
                    Route::get('custumer/wallet/{CustomerNumber}', 'M2U_WalletCustomer')->name("M2U_PROD_WalletCustomer");
                    Route::post('depot', 'M2U_depot')->name("M2U_PROD_depot");
                    Route::post('transfertstatus', 'M2U_getTransfertStatus')->name("M2U_PROD_getTransfertStatus");
                    Route::post('retrait/CPPayCash', 'M2U_RetraitCPPayCash')->name("M2U_PROD_RetraitCPPayCash");

                    Route::post('CashBackStatus', 'M2U_CashBackStatus')->name("M2U_CashBackStatus");
                    Route::post('retrait/ExecuteCashBack', 'M2U_ExecuteCashBack')->name("M2U_PROD_ExecuteCashBack");
                });
            });

            //YOOMEE
            Route::group(['prefix' => 'yoomee'], function () {
                Route::controller(ApiProdYooMeeController::class)->group(function () {
                    Route::get('custumer/name/{customerPhone}', 'YooMee_getUserInfo')->name("YooMee_getUserInfo");
                    Route::post('depot', 'YooMee_depot')->name("YooMee_depot");
                    Route::post('retrait', 'YooMee_retrait')->name("YooMee_retrait");
                    Route::get('retrait/status/{referenceID}', 'YooMee_getRetraitStatus')->name("YooMee_retraitStatus");
                });
            });

            //ENEO
            Route::group(['prefix' => 'eneo'], function () {
                Route::controller(ApiProdFactureEneoController::class)->group(function () {
                    Route::get('facture/status/{numFacture}', 'ENEO_CheckFactureStatus')->name("ENEO_CheckFactureStatus");
                    Route::post('payment/facture', 'ENEO_PayMentFacture')->name("ENEO_PayMentFacture");
                });
            });
        });
      });
    });
});
