<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label_az');
            $table->string('label_en')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('warning_days')->default(60);
            $table->unsignedSmallInteger('critical_days')->default(30);
            $table->timestamps();
        });

        DB::table('compliance_document_requirements')->insert([
            [
                'key' => 'service_card',
                'label_az' => 'Xidməti vəsiqə',
                'label_en' => 'Service card',
                'is_required' => true,
                'warning_days' => 60,
                'critical_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'passport',
                'label_az' => 'Pasport',
                'label_en' => 'Passport',
                'is_required' => true,
                'warning_days' => 60,
                'critical_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contract',
                'label_az' => 'Əmək müqaviləsi',
                'label_en' => 'Employment contract',
                'is_required' => true,
                'warning_days' => 60,
                'critical_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_document_requirements');
    }
};
