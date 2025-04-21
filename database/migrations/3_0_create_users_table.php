<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name',200);
            $table->string('surname',200);
            $table->string('telephone',200);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->dateTime('last_connexion')->nullable();
            $table->string('password');
            $table->string('codeparrainage',200)->nullable();
            $table->string('moncodeparrainage',200)->nullable();
            $table->string('numcni',200)->nullable();
            $table->date("datecni");
            $table->string('login');
            $table->double("balance_before")->default(0);
            $table->double("balance_after")->default(0);
            $table->double("last_amount")->default(0);
            $table->double("total_commission")->default(0);
            $table->integer("last_transaction_id")->default(0);
            $table->double("sum_payment")->default(0);
            $table->double("sum_refund")->default(0);
            $table->integer("seuilapprovisionnement")->default(0);
            $table->date("date_last_transaction")->default(now());
            $table->string('countrie');
            $table->string('quartier')->nullable(); //Va etre enlevé. Quartier migre deja dans cette table
            $table->string('adresse')->nullable();
            $table->boolean('optin');
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->foreignId("created_by")->references("id")->on("users");
            $table->string("'reference_last_transaction',",100)->nullable();
            $table->integer("last_service_id")->nullable();
            $table->integer("status_delete")->nullable()->default(0)->comment("0 : no deleted, 1:deleted");;
            $table->dateTime("deleted_at")->nullable();
            $table->integer("ville_id"); //Va etre enleve, il y'a une relation entre ville et quartier
            $table->integer("quartier_id");
            $table->integer("version");
            $table->integer('statut_code_parrainage')->default(1);
            $table->integer("view")->default(1)->comment("0 : no view, 1:view : User for stripe operation");
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('users', function($table) {
            $table->foreignId("deleted_by")->references("id")->on("users");
            $table->foreignId("ville_id")->references("id")->on("villes");
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->foreignId("user_last_transaction_id")->references("id")->on("users");
            $table->foreignId("distributeur_id")->references("id")->on("distributeurs");
            $table->foreignId("type_user_id")->references("id")->on("type_users");
            $table->foreignId("countrie_id")->references("id")->on("countries");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
