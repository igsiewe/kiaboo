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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->text('lien_politique')->nullable();
            $table->text('lien_cgu')->nullable();
            $table->text('lien_mention')->nullable();
            $table->text('lien_appstore')->nullable();
            $table->text('lien_playstore')->nullable();
            $table->text('telephone_support')->nullable();
            $table->String('email_support')->nullable();
            $table->text('message_parrainage')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
