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
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->string("name_partenaire",100);
            $table->string("short_name_partenaire",100);
            $table->string("logo_partenaire",100);
            $table->string("name_contact",100);
            $table->string("surname_contact",200);
            $table->string("phone",100);
            $table->string("email",250);
            $table->integer("status")->default(1)->comment("0 : Deactivated, 1:Activated");
            $table->foreignId("countrie_id")->references("id")->on("countries");
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
        Schema::dropIfExists('partenaires');
    }
};
