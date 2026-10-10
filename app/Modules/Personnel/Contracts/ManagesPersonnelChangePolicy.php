<?php

namespace App\Modules\Personnel\Contracts;

use InvalidArgumentException;

/**
 * Dəyişiklik siyasətinin idarə səthi (Admin → Tənzimləmələr → «Dəyişiklik siyasəti»).
 * Hər yazma activity log-a köhnə → yeni rejimlə düşür.
 *
 * @see \App\Modules\Personnel\Application\Services\PersonnelChangePolicyService
 */
interface ManagesPersonnelChangePolicy
{
    /**
     * Qrupların cədvəli (reyestr ardıcıllığı ilə).
     *
     * @return list<array{
     *     group: string,
     *     label: string,
     *     description: string,
     *     mode: string,
     *     default_mode: string,
     *     customized: bool,
     *     owner: string,
     *     updated_at: string|null,
     *     updated_by: string|null
     * }>
     */
    public function rows(): array;

    /**
     * Seçilə bilən rejimlər: dəyər → etiket.
     *
     * @return array<string, string>
     */
    public function modeOptions(): array;

    public function modeFor(string $group): PersonnelChangeMode;

    /**
     * @throws InvalidArgumentException naməlum qrup və ya rejim
     */
    public function setMode(string $group, string $mode, ?int $userId = null): void;

    /** Qrupu ilkin rejiminə qaytarır (siyasət sətri silinir). */
    public function resetToDefault(string $group, ?int $userId = null): void;
}
