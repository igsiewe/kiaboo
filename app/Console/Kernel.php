<?php

namespace App\Console;

use App\Http\Controllers\api\ApiSmsController;
use App\Http\Enums\UserRolesEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        //copier le fichier laravel.log tous les jours à minuit
        //$schedule->exec('sudo cp ./../../storage/logs/laravel.log ./../../storage/logs/laravel_$(date +\%Y\%m\%d\%H%M%S).log')->everyMinute();
        //$schedule->exec('sudo rm -rf ./../../storage/logs/laravel.log')->everyMinute();
        //$schedule->exec('touch ./../../storage/logs/laravel.log')->everyMinute();

        $schedule->command('archive:log')->dailyAt('00:00');

        $schedule->call(function () {
            $sms = new ApiSmsController();
            //Parcourir la table des utilisateurs et recuperer le numéro de téléphone et le  solde (balanceAfter)
            $users = User::where("type_user_id", UserRolesEnum::AGENT->value)->where("status",1)->where("id",36)->get();
            foreach ($users as $user) {
                $date_last_connexion = $user->last_connexion;
                $now = Carbon::now();
                if($date_last_connexion == null){
                   $message = "M./Mme ".$user->lastname." ".$user->firstname."\nVotre compte KIABOO vous attend ! Connectez-vous dès maintenant et découvrez nos services.";
                }else{
                    $date_last_transaction = $user->date_last_transaction;
                    $diffInDays = $date_last_transaction->diffInDays($now);
                    if($diffInDays > 5){
                        $message = "M./Mme ".$user->lastname." ".$user->firstname."\nVotre dernière transaction date du ".Carbon::parse($date_last_transaction)->format("dd/mm/YYYY H:i:s")." - Solde :".$user->balance_after." F CFA";
                    }
                }

                $tel = ltrim($user->login,"+");
                $msg = "M. ".$user->name." ".$user->surname."\nLe solde de votre compte Kiaboo est de ".$user->balance_after;
                $envoyerSMS = $sms->SendSMS($tel,utf8_decode($msg));
            }
        })->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
