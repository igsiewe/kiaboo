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
        Schema::create('recrutements', function (Blueprint $table) {
            $table->id();
            $table->string("name",60);
            $table->string("surname",60);
            $table->string("email",60);
            $table->string("telephone",255);
            $table->string("quartier",255);
            $table->string("adresse",255);
            $table->string("numcni",255);
            $table->string("recto",255);
            $table->string("verso",255);
            $table->string("datecni",255);
            $table->string("photo",255);
            $table->dateTime("date_creation");
            $table->foreignId("ville_id")->references("id")->on("villes");
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
        Schema::dropIfExists('recrutements');
    }
};
