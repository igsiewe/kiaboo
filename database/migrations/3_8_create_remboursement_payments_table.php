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
        Schema::create('remboursement_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->foreignId("user_id")->references("id")->on("users");
            $table->dateTime("date_demande");
            $table->double('amount')->default(0);
            $table->text('description')->nullable();
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->dateTime("date_validation")->nullable();
            $table->text('motif_validation')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->dateTime("date_rejet")->nullable();
            $table->integer("status")->default(0)->comment("0 : En attente, 1:Validé, 2:Exécuté, 3:Rejeté, 4:Annulé");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remboursement_payments');
    }
};
