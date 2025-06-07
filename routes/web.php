<?php

use App\Http\Controllers\Auth\Google2FAController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\web\WebAgentController;
use App\Http\Controllers\web\WebApproAgentController;
use App\Http\Controllers\web\WebApproDistributeurController;
use App\Http\Controllers\web\WebAuthController;
use App\Http\Controllers\web\WebCommissionController;
use App\Http\Controllers\web\WebDashBoardController;
use App\Http\Controllers\web\WebDistributeurController;
use App\Http\Controllers\web\WebExportExcelController;
use App\Http\Controllers\web\WebProspectontroller;
use App\Http\Controllers\web\WebReconciliationController;
use App\Http\Controllers\web\WebServiceController;
use App\Http\Controllers\web\WebTransactionsController;
use App\Http\Controllers\web\WebUtilisateurController;
use App\Http\Enums\UserRolesEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', function () {
    return view('index');
});
//Auth::routes();
Route::any('/', [WebAuthController::class, 'login'])->name('login');
Route::get('/reload-captcha', [WebAuthController::class, 'reloadCaptcha']);
Route::middleware(['auth','checkStatus'])->group(function (){
    Route::middleware(['auth', 'role:'.UserRolesEnum::SUPADMIN->name])->group(function () {
        // Routes protégées
    });
    Route::any('/dashboard', [WebDashBoardController::class,'dashboard'])->name("dashboard");
    Route::any('/logout', [WebAuthController::class, 'logout'])->name('fermer');
    Route::get('https://kiaboo.net', function () {
        Session::flush();
        Auth::logout();
        return Redirect::to("https://kiaboo.net");
    })->name("siteweb");

    Route::group(['prefix' => 'partenaires'], function () {
        Route::controller(WebProspectontroller::class)->group(function () {
            Route::any('list/prospect', 'getListProspect')->name('listProspect');
            Route::any('prospect/validate/{id}', 'valideProspect')->name('valideProspect');
            Route::any('prospect/rejeted/{id}', 'rejectedProspect')->name('rejectedProspect');
            Route::any('prospect/edit/{id}', 'editProspect')->name('editProspect');
        });
    });

    Route::group(['prefix' => 'approvisionnement'], function () {
        Route::controller(WebApproAgentController::class)->group(function () {
            Route::middleware(['routedealer'])->group(function () {
                Route::any('/agent/new', 'setTopUpAgent')->name("setTopUpAgent");
            });
            Route::get('/agent/edit/{edit}', 'getDetailAgentTopUpd')->name("getDetailAgentTopUpd");
        });
        Route::controller(WebDistributeurController::class)->group(function () {
            Route::get('/distributeur/edit/{edit}', 'getDetailDistributeurTopUpd')->name("getDetailDistributeurTopUpd");
            Route::get('/distributeur/list', 'getListDistributeur')->name("listDistributeur");
            Route::get('/distributeur/list/filtre', 'getListFiltreDistributeur')->name("getListFiltreDistributeur");
            Route::any('distributeur/agent/list/{id}', 'agentDistributeur')->name('agentDistributeur');
            Route::middleware(['routeadministrateur'])->group(function () {
                Route::any('distributeur/bloque/{id}', 'bloqueDistributeur')->name('bloqueDistributeur');
                Route::any('distributeur/debloque/{id}', 'debloqueDistributeur')->name('debloqueDistributeur');
                Route::any('distributeur/edit/donnees/{edit}', 'showDistributeur')->name("showDistributeur");
                Route::any('distributeur/create', 'setNewDistributeur')->name('setNewDistributeur');
                Route::any('distributeur/update/{id}', 'setUpdateDistributeur')->name('setUpdateDistributeur');
                Route::any('distributeur/delete/{id}', 'deleteDistributeur')->name('deleteDistributeur');
            });
        });
        Route::controller(WebApproDistributeurController::class)->group(function () {
            Route::any('/distributor/list', 'getApproDistributor')->name("getApproDistributor");
            Route::any('/approvisionnement/search', 'listApprovisionnementFiltre')->name('listApprovisionnement.filtre');
            Route::any('/approvisionnement/distributeur/init/', 'initApproDistributeur')->name("approDistributeurInit");
            Route::any('/distributeur/topup/{id}/{action}', 'getTopUpDetailDistributeur')->name('topupDistributeur.detail');
            Route::middleware(['routebackoffice'])->group(function () {
                Route::any('/approvisionnement/cancel/{id}', 'CancelTopUpDistributeur')->name("CancelTopUpDistributeur");
                Route::any('/approvisionnement/validate/{id}', 'validateTopUpDistributeur')->name("validateTopUpDistributeur");
            });

        });
    });

    Route::group(['prefix' => 'transactions'], function () {
        Route::controller(WebTransactionsController::class)->group(function () {
            Route::any('/list', 'listTransactions')->name("listTransactions");
            Route::any('/topup/agent', 'topupAgent')->name("topupAgent");
            Route::any('/topup/agent/search', 'getTopUpAgentFiltre')->name('topupAgent.filtre');
            Route::any('/topup/agent/{id}', 'getTopUpDetail')->name('topupAgent.detail');
            Route::get('/transaction/edit/{id}', 'getDetailTransaction')->name("getDetailTransaction");
            Route::any('/transaction/search', 'listTransactionsFiltre')->name("listTransactions.filtre");

        });
    });

    Route::group(['prefix' => 'reconciliation'], function () {
        Route::controller(WebReconciliationController::class)->group(function () {
            Route::any('/transactions/attente', 'transactionEnattente')->name("transactionEnattente");
            Route::any('/transactions/attente/search', 'transactionEnattenteSearch')->name("transactionEnattente.filtre");
            Route::any('/transactions/corrigees', 'transactionCorrigees')->name("transactionCorrigees");
            Route::get('/transactions/edit/{id}', 'getDetailTransaction')->name("getDetailTransactionEnAttente");
        });
    });
    Route::group(['prefix' => 'commissions'], function () {
        Route::controller(WebCommissionController::class)->group(function () {
            Route::any('/grille/', 'grilleCommission')->name("grilleCommission");
            Route::any('/percues/agent', 'listAgentCommissions')->name("listAgentCommissions");
            Route::any('/percues/agent/search', 'listAgentCommissionsSearch')->name("listAgentCommissions.search");
            Route::get('/percues/agent/detail/{reference}', 'getDetailCommission')->name("getDetailCommission");

            Route::any('/percues/distributeur', 'listDistributeurCommissions')->name("listDistributeurCommissions");
            Route::any('/percues/distributeur/search', 'listDistributeurCommissionsSearch')->name("listDistributeurCommissionsSearch.search");
            Route::any('/percues/distributeur/execute', 'setRemboursementCommissionDistributeur')->name("setRemboursementCommissionDistributeur");
            Route::get('/percues/distributeur/detail/{reference}', 'getDetailCommissionDistributeur')->name("getDetailCommissionDistributeur");
            Route::any('/cancel/{id}', 'deleteCommission')->name('deleteCommission');
            Route::any('/grille/new/commission', 'addNewCommission')->name('addNewCommission');
        });
    });
    Route::group(['prefix' => 'utilisateur'], function () {
        Route::middleware(['notauthorizefordealer'])->group(function () {
            Route::controller(WebUtilisateurController::class)->group(function () {
                Route::any('/list', 'listUtilisateurs')->name("listUtilisateurs");
                Route::any('/create', 'setNewUtilisateur')->name('setNewUtilisateur');
                Route::any('/bloque/{id}', 'bloqueUtilisateur')->name('bloqueUtilisateur');
                Route::any('/debloque/{id}', 'debloqueUtilisateur')->name('debloqueUtilisateur');
                Route::any('/delete/{id}', 'deleteUtilisateur')->name('deleteUtilisateur');
                Route::any('/edit/{id}', 'getUpdateUtilisateur')->name('getUpdateUtilisateur');
            });
        });
    });
    Route::controller(WebServiceController::class)->group(function(){
        Route::get('services/partenaire/{idPartenaire}', 'getServicePartenaire')->name('getServicePartenaire');
    });
    Route::controller(WebExportExcelController::class)->group(function(){
        Route::get('export/transaction', 'exportTransaction')->name('export.transactions');
        Route::get('export/approvisionnement', 'exportApprovisionnement')->name('export.approvisionnement');
        Route::get('export/recharge', 'exportRecharge')->name('export.recharge');
    });

    Route::controller(WebAgentController::class)->group(function(){
        Route::get('agent/list', 'listAgent')->name('listAgent');
        Route::get('agent/edit/{id}', 'getUpdateUser')->name('getUpdateUser');
        Route::get('agent/edit/{id}', 'getUpdateUser')->name('getUpdateUser');
        Route::middleware(['routedealer'])->group(function () {
            Route::any('agent/update/{id}', 'setUpdateAgent')->name('setUpdateAgent');
            Route::any('agent/create', 'setNewAgent')->name('setNewAgent');
            Route::any('agent/debloque/{id}', 'debloqueAgent')->name('debloqueAgent');
            Route::any('agent/delete/{id}', 'deleteAgent')->name('deleteAgent');
        });
        Route::any('agent/bloque/{id}', 'bloqueAgent')->name('bloqueAgent');
        Route::get('/agent/distributeur/{idDistributeur}', 'getMesAgents')->name('getMesAgents');

    });



}); //middleware for auth and checkStatus
