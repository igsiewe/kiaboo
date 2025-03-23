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
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->text('description')->nullable();
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
        Schema::dropIfExists('versions');
    }
};
