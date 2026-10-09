<?php

namespace App\Modules\Personnel\Contracts;

use App\Models\Personnel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Dəyişiklik siyasətinin qoruyucusu — modullar arası rəsmi səth. Hər işçi sahə qrupu
 * (struktur/vəzifə, əmək haqqı, soyad, tarixlər, əlaqə, ailə, sənədlər, şəkil/qeydlər)
 * qurumun seçdiyi rejimlə qorunur:
 *
 *  - `free`    — məhdudiyyət yoxdur;
 *  - `journal` — yazma `withReason()` daxilində olmalıdır; köhnə → yeni dəyərlər səbəblə
 *                birlikdə activity log-a yazılır;
 *  - `order`   — yalnız `allow()` / `allowForEffect()` daxilində (əmr effektləri, işə qəbul).
 *
 * `personnels` sütunları model səviyyəsində (PersonnelObserver) yoxlanılır, ona görə istənilən
 * Eloquent yazma yolu — Livewire, API, idxal — siyasətə tabedir. Sütunu olmayan qruplar
 * (əmək haqqı Compensation-da) sahib modul tərəfindən `authorizeExternalChange()` ilə yoxlanılır.
 *
 * @see \App\Modules\Personnel\Application\Services\PersonnelChangeGuard
 */
interface GuardsPersonnelChanges extends GuardsPersonnelAssignment
{
    public function modeFor(string $group): PersonnelChangeMode;

    /**
     * Callback-i icazəli kontekstdə işlədir. `$groups` null-dırsa bütün qruplar açıqdır
     * (köhnə davranış), əks halda yalnız sadalananlar.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @param  list<string>|null  $groups
     * @return TResult
     */
    public function allow(callable $callback, ?array $groups = null): mixed;

    /**
     * Əmr effektinin reyestrdə yaza bildiyi qruplar üçün icazəli kontekst.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function allowForEffect(string $effect, callable $callback): mixed;

    /** `$group` null-dırsa hər hansı icazəli kontekstin açıq olub-olmadığı. */
    public function isAllowed(?string $group = null): bool;

    /**
     * Jurnal rejimli dəyişikliklər üçün səbəb konteksti: içində edilən dəyişikliklər bu
     * səbəblə activity log-a yazılır.
     *
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    public function withReason(string $reason, callable $callback): mixed;

    public function currentReason(): ?string;

    /**
     * Hal-hazırda məhdud (`journal` və ya `order`) olan bütün `personnels` sütunları.
     *
     * @return list<string>
     */
    public function restrictedAttributes(): array;

    public function groupOf(string $attribute): ?string;

    /**
     * Sahib modulun (məs. Compensation — əmək haqqı) öz yazma yolunda çağırdığı yoxlama:
     * `order` rejimində icazəli kontekstdən kənar dəyişiklik, `journal` rejimində səbəbsiz
     * dəyişiklik rədd olunur; səbəbli jurnal dəyişikliyi activity log-a yazılır.
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    public function authorizeExternalChange(string $group, ?string $reason, array $changes = [], array $context = [], string $errorKey = 'reason', ?Model $subject = null): void;

    /**
     * Jurnal girişini birbaşa yazır (sütunsuz dəyişikliklər — məs. sənəd və ailə siyahıları).
     *
     * @param  array<string, array{old: mixed, new: mixed}>  $changes
     * @param  array<string, mixed>  $context
     */
    public function recordJournal(string $group, string $reason, array $changes, ?Model $subject = null, array $context = []): void;

    /** Observer-in `updated` mərhələsində səbəbli jurnal dəyişikliklərini yazır. */
    public function journal(Personnel $personnel): void;
}
