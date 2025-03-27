<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment("Table configuration Users");
            $table->unsignedBigInteger('societe_id')->nullable()->comment("Table configuration Société");
            $table->unsignedBigInteger('prefix_id')->comment("Table configuration Prefixes");
            $table->unsignedBigInteger('status_familiale_id')->comment("Table configuration Status Familiale");
            $table->unsignedBigInteger('type_document_id')->comment("Table configuration Type Document");
            $table->unsignedBigInteger('sexe_id')->comment("Table configuration Sexe");
            $table->string('nomcomplet_client')->comment('nomcomplet_cli est la concatenation du nom_cli, prenom_cli et secondprenom_cli. Si prefix="Epouse", nom_complet_client= nom_cli "Epouse" nom_conjoint_cli prenom_cli secondprenom_cli. Exemple EYENGA Epouse MBODO Veronique. Si prefixe="Enfant" ou "Bébé", nomcomplet_client= "Enfant" nom_cli prenom_cli seconprenom_cli. Exempe Enfant TEMO Paulin Noël');
            $table->string('prenom_cli')->nullable();
            $table->string('nom_cli')->comment("code généré à partir de l'année, mois, numéro sequence de création du site et code du site");
            $table->string('secondprenom_cli')->nullable();
            $table->date('date_naiss_cli')->nullable()->comment("La date ne doit pas etre superieur a la date du jour de saisie et plus grand que 1900");
            $table->boolean('enfant_cli')->default(0)->comment("Liste deroulante a deux valeurs 'Oui' et 'Non'. Non seulement si l'age est superieur a 14 ans");
            $table->string('ref_cli')->nullable()->comment("code généré à partir de l'année, mois, numéro sequence de création du site et code du site");
            $table->string('tel_cli')->comment("Limiter saisie a 9 caracteres comprenant les chiffres de 0 a 9");
            $table->string('tel2_cli')->nullable()->comment("Limiter saisie a 9 caracteres comprenant les chiffres de 0 a 9");
            $table->string('type_cli')->default('normal')->comment("Deux type: Normal, Associe - Notion de payeur a integrer au reglement des factures");
            $table->string('renseign_clini_cli')->nullable();
            $table->boolean('assure_pa_cli')->default(1);
            $table->boolean('afficher_ap')->default(0);
            $table->string('nom_assure_principale_cli')->nullable()->comment("Obligatoire si Assure_PA_cli='Non' et doit etre un nom d'un client deja enregistrer dans la base de donnees");
            $table->string('document_number_cli')->nullable();
            $table->string('nom_conjoint_cli')->nullable();
            $table->string('email_cli')->nullable()->comment("Conformer au format email");
            $table->boolean('date_naiss_cli_estime')->default(false)->comment("Deux valeurs: 'Oui' quand la date de naissance est connue, 'Non' lorsque la date de naissance estimee (age)");
            $table->integer('status_cli')->default(1)->comment("Trois valeurs: 'Actif', 'Inactif le client n'apparait dans les recherches et les saisies sauf aux modules recherche Inactif, 'Archive' n'apparait dans les recherches et saisies courantes sauf dans le module des archives");
            $table->boolean('client_anonyme_cli');
            $table->string('addresse_cli')->nullable();
            $table->boolean('tel_whatsapp')->default(true);
            $table->foreignId('created_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Foreign Key
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('societe_id')->references('id')->on('societes')->nullOnDelete();
            $table->foreign('prefix_id')->references('id')->on('prefixes');
            $table->foreign('status_familiale_id')->references('id')->on('status_familiales');
            $table->foreign('type_document_id')->references('id')->on('type_documents');
            $table->foreign('sexe_id')->references('id')->on('sexes');
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
