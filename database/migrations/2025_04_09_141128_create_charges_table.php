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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->double("borne_min");
            $table->double("borne_max");
            $table->double("amount",60)->comment("Charge supplementaire que KIABOO facture aux clients");
            $table->foreignId("type_service_id")->references("id")->on("type_services");
            $table->string("type_charge")->comment("Type de charge : 1:taux, 2:borne");
            $table->double('part_agent')->default(0);
            $table->double('part_distributeur')->default(0);
            $table->double('part_kiaboo')->default(0);
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
