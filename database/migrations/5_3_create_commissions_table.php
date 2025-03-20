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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->foreignId('distributeur_id')->references('id')->on('distributeurs');
            $table->double('borne_min')->default(0);
            $table->double('borne_max')->default(0);
            $table->double('amount')->default(0);
            $table->double('taux')->default(0);
            $table->double('part_agent')->default(0);
            $table->double('part_distributeur')->default(0);
            $table->double('part_kiaboo')->default(0);
            $table->string('type_commission');
            $table->integer('status')->default(1)->comment('0 : non actif, 1:actif')->default(1);
            $table->foreignId('created_by')->references('id')->on('users');
            $table->foreignId('updated_by')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
