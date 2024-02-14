<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Mockery\Generator\StringManipulation\Pass\Pass;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
//        $this->registerPolicies();
//        Passport::routes();
        Passport::tokensExpireIn(now()->addHour(12));
        Passport::refreshTokensExpireIn(now()->addHour(12));
        Passport::personalAccessTokensExpireIn(now()->addHour(12));
    }
}
