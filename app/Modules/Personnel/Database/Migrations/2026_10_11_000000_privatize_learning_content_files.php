<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (Artisan::call('files:privatize') !== 0) {
            throw new RuntimeException('Learning content could not be moved to private storage.');
        }
    }

    public function down(): void {}
};
