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
        Schema::create('appro_distributeurs', function (Blueprint $table) {
            $table->id();
            $table->string("reference",60)->unique();
            $table->string("reference_validation",60)->nullable();
            $table->dateTime("date_operation")->comment("date à laquelle la demande a été initiée");
            $table->dateTime("date_validation")->comment("date à laquelle la demande a été validée")->nullable();
            $table->double("amount")->comment("Montant à approvisionner")->default(0);
            $table->double("balance_before")->default(0);
            $table->double("balance_after")->default(0);
            $table->foreignId("distributeur_id")->references("id")->on("distributeurs");
            $table->string("description",220)->nullable();
            $table->integer("status")->default(1)->comment("0 : Initiated, 1:Validated, 3:Rejected");
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->foreignId("validated_by")->references("id")->on("users");
            $table->foreignId("countrie_id")->references("id")->on("countries");
            $table->foreignId("rejected_by")->references("id")->on("users");
            $table->dateTime("date_reject")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appro_distributeurs');
    }
};
