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
            $yesterday = Carbon::today()->format('Y-m-d His');
            $archivedPath = storage_path("logs/laravel-{$yesterday}.log");

            // Renommer le fichier
            File::move($logPath, $archivedPath);
            $this->info("Fichier renommé en : laravel-{$yesterday}.log");

            // Créer un nouveau fichier vide
            File::put($logPath, '');

            // Changer le propriétaire en "ubuntu"
            // ATTENTION : nécessite que le script soit exécuté avec des droits sudo/crontab
            exec("chown ubuntu:ubuntu {$logPath}");
            exec("chmod 664 {$logPath}"); // lecture/écriture pour user et groupe

            exec("chown www-data:www-data {$logPath}");
            exec("chmod 664 {$logPath}"); // lecture/écriture pour user et groupe

            exec("chown -R www-data storage");
            exec("chown -R www-data storage/framework");
            exec("chown g+w -R storage");
            exec("chown g+w -R storage/framework");
            exec("chown g+w -R storage/framework/sessions/");
            exec("chown g+w -R storage/logs/");

            exec("chmod 664 {$logPath}"); // lecture/écriture pour user et groupe

            $this->info("Nouveau fichier laravel.log créé avec droits pour ubuntu");
        } else {
            $this->warn('Aucun fichier laravel.log trouvé à archiver.');
        }
    }
}
