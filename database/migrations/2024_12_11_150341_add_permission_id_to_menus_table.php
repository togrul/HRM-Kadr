<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('menus', function (Blueprint $table) {
            // Add the permission_id column
            $table->foreignIdFor(\Spatie\Permission\Models\Permission::class)
                ->nullable() // Temporary solution if you need to migrate existing data
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::table('menus', function (Blueprint $table) {
            // Drop the foreign key and column
            $table->dropForeign(['permission_id']);
            $table->dropColumn('permission_id');
        });
    }
};
