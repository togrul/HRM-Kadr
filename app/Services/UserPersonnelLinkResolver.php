<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;

/**
 * İstifadəçi ↔ əməkdaş eyniləşdirməsinin YEGANƏ mənbəyi.
 *
 * İstifadəçi yalnız admin tərəfindən açıq yaradılmış `user_personnel_links` sətri ilə əməkdaşa
 * bağlanır. E-poçt və ya ad-soyad uyğunluğu heç vaxt eyniləşdirmə sayılmır: istifadəçi öz
 * e-poçtunu və adını dəyişə bilərdi, bu da başqasının kabinetinə, əmək haqqı vərəqəsinə və
 * təsdiq hüququna yol açırdı. Resolver heç nə yazmır — yalnız mövcud bağı oxuyur.
 */
class UserPersonnelLinkResolver
{
    /**
     * @var array<int, int|null>
     */
    private array $resolvedByUser = [];

    /**
     * İstifadəçinin açıq bağlı, aktiv əməkdaş kartının id-si; bağ yoxdursa və ya əməkdaş
     * işdən çıxıbsa/təsdiq gözləyirsə — null.
     */
    public function resolve(?User $user): ?int
    {
        if (! $user || ! $user->getKey()) {
            return null;
        }

        $userId = (int) $user->getKey();

        if (array_key_exists($userId, $this->resolvedByUser)) {
            return $this->resolvedByUser[$userId];
        }

        $linkedPersonnelId = UserPersonnelLink::query()
            ->where('user_id', $userId)
            ->value('personnel_id');

        if (! $linkedPersonnelId) {
            return $this->resolvedByUser[$userId] = null;
        }

        $activeLinkedId = Personnel::query()
            ->active()
            ->whereKey($linkedPersonnelId)
            ->value('id');

        return $this->resolvedByUser[$userId] = $activeLinkedId ? (int) $activeLinkedId : null;
    }

    /**
     * Əməkdaş kartlarına açıq bağlı istifadəçilər: [personnel_id => user_id].
     *
     * @param  array<int, int>  $personnelIds
     * @return array<int, int>
     */
    public function userIdsByPersonnel(array $personnelIds): array
    {
        $personnelIds = array_values(array_unique(array_filter(array_map('intval', $personnelIds))));

        if ($personnelIds === []) {
            return [];
        }

        return UserPersonnelLink::query()
            ->whereIn('personnel_id', $personnelIds)
            ->pluck('user_id', 'personnel_id')
            ->mapWithKeys(fn ($userId, $personnelId): array => [(int) $personnelId => (int) $userId])
            ->all();
    }

    /**
     * Bağ dəyişəndə (yaradıldı / silindi) eyni sorğu daxilindəki keşi təmizləyir.
     */
    public function forget(?int $userId = null): void
    {
        if ($userId === null) {
            $this->resolvedByUser = [];

            return;
        }

        unset($this->resolvedByUser[$userId]);
    }
}
