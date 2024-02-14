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
        Schema::create('parrainages', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->references("id")->on("users");
            $table->String("name")->nullable();
            $table->String("surname")->nullable();
            $table->String("phone")->nullable();
            $table->String("codeparrainage")->nullable();
            $table->integer("status")->default(1)->comment("0 : en attente, 1:inscrit");
            $table->dateTime("date_subscribe")->nullable();
            $table->Double("bonus")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parrainages');
    }
};
