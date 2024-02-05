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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('surname');
            $table->string('name');
            $table->string('patronymic');
            $table->foreignIdFor(\App\Models\Structure::class)->constrained();
            $table->integer('height');
            $table->string('military_service')->nullable();
            $table->unsignedInteger('status_id');
            $table->foreign('status_id')->references('id')->on('appeal_statuses');
            $table->string('phone')->nullable();
            $table->unsignedSmallInteger('knowledge_test')->default(0);
            $table->unsignedSmallInteger('physical_fitness_exam')->default(0);
            $table->date('research_date')->nullable();
            $table->enum('research_result',\App\Enums\ResearchResultEnum::values())->nullable();
            $table->text('discrediting_information')->nullable();
            $table->date('examination_date')->nullable();
            $table->date('appeal_date')->nullable();
            $table->date('application_date')->nullable();
            $table->date('requisition_date')->nullable();
            $table->string('initial_documents')->nullable();
            $table->string('documents_completeness')->nullable();
            $table->enum('attitude_to_military',\App\Enums\AttitudeMilitaryEnum::values())->default('h/m');
            $table->string('characteristics')->nullable();
            $table->date('hhk_date')->nullable();
            $table->enum('hhk_result',\App\Enums\MilitaryStatusEnum::values())->nullable();
            $table->text('useless_info')->nullable();
            $table->text('note')->nullable();
            $table->text('presented_by')->nullable();
            $table->foreignId('creator_id')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
