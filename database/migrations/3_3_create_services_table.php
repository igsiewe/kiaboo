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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string("name_service",100);
            $table->string("short_name",100);
            $table->string("logo_service",255);
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->foreignId("partenaire_id")->references("id")->on("partenaires");
            $table->foreignId("type_service_id")->references("id")->on("type_services");
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->integer("display")->default(1)->comment("0 : Hidden, 1:Visible");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
