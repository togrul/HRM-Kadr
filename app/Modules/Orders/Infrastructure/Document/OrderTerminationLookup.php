<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Enums\OrderStatusEnum;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;

/**
 * Whether an employee has a termination order (xitam) on file that is pending or approved —
 * what the unused-leave compensation order needs before the termination itself is approved.
 */
class OrderTerminationLookup
{
    public function __construct(private readonly OrderWordTemplateRepository $templates) {}

    public function hasTerminationOrder(Personnel $personnel): bool
    {
        if (blank($personnel->tabel_no)) {
            return false;
        }

        return OrderLog::query()
            ->whereIn('status_id', [OrderStatusEnum::PENDING->value, OrderStatusEnum::APPROVED->value])
            ->whereNotNull('template_snapshot')
            ->whereHas('personnels', fn ($query) => $query->where('personnels.tabel_no', $personnel->tabel_no))
            ->get(['id', 'template_snapshot'])
            ->contains(function (OrderLog $order): bool {
                $code = (string) data_get($order->template_snapshot, 'template_code', '');

                return $code !== '' && $this->templates->find($code)?->effect === 'termination';
            });
    }
}
