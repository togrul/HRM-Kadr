<?php

use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chief_delegations', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Personnel::class, 'chief_personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignIdFor(Personnel::class, 'delegate_personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->string('reason')->nullable();
            $table->foreignIdFor(OrderLog::class, 'basis_order_id')->nullable()->constrained('order_logs')->nullOnDelete();
            $table->string('basis_document')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignIdFor(User::class, 'revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['chief_personnel_id', 'starts_at', 'ends_at'], 'chief_del_chief_dates_idx');
            $table->index(['delegate_personnel_id', 'starts_at'], 'chief_del_delegate_start_idx');
            $table->index(['is_active', 'revoked_at'], 'chief_del_active_revoked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chief_delegations');
    }
};
