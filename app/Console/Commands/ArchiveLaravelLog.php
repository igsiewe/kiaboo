<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
class ArchiveLaravelLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //protected $signature = 'app:archive-laravel-log';
    protected $signature = 'archive:log';

    /**
     * The console command description.
     *
     * @var string
     */
   // protected $description = 'Command description';
    protected $description = 'Archive laravel.log file daily and reset it';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            // Date de la veille
            $yesterday = Carbon::today()->format('Y-m-d');
            $archivedPath = storage_path("logs/archive-{$yesterday}.log");

            // Renommer le fichier
            File::move($logPath, $archivedPath);
            $this->info("Fichier renommé en : laravel-{$yesterday}.log");

            // Créer un nouveau fichier vide
            File::put($logPath, '');

            // Changer le propriétaire en "ubuntu"
            // ATTENTION : nécessite que le script soit exécuté avec des droits sudo/crontab
            exec("chown www-data:www-data {$logPath}");
            exec("chmod 664 {$logPath}"); // lecture/écriture pour user et groupe

            exec("chown -R www-data:www-data " . storage_path());
            exec("chown g+w -R www-data:www-data " . storage_path());
            exec("chown g+r -R " . storage_path("logs/"));
            exec("chown g+r -R " . storage_path("framework/"));
            exec("chown g+r -R " . storage_path("framework/sessions"));

            exec("chown -R ubuntu:ubuntu " . storage_path());
            exec("chown g+w -R ubuntu:ubuntu " . storage_path());
            exec("chown g+r -R " . storage_path("logs/"));
            exec("chown g+r -R " . storage_path("framework/"));
            exec("chown g+r -R " . storage_path("framework/sessions"));


//            sudo chown -R ubuntu:ubuntu storage bootstrap/cache
//sudo chmod -R 775 storage bootstrap/cache
//sudo chmod 664 storage/logs/laravel.log



     //       exec("chown -R www-data:www-data /var/www/kiaboopay/storage >> /var/www/kiaboopay/storage/logs/cron.log 2>&1");
     //       exec("chmod -R 775 /var/www/kiaboopay/storage >> /var/www/kiaboopay/storage/logs/cron.log 2>&1");

            $this->info("Nouveau fichier laravel.log créé avec droits pour ubuntu");
        } else {
            $this->warn('Aucun fichier laravel.log trouvé à archiver.');
        }
    }
}
