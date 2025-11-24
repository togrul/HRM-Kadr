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
        Schema::create('personnel_identity_documents', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('nationality_id');
            $table->foreign('nationality_id')->references('id')->on('countries');
            $table->string('series');
            $table->string('number');
            $table->string('pin');
            $table->integer('born_country_id');
            $table->foreign('born_country_id')->references('id')->on('countries');
            $table->integer('born_city_id');
            $table->foreign('born_city_id')->references('id')->on('cities');
            $table->string('registered_address')->nullable();
            $table->boolean('is_married')->default(0);
            $table->string('military_duty')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('eye_color')->nullable();
            $table->integer('height');
            $table->string('document_issued_authority')->nullable();
            $table->date('document_issued_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_documents');
    }
};
