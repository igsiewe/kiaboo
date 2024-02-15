<?php

use App\Http\Controllers\api\ApiApproDistributeurController;
use App\Http\Controllers\api\ApiApproSousDistributeurController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiM2UController;
use App\Http\Controllers\api\ApiMoMoMoneyController;
use App\Http\Controllers\api\ApiOMController;
use App\Http\Controllers\api\ApiOperationAgent;
use App\Http\Controllers\api\ApiOrangeMoneyController;
use App\Http\Controllers\api\ApiParrainageController;
use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\api\ApiTransactionsController;
use App\Http\Controllers\api\ApiUserController;
use App\Http\Controllers\api\prod\ApiProdM2UController;
use App\Http\Controllers\api\prod\ApiProduction_MoMo;
use Illuminate\Http\Request;
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

    Route::group(['prefix' => 'v1'], function () {

         Route::controller(ApiAuthController::class)->group(function () {
            Route::post('user/login', 'Login')->name("Login");
            Route::post('user/register', 'registerUser')->name("registerUser");
            Route::post('/user/phone/verify', 'checkNumeroUser')->name("checkNumeroUser");
            Route::post('/user/verify/update/password', 'updateUserPassword')->name("updateUserPassword");
        });

        Route::controller(ApiSmsController::class)->group(function () {
            //Route::get('sms/{tel}/{msg}', 'SendSMS')->name("SendSMS");
        });
});

Route::middleware('auth:api')->group(function () {


    Route::group(['prefix' => 'v1'], function () {

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
            Route::post('/commission/remboursement/execute', 'setRemboursementCommission')->name("setRemboursementCommission");
        });

        //Orange Money
        Route::controller(ApiOMController::class)->group(function () {
            Route::get('om/custumer/name/{CustomerNumber}', 'OM_NameCustomer')->name("OM_NameCustomer");
            Route::post('operation/om/depot', 'OM_Depot')->name("OM_Depot");
        });

        //Production
        Route::group(['prefix' => 'prod'], function () {
            //MTN Mobile Money
            Route::group(['prefix' => 'momo'], function () {
                Route::controller(ApiMoMoMoneyController::class)->group(function () {
                    Route::post('depot', 'MOMO_Depot')->name("MOMO_Depot");
                    Route::post('retrait', 'MOMO_Retrait')->name("MOMO_Retrait");
                    Route::get('retrait/status/{referenceID}', 'MOMO_Retrait_CheckStatus')->name("MOMO_Retrait_CheckStatus");
                    Route::post('transfert', 'MOMO_Transfert')->name("MOMO_Transfert");
                    Route::get('customer/name/{customerPhone}', 'MOMO_CustomerName')->name("MOMO_CustomerName");
                  //  Route::get('momo/retrait/callback/status/{referenceID}', 'MOMO_Retrait_CallBack')->name("MOMO_Retrait_CallBack");
                });
            });
            //M2U
            Route::group(['prefix' => 'm2u'], function () {
                Route::controller(ApiProdM2UController::class)->group(function () {
                    Route::get('custumer/name/{CustomerNumber}', 'M2U_NameCustomer')->name("M2U_PROD_NameCustomer");
                    Route::post('depot', 'M2U_depot')->name("M2U_PROD_depot");
                    Route::post('transfertstatus', 'M2U_getTransfertStatus')->name("M2U_PROD_getTransfertStatus");
                    Route::post('retrait/CPPayCash', 'M2U_RetraitCPPayCash')->name("M2U_PROD_RetraitCPPayCash");
                });
            });
        });
      });
    });
});
