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
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('genre');
            $table->string('name');
            $table->string('surname');
            $table->string('phone')->unique();
            $table->string('phone_court')->nullable();
            $table->string('password');
            $table->string('email')->unique();
            $table->foreignId('quartier_id')->constrained('quartiers');
            $table->string('type_piece');
            $table->string('numero_piece')->unique();
            $table->date('date_validite')->nullable();
            $table->foreignId('ville_piece_id')->constrained('villes');
            $table->string('adresse');
            $table->boolean('optin')->default(0);
            $table->string('code_parrainage')->nullable();
            $table->string('photo_verso')->nullable();
            $table->string('photo_recto')->nullable();
            $table->boolean('status')->default(0);
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->dateTime('validated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
