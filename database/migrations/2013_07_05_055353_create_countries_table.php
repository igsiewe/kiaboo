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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string("name_country",60);
            $table->string("short_name",60);
            $table->string("phone_code",60);
            $table->string("flag",255);

            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
          //  $table->foreignId("created_by")->references("id")->on("users");
          //  $table->foreignId("updated_by")->references("id")->on("users");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
