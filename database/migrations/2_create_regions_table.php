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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string("name_region",60);
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->timestamps();
        });

        Schema::table('regions', function($table) {
            $table->foreignId("countrie_id")->references("id")->on("countries");
          //  $table->foreignId("created_by")->references("id")->on("users");
          //  $table->foreignId("updated_by")->references("id")->on("users");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
