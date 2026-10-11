<?php

namespace App\Modules\Reports\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hesabat filtr şablonu.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $tab
 * @property array<string,mixed> $filters
 * @property bool $is_shared
 * @property-read User|null $user
 */
class ReportPreset extends Model
{
    protected $table = 'report_presets';

    protected $fillable = [
        'user_id',
        'name',
        'tab',
        'filters',
        'is_shared',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'is_shared' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User,$this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * İstifadəçinin öz şablonları və paylaşılanlar.
     *
     * @param  Builder<ReportPreset>  $query
     * @return Builder<ReportPreset>
     */
    public function scopeAvailableTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $nested) use ($user): void {
            $nested->where('user_id', $user->getKey())->orWhere('is_shared', true);
        });
    }

    public function isOwnedBy(User $user): bool
    {
        return (int) $this->user_id === (int) $user->getKey();
    }
}
