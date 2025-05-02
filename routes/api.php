<?php

use App\Http\Controllers\api\ApiApproDistributeurController;
use App\Http\Controllers\api\ApiAuthController;
use App\Http\Controllers\api\ApiCommissionController;
use App\Http\Controllers\api\ApiImageUploadController;
use App\Http\Controllers\api\ApiNotification;
use App\Http\Controllers\api\ApiOMControllerSave;
use App\Http\Controllers\api\ApiOperationAgent;
use App\Http\Controllers\api\ApiParrainageController;
use App\Http\Controllers\api\ApiProspectController;
use App\Http\Controllers\api\ApiSmsController;
use App\Http\Controllers\api\ApiTransactionsController;
use App\Http\Controllers\api\ApiUserController;
use App\Http\Controllers\api\ApiVersion;
use App\Http\Controllers\api\ApiVilleController;
use App\Http\Controllers\api\prod\ApiKiabooController;
use App\Http\Controllers\api\prod\ApiOMCallBanckController;
use App\Http\Controllers\api\prod\ApiOMController;
use App\Http\Controllers\api\prod\ApiProdAuthController;
use App\Http\Controllers\api\prod\ApiProdFactureEneoController;
use App\Http\Controllers\api\prod\ApiProdMoMoMoneyController;
use App\Http\Controllers\api\prod\ApiProdM2UController;
use App\Http\Controllers\api\prod\ApiProdOrangeMoneyController;
use App\Http\Controllers\api\prod\ApiProdRemboursementPaymentController;
use App\Http\Controllers\api\prod\ApiProdTransactionsController;
//use App\Http\Controllers\api\prod\ApiProduction_MoMo;
use App\Http\Controllers\api\prod\ApiProdYooMeeController;
use App\Http\Controllers\api\prod\ApiStripe;
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
    Route::controller(ApiOMCallBanckController::class)->group(function (){
        Route::get('callback/om/cico', 'OMCallBack')->name("OMCallBack");
    });

    Route::controller(ApiVersion::class)->group(function (){
        Route::get('version/upload/new', 'getVersion')->name("getVersion");
    });

    Route::controller(ApiVilleController::class)->group(function (){
        Route::get('v1/ville/list/d/cm', 'getListVille')->name("getListVille");
    });

    Route::controller(ApiProdYooMeeController::class)->group(function (){
        Route::post('yoomee/callback','YooMeeCallback')->name("YooMeeCallback");
    });

    Route::group(['prefix' => 'v1'], function () {
        Route::controller(ApiProspectController::class)->group(function (){
            Route::post('prospect/new/po', 'setNewProspect')->name("setNewProspect");
        });
         Route::controller(ApiAuthController::class)->group(function () {
            //Route::post('user/login', 'Login')->name("Login");
            Route::post('user/login', 'login');
            Route::post('user/login/recrutement', 'loginRecrutement');
            Route::post('user/register', 'registerUser')->name("registerUser");
            Route::post('/user/phone/verify', 'checkNumeroUser')->name("checkNumeroUser");
             Route::post('/user/phone/verify/inscription', 'checkNumeroInscription')->name("checkNumeroInscription");
            Route::post('/agent/phone/verify', 'checkNumeroAgent')->name("checkNumeroAgent");
            Route::post('/user/verify/update/password', 'updateUserPassword')->name("updateUserPassword");
           // Route::post('authenticate/auth','loginSwagger')->name("loginSwagger");
        });

        Route::controller(ApiSmsController::class)->group(function () {
            //Route::get('sms/{tel}/{msg}', 'SendSMS')->name("SendSMS");
        });

        //Swagger
        Route::controller(ApiProdAuthController::class)->group(function (){
            Route::post('authenticate/auth','loginSwagger')->name("loginSwagger");
        });
});
Route::controller(ApiImageUploadController::class)->group(function (){
    Route::post('public/assets/upload','upload')->name("uploadImage");
});
Route::middleware('auth:api')->group(function () {

    Route::group(['prefix' => 'v1'], function () {
        Route::controller(ApiProdAuthController::class)->group(function (){
            Route::post('authenticate/changepassword','changePasswordSwagger')->name("changePasswordSwagger");
            Route::post('agent/add','CreatedNewAgentSwagger')->name("CreatedNewAgentSwagger");
            Route::get('agent/list','listAgentSwagger')->name("listAgentSwagger");
            Route::put('agent/block/{phone}','blockAgentSwagger')->name("blockAgentSwagger");
            Route::put('agent/unblock/{phone}','unblockAgentSwagger')->name("unblockAgentSwagger");
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
            Route::post('/agent/update/donnee', 'updateUserInfo')->name("updateUserInfo");
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
            Route::get('transactions/user/fail/all', 'getTransactionFail')->name("getTransactionFail");

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

        Route::controller(ApiNotification::class)->group(function () {
            Route::post('send/notification', 'SendPushNotification')->name("SendPushNotification");
        });

        //SandBox
        Route::group(['prefix' => 'sandbox'], function () {

        });

        //Production

        Route::group(['prefix' => 'prod'], function () {
            //Kiaboo
            Route::group(['prefix' => 'kiaboo'], function () {
                Route::controller(ApiKiabooController::class)->group(function () {
                    Route::get('agent/info/{phone}', 'getAgentInfo')->name("getAgentInfo");
                    Route::post('transfert/pay', 'setTransfert')->name("setTransfert");
                });
            });
            //Transaction

            Route::group(['prefix' => 'transactions'], function () {
                Route::controller(ApiProdTransactionsController::class)->group(function () {
                    Route::post('/', 'getTransactionSwagger')->name("getTransactionSwagger");
                    Route::get('/{nbre}', 'getLastTransactionSwagger')->name("getLastTransactionSwagger");
                    Route::get('/dashboard/data', 'getDataDashBoard')->name("getDataDashBoard");
                });
            });

            Route::group(['prefix' => 'payment'], function () {
                Route::controller(ApiProdRemboursementPaymentController::class)->group(function () {
                    Route::post('/refund', 'getListRemboursement')->name("getListRemboursement");
                    Route::post('/refund/search', 'getListRemboursementSearch')->name("getListRemboursementSearch");

                });
            });

            //ORANGE
            Route::group(['prefix' => 'om'], function () {
                Route::controller(ApiOperationAgent::class)->group(function () {
                  // Route::post('retrait', 'setTransactionRetraitOM')->name("OM_retrait");
                });

                Route::controller(ApiProdOrangeMoneyController::class)->group(function () {
                    Route::post('payment', 'OM_Payment')->name("OM_Payment");
                    Route::get('payment/push/{transactionId}', 'OM_Payment_Push')->name("OM_Payment_Push");
                    Route::get('payment/status/{transactionId}', 'OM_Payment_Status')->name("OM_Payment_Status");
                });

                Route::controller(ApiOMController::class)->group(function () {
                    Route::get('customer/name/{customerNumber}', 'OM_CustomerName')->name("OM_CustomerName");
                    Route::post('cashin/pay', 'OM_Depot')->name("OM_Depot");
                    Route::post('cashout/pay', 'OM_Retrait')->name("OM_Retrait");
                    Route::post('payment/pay', 'OM_Payment')->name("OM_Payments");

                    Route::get('cashin/status/{referenceID}', 'OM_Depot_Status')->name("OM_Depot_Status");
                    Route::get('cashout/status/{referenceID}', 'OM_Retrait_Status')->name("OM_Retrait_Status");
                });

            });
            //MTN Mobile Money
            Route::group(['prefix' => 'momo'], function () {
                Route::controller(ApiProdMoMoMoneyController::class)->group(function () {
                    Route::post('depot', 'MOMO_Depot')->name("MOMO_Depot");
                    Route::post('retrait', 'MOMO_Retrait')->name("MOMO_Retrait");
                    Route::post('payment', 'MOMO_Payment')->name("MOMO_Payment");
                    Route::get('payment/status/{transactionId}', 'MOMO_Payment_Status')->name("MOMO_Payment_Status");

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

            //Stripe
            Route::group(['prefix' => 'stripe'], function () {
                Route::controller(ApiStripe::class)->group(function () {
                    Route::post('payment/init', 'initApproDistributeurSkype')->name("initApproDistributeurSkype");
                    Route::get('payment/validation/{reference}', 'validateTopUpDistributeurSkype')->name("validateTopUpDistributeurSkype");
                });
            });
        });
      });
    });
});
