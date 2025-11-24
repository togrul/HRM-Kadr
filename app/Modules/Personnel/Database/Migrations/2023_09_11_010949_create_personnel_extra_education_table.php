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
        Schema::create('personnel_extra_education', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('education_type_id')->index();
            $table->foreign('education_type_id')->references('id')->on('education_types');
            $table->integer('educational_institution_id')->index();
            $table->foreign('educational_institution_id')->references('id')->on('educational_institutions');
            $table->integer('education_form_id')->index();
            $table->foreign('education_form_id')->references('id')->on('education_forms');
            $table->string('name');
            $table->string('shortname')->nullable();
            $table->string('education_language');
            $table->string('education_program_name');
            $table->integer('admission_year');
            $table->integer('graduated_year');
            $table->integer('education_document_type_id')->index();
            $table->foreign('education_document_type_id')->references('id')->on('education_document_types');
            $table->string('diplom_serie');
            $table->integer('diplom_no');
            $table->date('diplom_given_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_extra_education');
    }
};
