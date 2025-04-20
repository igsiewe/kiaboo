<?php

namespace App\Providers;

use App\Http\Enums\UserRolesEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       // $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        LogViewer::auth(function ($request){
            return $request->user() && in_array($request->user()->type_user_id, [
                UserRolesEnum::SUPADMIN->value, UserRolesEnum::ADMIN->value
                ]);
        });
    }
}
