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
        Schema::create('sous_distributeurs', function (Blueprint $table) {
            $table->id();
            $table->string("name_sous_distributeur",100);
            $table->string("name_contact",100);
            $table->string("surname_contact",200);
            $table->string("phone",100);
            $table->string("email",250);
            $table->bigInteger("zone_id");
          //  $table->foreignId("zone_id")->references("id")->on("zone");
            $table->foreignId("distributeur_id")->references("id")->on("distributeurs");
            $table->double("balance_before")->default(0);
            $table->double("balance_after")->default(0);
            $table->double("last_amount")->default(0);
            $table->integer("last_transaction_id")->default(0);
            $table->date("date_last_transaction")->default(now());
            $table->foreignId("user_last_transaction_id")->references("id")->on("users");
            $table->string("'reference_last_transaction',",100)->nullable();
            $table->integer("last_service_id")->nullable();
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sous_distributeurs');
    }
};
