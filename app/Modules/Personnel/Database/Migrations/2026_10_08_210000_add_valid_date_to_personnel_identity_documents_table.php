<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The identity card (şəxsiyyət vəsiqəsi) expires like a passport does, so it gets the
 * same expiry column the passport and service card tables use (`valid_date`). Nullable:
 * cards entered before this column existed have no recorded expiry and stay "valid"
 * in document compliance until someone fills it in.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('personnel_identity_documents', 'valid_date')) {
            return;
        }

        Schema::table('personnel_identity_documents', function (Blueprint $table): void {
            $table->date('valid_date')->nullable()->after('document_issued_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('personnel_identity_documents', 'valid_date')) {
            return;
        }

        Schema::table('personnel_identity_documents', function (Blueprint $table): void {
            $table->dropColumn('valid_date');
        });
    }
};
