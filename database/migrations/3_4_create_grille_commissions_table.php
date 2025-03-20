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
        Schema::create('grille_commissions', function (Blueprint $table) {
            $table->id();
            $table->string("model",60)->comment("1 : Taux; 2: Borne");
            $table->double("borne_inferieure")->nullable();
            $table->double("borne_superieure")->nullable();
            $table->double("tauxht")->nullable();
            $table->double("montant")->nullable();
            $table->foreignId("service_id")->references("id")->on("services");
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
        Schema::dropIfExists('grille_commissions');
    }
};
