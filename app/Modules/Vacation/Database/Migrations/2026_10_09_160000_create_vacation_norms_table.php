<?php

use App\Modules\Vacation\Application\Services\VacationNormDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Əmək məzuniyyəti normaları (ƏM m.114–117, 119): dörd qrup — əsas, staj, uşaqlı valideyn,
 * əmək şəraiti. Qanunla təsdiqlənmiş standart dəyərlər (docs/vacation-legal-basis.md) ilə
 * doldurulur; qurum vəzifə / işçi üzrə əlavə sətirlər yazır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacation_norms', function (Blueprint $table): void {
            $table->id();
            $table->string('group', 20);
            $table->string('scope', 20)->default('all');
            $table->integer('position_id')->nullable();
            $table->string('tabel_no')->nullable();
            $table->string('condition', 40)->nullable();
            $table->unsignedSmallInteger('min_value')->nullable();
            $table->unsignedSmallInteger('max_value')->nullable();
            $table->boolean('women_only')->default(false);
            $table->boolean('exclusive')->default(false);
            $table->unsignedSmallInteger('days');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->string('legal_basis', 60)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['group', 'is_active']);
            $table->index('position_id');
            $table->index('tabel_no');
        });

        $now = now();
        DB::table('vacation_norms')->insert(array_map(
            fn (array $row): array => $row + ['created_at' => $now, 'updated_at' => $now],
            VacationNormDefaults::rows(),
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('vacation_norms');
    }
};
