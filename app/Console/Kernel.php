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
        // Planifie l'archivage du log tous les jours à minuit
        $schedule->command('archive:log')->dailyAt('22:21');

        $schedule->call(function () {
            $sms = new ApiSmsController();

            // Récupère les utilisateurs de type AGENT actifs (exemple ici avec un seul user : id = 36)
            $users = User::where("type_user_id", UserRolesEnum::AGENT->value)
                ->where("status", 1)
                ->where("id", 36)
                ->get();

            foreach ($users as $user) {
                $now = Carbon::now();
                $message = null;

                if ($user->last_connexion === null) {
                    $message = "M./Mme {$user->name} {$user->surname}\nVotre compte KIABOO vous attend ! Connectez-vous dès maintenant et découvrez nos services.";
                } else {
                    $date_last_transaction = $user->date_last_transaction;

                    if ($date_last_transaction) {
                        $diffInDays = Carbon::parse($date_last_transaction)->diffInDays($now);

                        if ($diffInDays >= 3) {
                            $formattedDate = Carbon::parse($date_last_transaction)->format("d/m/Y H:i:s");
                            $message = "M./Mme {$user->name} {$user->surname}\nVotre dernière transaction date du {$formattedDate} - Solde : {$user->balance_after} F CFA";
                        }
                    }
                }

                // Si un message est défini, on l’envoie
                if ($message) {
                    $tel = ltrim($user->login, "+"); // Supprime le "+" du numéro
                    $sms->SendSMS($tel, utf8_decode($message));
                }
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
