<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    /**
     * The activity log lives in its own database, which is shared and outlives any
     * single application database — the ledger that records this migration does not.
     * Point a fresh install at an audit database that already has the table and the
     * create fails, so every step here checks before it builds.
     */
    public function up()
    {
        if (Schema::connection(config('activitylog.database_connection'))->hasTable(config('activitylog.table_name'))) {
            return;
        }

        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
