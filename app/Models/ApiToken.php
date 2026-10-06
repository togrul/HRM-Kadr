<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $name
 * @property string $token_hash
 * @property array<int, string>|null $abilities
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_active
 *
 * A machine-to-machine token for the integration API.
 *
 * The plaintext exists for exactly one moment — the return value of
 * {@see self::generate()}. Everything afterwards works off the SHA-256 hash, so
 * a database copy does not yield working credentials and nobody, including an
 * administrator, can read an issued token back.
 */
class ApiToken extends Model
{
    /** Distinguishes our tokens from anything else pasted into a bearer header. */
    private const PREFIX = 'hrm_';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mint a token and return the plaintext ONCE.
     *
     * @param  list<string>|null  $abilities  null = every ability
     * @return array{token: self, plain: string}
     */
    public static function generate(string $name, ?array $abilities = null, ?Carbon $expiresAt = null, ?int $createdBy = null): array
    {
        $plain = self::PREFIX.Str::random(48);

        $token = static::query()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
            'is_active' => true,
            'created_by' => $createdBy,
        ]);

        return ['token' => $token, 'plain' => $plain];
    }

    /**
     * Resolve a presented bearer string, or null.
     *
     * Lookup is by hash, so an invalid token costs one indexed read and reveals
     * nothing about which part was wrong.
     */
    public static function resolve(?string $plain): ?self
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        $token = static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where('is_active', true)
            ->first();

        if ($token === null) {
            return null;
        }

        if ($token->expires_at !== null && $token->expires_at->isPast()) {
            return null;
        }

        return $token;
    }

    /** A null ability list means "everything"; an explicit list is checked exactly. */
    public function allows(string $ability): bool
    {
        $abilities = $this->abilities;

        if (! is_array($abilities)) {
            return true;
        }

        return in_array($ability, $abilities, true);
    }

    /**
     * Record use — at most once a minute.
     *
     * A cursor-based feed is polled on a schedule, so writing this on every
     * request would put every caller in contention over one row. The field
     * answers "is this token still in use?", where a minute of resolution is
     * plenty.
     */
    public function touchUsage(): void
    {
        if ($this->last_used_at !== null && $this->last_used_at->greaterThan(now()->subMinute())) {
            return;
        }

        $this->forceFill(['last_used_at' => now()])->saveQuietly();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
