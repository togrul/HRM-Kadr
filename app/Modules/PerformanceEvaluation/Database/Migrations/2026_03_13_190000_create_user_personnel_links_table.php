<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_personnel_links')) {
            return;
        }

        Schema::create('user_personnel_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->string('resolution_source', 40)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->unique('personnel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_personnel_links');
    }
};
