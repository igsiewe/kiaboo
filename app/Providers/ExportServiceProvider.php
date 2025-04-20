<?php

namespace App\Providers;

use App\Exports\TransactionExport;
use App\Http\Controllers\web\WebTransactionsController;
use Illuminate\Support\ServiceProvider;

class ExportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(abstract: TransactionExport::class, concrete: WebTransactionsController::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
