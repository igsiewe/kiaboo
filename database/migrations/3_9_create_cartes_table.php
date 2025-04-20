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
        Schema::create('cartes', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->references("id")->on("users");
            $table->String("number")->nullable();
            $table->String("name")->nullable();
            $table->String("cvv")->nullable();
            $table->String("expire")->nullable();
            $table->String("month")->nullable();
            $table->String("year")->nullable();
            $table->String("fourth_first_number")->nullable();
            $table->String("fourth_last_number")->nullable();
            $table->String("adresse")->nullable();
            $table->String("ville")->nullable();
            $table->String("boitepostale")->nullable();
            $table->String("facturation_name")->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartes');
    }
};
