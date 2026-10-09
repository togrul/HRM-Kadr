<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\Personnel;
use Illuminate\Validation\ValidationException;

/**
 * Mövcud əməkdaşın təyinat sahələrinin (struktur bölmə, vəzifə) qoruyucusu üçün modullar
 * arası rəsmi səth. Bu sahələr yalnız əmr effektləri (köçürmə, işə qəbul) və açıq icazəli
 * sistem prosesləri ilə dəyişir; digər yazma yolları — Livewire formu, API, idxal — rədd
 * olunur.
 *
 * Qanuni yazıcı dəyişikliyi `allow()` daxilində edir:
 *
 *     app(GuardsPersonnelAssignment::class)->allow(fn () => $personnel->forceFill([...])->save());
 *
 * @see \App\Modules\Personnel\Application\Services\PersonnelAssignmentGuard
 */
interface GuardsPersonnelAssignment
{
    /**
     * Callback-i icazəli kontekstdə işlədir: içində qorunan sahələrin dəyişməsinə icazə var.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function allow(callable $callback): mixed;

    public function isAllowed(): bool;

    /**
     * Hal-hazırda yalnız əmrlə dəyişən atributlar (bütün sahə qrupları üzrə).
     *
     * @return list<string>
     */
    public function guardedAttributes(): array;

    public function isGuarded(string $attribute): bool;

    /**
     * Saxlanmağa hazırlaşan modeldə icazəsiz dəyişmiş qorunan atributlar.
     *
     * @return list<string>
     */
    public function lockedChanges(Personnel $personnel): array;

    /**
     * İcazəsiz dəyişiklik varsa saxlamağı dayandırır.
     *
     * @throws ValidationException
     */
    public function enforce(Personnel $personnel): void;
}
