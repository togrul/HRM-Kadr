<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Role;
use App\Models\RoleStructure;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StructureService
{
    private const SCOPE_TTL_MINUTES = 5;

    /**
     * İstifadəçinin struktur görünürlüyü (dəyər obyekti). «Bütün strukturlar» bayrağı olan
     * bir rol kifayətdir; əks halda rolların role_structures sətirlərinin birləşməsi.
     * Heç bir struktur verilməyibsə nəticə boşdur — istifadəçi heç nə görmür (fail closed).
     */
    public function scopeFor(?User $user = null): StructureScope
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return StructureScope::none();
        }

        if ($this->hasAllStructures($user)) {
            return StructureScope::all();
        }

        return StructureScope::of($this->getAccessibleStructures($user));
    }

    /**
     * Geriyə uyğun köməkçi: istifadəçinin görə biləcəyi struktur id-ləri. «Bütün
     * strukturlar» bayrağı olan istifadəçi üçün bütün strukturların id-ləri qaytarılır,
     * ona görə boş massiv HƏMİŞƏ «heç nə» deməkdir — onu «hamısı» kimi şərh etmə.
     *
     * @return list<int>
     */
    public function getAccessibleStructures(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        if ($this->hasAllStructures($user)) {
            return $this->allStructureIds();
        }

        return Cache::remember(
            self::idsCacheKey((int) $user->getKey()),
            now()->addMinutes(self::SCOPE_TTL_MINUTES),
            fn (): array => RoleStructure::query()
                ->whereIn('role_id', $user->roles()->select('roles.id'))
                ->distinct()
                ->pluck('structure_id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all()
        );
    }

    /** İstifadəçinin rollarından biri «bütün strukturlar» bayrağı daşıyırmı. */
    public function hasAllStructures(User $user): bool
    {
        return (bool) Cache::remember(
            self::flagCacheKey((int) $user->getKey()),
            now()->addMinutes(self::SCOPE_TTL_MINUTES),
            fn (): bool => self::supportsAllStructuresFlag()
                && $user->roles()->where('roles.all_structures', true)->exists()
        );
    }

    /** Qeydin strukturu istifadəçinin görünürlüyündədirmi (struktursuz qeyd — yalnız «hamısı»). */
    public function allows(User $user, int|string|null $structureId): bool
    {
        return $this->scopeFor($user)->allows($structureId);
    }

    public function allowsPersonnel(User $user, ?Personnel $personnel): bool
    {
        if ($personnel === null) {
            return false;
        }

        // Dar select-lə (məs. yalnız id, tabel_no) yüklənmiş model üçün strukturu ayrıca oxu.
        $structureId = array_key_exists('structure_id', $personnel->getAttributes())
            ? $personnel->getAttribute('structure_id')
            : Personnel::withTrashed()->whereKey($personnel->getKey())->value('structure_id');

        return $this->allows($user, $structureId);
    }

    public function allowsPersonnelId(?User $user, int|string|null $personnelId): bool
    {
        if (! $user instanceof User || (int) $personnelId <= 0) {
            return false;
        }

        $scope = $this->scopeFor($user);

        // «Hamısı» üçün sorğu yoxdur — qeydin mövcudluğunu çağıran özü yoxlayır (findOrFail).
        return $scope->isAll()
            || $scope->allows(Personnel::withTrashed()->whereKey((int) $personnelId)->value('structure_id'));
    }

    /** tabel_no ilə bağlı qeydlər (məzuniyyət, ezamiyyət, məzuniyyət sorğusu…) üçün. */
    public function allowsTabelNo(User $user, int|string|null $tabelNo): bool
    {
        $scope = $this->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        if ($tabelNo === null || $tabelNo === '' || $scope->isNone()) {
            return false;
        }

        return $scope->allows(
            Personnel::withTrashed()->where('tabel_no', (string) $tabelNo)->value('structure_id')
        );
    }

    /**
     * @return list<int>
     */
    public function allStructureIds(): array
    {
        return Cache::remember(
            'structure-ids-all',
            now()->addMinutes(self::SCOPE_TTL_MINUTES),
            fn (): array => Structure::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all()
        );
    }

    public function forgetUser(int $userId): void
    {
        Cache::forget(self::idsCacheKey($userId));
        Cache::forget(self::flagCacheKey($userId));
    }

    /** Rolun strukturları və ya «bütün strukturlar» bayrağı dəyişəndə onun bütün istifadəçiləri. */
    public function forgetRole(int $roleId): void
    {
        $role = Role::query()->with('users:id')->find($roleId);

        foreach ($role->users ?? [] as $user) {
            $this->forgetUser((int) $user->getKey());
        }
    }

    public function forgetAllStructureIds(): void
    {
        Cache::forget('structure-ids-all');
    }

    public static function supportsAllStructuresFlag(): bool
    {
        static $supported = null;

        return $supported ??= Schema::hasColumn('roles', 'all_structures');
    }

    private static function idsCacheKey(int $userId): string
    {
        return "structure-accessible-{$userId}";
    }

    private static function flagCacheKey(int $userId): string
    {
        return "structure-all-flag-{$userId}";
    }
}
