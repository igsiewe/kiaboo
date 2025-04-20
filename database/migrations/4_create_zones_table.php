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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string("name_zone",60);
            $table->foreignId("region_id")->references("id")->on("regions");
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
        Schema::dropIfExists('zones');
    }
};
