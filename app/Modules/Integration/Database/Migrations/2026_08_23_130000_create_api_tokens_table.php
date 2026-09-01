<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Machine-to-machine tokens for the integration API.
 *
 * ## Why not reuse the attendance ingest secret
 *
 * `AttendancePunchIngestController` authenticates against a single static value
 * in config. That is acceptable for one appliance on a private network, but it
 * has no caller identity, no scope, no expiry and no way to revoke one consumer
 * without breaking the rest. Extending it to a feed that carries personal data
 * would make every future consumer share one secret.
 *
 * Each token here names its holder, carries an explicit ability list and can be
 * expired or switched off on its own.
 *
 * ## The plaintext is never stored
 *
 * Only the SHA-256 hash is kept. A leaked database therefore does not hand over
 * working credentials, and the token is shown to the operator exactly once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->char('token_hash', 64)->unique();
            // null = every ability. An explicit list is always preferable:
            // the employee feed should not be readable by a payroll token.
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
