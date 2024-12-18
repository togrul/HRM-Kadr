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
        Schema::create('role_structures', function (Blueprint $table) {
            $table->foreignIdFor(\Spatie\Permission\Models\Role::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Structure::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_structures');
    }
};
