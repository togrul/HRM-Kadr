<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Həssas yükləmələr (məzuniyyət/xəstəlik sənədləri, təlim sertifikatları, portfel əlavələri,
 * onboarding sənədləri, işçi fotoları) public diskdən özəl local diskə köçürülür.
 * İş `files:privatize` əmrindədir (idempotent) — deploy-dan sonra əl ilə də işlədilə bilər.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('files:privatize');
    }

    public function down(): void
    {
        // Faylları yenidən veb ilə açıq diskə qaytarmaq təhlükəsizlik reqressiyası olardı.
    }
};
