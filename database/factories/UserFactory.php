<?php

namespace Database\Factories;

use App\Http\Enums\UserRolesEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $telephone = '69'.rand(1000000,9999999);
//        $date = Carbon::now()->subDays(rand(-200, 200));
//        $heure=Carbon::now()->subDays(rand(-200, 200))->format('H:i:s');
//        $service=rand(4,7);
//        $commission=rand(100,1100);
//        $iduser=rand(23,6608);
        return [
            'name'=>strtoupper(fake()->name()),
            'surname'=> fake()->firstName(),
            'telephone'=> $telephone,
            'email'=> fake()->unique()->safeEmail(),
            'login'=>'+237'.$telephone,
            'password'=> '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'codepin'=>rand(1000,9999),
            'type_user_id'=>UserRolesEnum::AGENT->value,
            'countrie_id'=>1,
            'balance_before'=>0,
            'balance_after'=>rand(100000,1000000),
            'total_commission'=>0,
            'last_amount'=>0,
            'last_transaction_id'=>null,
            'date_last_transaction'=>null,
            'distributeur_id'=>rand(1,3),
            'optin'=>1,
            'codeparrainage'=>Str::random(10),
            'moncodeparrainage'=>Str::random(10),
            'seuilapprovisionnement'=>2500000,
            'updated_by'=>1,
            'created_by'=>1,
            'expires_at'=>now(),
            "reference_last_transaction"=>null,
            "last_service_id"=>null,
            "countrie"=>1,
            "ville_id"=>rand(1,6),
            'quartier'=>fake()->city(),
            'adresse'=>fake()->address(),
            "last_connexion"=>now(),
            "numcni"=>rand(100000000,999999999),
            "datecni"=>now(),

        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
