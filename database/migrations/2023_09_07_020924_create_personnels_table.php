<?php

use App\Models\Structure;
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
        Schema::create('personnels', function (Blueprint $table) {
            $table->id();
            $table->index(['tabel_no', 'id', 'position_id']);
            $table->string('tabel_no')->unique();
            $table->string('surname');
            $table->string('name');
            $table->string('patronymic');
            $table->boolean('has_changed_initials')->default(false);
            $table->string('previous_surname')->nullable();
            $table->string('previous_name')->nullable();
            $table->string('previous_patronymic')->nullable();
            $table->date('initials_changed_date')->nullable();
            $table->string('initials_change_reason')->nullable();
            $table->date('birthdate');
            $table->smallInteger('gender')->default(1);
            $table->string('phone');
            $table->string('mobile');
            $table->string('email');
            $table->integer('nationality_id');
            $table->foreign('nationality_id')->references('id')->on('countries');
            $table->boolean('has_changed_nationality')->default(false);
            $table->integer('previous_nationality_id');
            $table->foreign('previous_nationality_id')->nullable()->references('id')->on('countries');
            $table->date('nationality_changed_date')->nullable();
            $table->string('nationality_change_reason')->nullable();
            $table->string('pin');
            $table->string('residental_address');
            $table->integer('education_degree_id');
            $table->foreign('education_degree_id')->references('id')->on('education_degrees');
            $table->foreignIdFor(Structure::class)->constrained();
            $table->integer('position_id');
            $table->foreign('position_id')->references('id')->on('positions');
            $table->date('join_work_date');
            $table->date('leave_work_date')->nullable();
            $table->integer('disability_id');
            $table->foreign('disability_id')->nullable()->references('id')->on('disabilities');
            $table->date('disability_given_date')->nullable();
            $table->text('extra_important_information')->nullable();
            $table->text('computer_knowledge')->nullable();
            $table->foreignId('added_by')->references('id')->on('users');
            $table->foreignId('deleted_by')->nullable()->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personnels');
    }
};
