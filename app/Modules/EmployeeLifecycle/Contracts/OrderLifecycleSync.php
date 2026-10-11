<?php

namespace App\Modules\EmployeeLifecycle\Contracts;

/**
 * Təsdiqlənmiş/ləğv edilmiş köçürmə və xitam əmrlərinin həyat dövrü hadisələrinə çevrildiyi
 * rəsmi modullararası səth. Orders effektləri yalnız BU interfeysdən asılıdır — konkret
 * EmployeeLifecycle servisindən yox.
 *
 * Bütün metodlar idempotentdir: eyni əmr + işçi üçün təkrar çağırış ikinci hadisə yaratmır;
 * ləğvdən sonra yenidən təsdiq ləğv olunmuş hadisəni yenidən açır.
 *
 * @see \App\Modules\EmployeeLifecycle\Application\Services\OrderLifecycleEventService
 */
interface OrderLifecycleSync
{
    public const SOURCE_TRANSFER = 'order_transfer';

    public const SOURCE_TERMINATION = 'order_termination';

    /**
     * Köçürmə işçinin kartına tətbiq olundu (təsdiq anında və ya gələcək tarixli köçürmə
     * `orders:apply-due-transfers` ilə qüvvəyə minəndə): «rol dəyişikliyi» hadisəsi və checklist.
     *
     * @param  array{from_structure_id?: int|null, from_position_id?: int|null, to_structure_id?: int|null, to_position_id?: int|null}  $movement
     */
    public function transferApplied(int $orderId, ?string $orderNo, int $personnelId, ?string $effectiveDate, array $movement): void;

    /** Xitam əmri təsdiqləndi: offboarding hadisəsi, işdən ayrılma işi və checklist. */
    public function terminationApplied(int $orderId, ?string $orderNo, int $personnelId, string $lastWorkingDate, ?string $reason = null): void;

    /**
     * Əmr ləğv olundu və ya gözləməyə qaytarıldı: həmin əmr + işçi üzrə hadisə, açıq
     * tapşırıqlar və bağlı yerdəyişmə/ayrılma qeydi «ləğv edilib» olur.
     *
     * @param  'transfer'|'termination'  $kind
     */
    public function orderReversed(int $orderId, int $personnelId, string $kind): void;
}
