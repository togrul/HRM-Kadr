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
        Schema::create('personnel_scientific_degree_and_names', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_no');
            $table->foreign('tabel_no')->references('tabel_no')->on('personnels')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('degree_and_name_id')->index();
            $table->foreign('degree_and_name_id')->references('id')->on('scientific_degree_and_names');
            $table->string('science');
            $table->date('given_date');
            $table->string('subject');
            $table->integer('edu_doc_type_id')->index();
            $table->foreign('edu_doc_type_id')->references('id')->on('education_document_types');
            $table->string('diplom_serie');
            $table->integer('diplom_no');
            $table->date('diplom_given_date');
            $table->string('document_issued_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnel_scientific_degree_and_names');
    }
};
