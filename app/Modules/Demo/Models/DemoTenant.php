<?php

namespace App\Modules\Demo\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Demo müştəri — demo serverin əsas ("idarə") bazasındakı `demo_tenants` cədvəli.
 * Həmişə `demo_control` bağlantısından oxunur, müştəri bazası aktiv olanda belə.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $email
 * @property string $database
 * @property string $audit_database
 * @property CarbonInterface $expires_at
 */
class DemoTenant extends Model
{
    public const CONNECTION = 'demo_control';

    protected $connection = self::CONNECTION;

    protected $table = 'demo_tenants';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function daysLeft(): int
    {
        return $this->isExpired() ? 0 : (int) ceil(now()->diffInHours($this->expires_at) / 24);
    }
}
