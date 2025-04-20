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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string("reference",80)->index();
            $table->string("reference_partenaire",250)->nullable();
            $table->dateTime("date_transaction");
            $table->date("date_operation")->nullable();
            $table->time("heure_operation")->nullable();
            $table->dateTime("date_end_trans")->nullable();
            $table->bigInteger("service_id");
            $table->double("balance_before")->default(0);
            $table->double("charge")->default(0)->comment("Ce charge de service facturée par KIABOO");
            $table->double("debit")->default(0);
            $table->double("credit")->default(0);
            $table->double("balance_after")->default(0);
            $table->double("fees")->default(0);
            $table->string("callback_response",250)->nullable();
            $table->string("terminaison",250)->nullable();
            $table->double("commission")->default(0)->comment("Commission reversée par le partenaire de service");
            $table->double("commission_filiale")->default(0)->comment("Par commission filiale");
            $table->double("commission_distributeur")->default(0)->comment("Part commission distributeur");
            $table->double("commission_agent")->default(0)->comment("Par commission agent");
            $table->double("commission_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->double("commission_distributeur_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->double("commission_agent_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->date("commission_agent_rembourse_date")->nullable();
            $table->date("commission_distributeur_rembourse_date")->nullable();
            $table->string("ref_remb_com_agent",80)->nullable();
            $table->string("ref_remb_com_distributeur",80)->nullable();

            $table->double("charge")->default(0)->comment("Charge de service facturée par KIABOO");
            $table->double("charge_kiaboo")->default(0)->comment("Par charge kiaboo");
            $table->double("charge_distributeur")->default(0)->comment("Part charge attribuée au distributeur");
            $table->double("charge_agent")->default(0)->comment("Par charge attribuée à l'agent");
            $table->double("charge_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->double("charge_distributeur_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->double("charge_agent_rembourse")->default(0)->comment("0 = Encaissée, 1 = non encaissée");
            $table->date("charge_agent_rembourse_date")->nullable();
            $table->date("charge_distributeur_rembourse_date")->nullable();
            $table->string("ref_remb_charge_agent",80)->nullable();
            $table->string("ref_remb_charge_distributeur",80)->nullable();





            $table->integer("status")->default(1)->comment("0 : INITIATED, 1:VALIDATED OR SUCCESSFUL");
            $table->string("description",255)->nullable();
            $table->string("paytoken",255)->nullable();
            $table->string("device_notification",255)->nullable();
            $table->foreignId("created_by")->references("id")->on("users");
            $table->foreignId("updated_by")->references("id")->on("users");
            $table->foreignId("countrie_id")->references("id")->on("countries");
            $table->foreignId("distributeur_id")->references("id")->on("distributeurs");
            $table->foreignId("agent_id")->references("id")->on("users");
            $table->double("balance_after_partenaire")->default(0)->comment("Ce champ est renseigné lors  de l'appro du compte de l'agent par le distributeur");
            $table->double("balance_before_partenaire")->default(0)->comment("Ce champ est renseigné lors  de l'appro du compte de l'agent par le distributeur");
            $table->string("customer_phone",80)->nullable();
            $table->integer("source",)->nullable();
            $table->string("fichier",255)->nullable();
            $table->text("message")->nullable();
            $table->string('moyen_payment')->nullable();
            $table->string('reference_trans_carte')->nullable();
            $table->integer("status_cancel")->default(1)->comment("0 : ACTIVATED, 1:CANCELED")->default(0);
            $table->dateTime("date_cancel")->nullable();
            $table->foreignId("cancel_by")->references("id")->on("users");
            $table->foreignId("transaction_cancel_id")->references("id")->on("transactions");
            $table->string("description_cancel",255)->nullable();
            $table->string("latitude",255)->nullable();
            $table->string("longitude",255)->nullable();
            $table->text("place")->nullable();
            $table->text("marchand_transaction_id")->nullable();
            $table->double('fees_collecte')->default(0);
            $table->double('fees_kiaboo')->default(0);
            $table->double('fees_partenaire_service')->default(0);
            $table->double('marchand_amount')->default(0);
            $table->string("version")->nullable();
            $table->text("api_response")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
