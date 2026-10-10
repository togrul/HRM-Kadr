<?php

namespace App\Modules\Orders\Application\Services;

use App\Models\OrderLog;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Əmrin struktur görünürlüyü — siyahı, siyahıdakı əməliyyatlar, siyasət (policy) və
 * önizləmə eyni qaydanı işlədir:
 *
 *  - əmrin işçiləri (order_log_personnels və ya çoxşəxsli iştirakçılar) varsa, onların
 *    HAMISI istifadəçinin strukturlarında olmalıdır;
 *  - işçisi olmayan əmr yalnız Word mühərrikli işə qəbul əmridirsə və hədəf strukturu
 *    (template_snapshot.hire_structure_id) görünürlükdədirsə görünür;
 *  - «bütün strukturlar» bayrağı heç bir məhdudiyyət qoymur, boş görünürlük heç nə
 *    göstərmir (fail closed). Köhnə «qlobal görünən» əmrlər də eyni qaydaya tabedir.
 */
class OrderVisibilityService
{
    public function __construct(private readonly StructureService $structures) {}

    public function scopeFor(?User $user = null): StructureScope
    {
        return $this->structures->scopeFor($user);
    }

    /**
     * @param  Builder<OrderLog>  $query
     * @return Builder<OrderLog>
     */
    public function constrain(Builder $query, ?StructureScope $scope = null): Builder
    {
        $scope ??= $this->scopeFor();

        if ($scope->isAll()) {
            return $query;
        }

        if ($scope->isNone()) {
            return $query->whereRaw('1 = 0');
        }

        $ids = $scope->ids();

        return $query->where(function (Builder $visible) use ($ids): void {
            $visible
                ->where(function (Builder $withSubjects) use ($ids): void {
                    $withSubjects
                        ->where(fn (Builder $any) => $any
                            ->whereHas('personnels', self::withTrashedPersonnels(...))
                            ->orWhereHas('participants'))
                        ->whereDoesntHave('personnels', fn (Builder $p) => self::withTrashedPersonnels($p)->where(fn ($outside) => $outside
                            ->whereNull('personnels.structure_id')
                            ->orWhereNotIn('personnels.structure_id', $ids)))
                        ->whereDoesntHave('participants', fn ($participant) => $participant
                            ->whereDoesntHave('personnel', fn (Builder $p) => self::withTrashedPersonnels($p)->whereIn('personnels.structure_id', $ids)));
                })
                ->orWhere(function (Builder $hire) use ($ids): void {
                    $hire
                        ->whereDoesntHave('personnels', self::withTrashedPersonnels(...))
                        ->whereDoesntHave('participants')
                        ->where('template_render_mode', OrderIssueService::RENDER_MODE_DOCX)
                        ->whereIn('template_snapshot->hire_structure_id', $ids);
                });
        });
    }

    /**
     * İşçi münasibətlərində silinmiş işçilər də nəzərə alınır — SoftDeletes-in withTrashed()
     * makrosu ilə eynidir, sadəcə əlaqə sorğusunun ümumi Builder tipində də işləyir.
     */
    private static function withTrashedPersonnels(Builder $personnels): Builder
    {
        return $personnels->withoutGlobalScope(SoftDeletingScope::class);
    }

    /**
     * Görünürlükdə olan əmrlər (silinmişlər daxil) — siyahıdakı əməliyyatlar əmri buradan tapır.
     *
     * @return Builder<OrderLog>
     */
    public function visibleQuery(?User $user = null, bool $withTrashed = false): Builder
    {
        $query = $withTrashed ? OrderLog::withTrashed() : OrderLog::query();

        return $this->constrain($query, $this->scopeFor($user));
    }

    public function canSee(User $user, OrderLog $order): bool
    {
        $scope = $this->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        if ($scope->isNone() || ! $order->exists) {
            return false;
        }

        return $this->constrain(OrderLog::withTrashed()->whereKey($order->getKey()), $scope)->exists();
    }
}
