<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemplateAdminService
{
    /**
     * Create order template with explicit/manual primary key assignment.
     *
     * @param  array<string,mixed>  $payload
     */
    public function create(array $payload): Order
    {
        $id = (int) Arr::get($payload, 'id');
        if ($id <= 0) {
            throw new RuntimeException(__('Template id is required.'));
        }

        if (Order::query()->whereKey($id)->exists()) {
            throw new RuntimeException(__('Template id already exists.'));
        }

        $attributes = Arr::except($payload, ['id']);

        return DB::transaction(function () use ($id, $attributes): Order {
            $template = new Order;
            $template->fill($attributes);
            $template->id = $id;
            $template->save();

            return $template->fresh();
        });
    }

    /**
     * Update order template and allow explicit/manual id change only when safe.
     *
     * @param  array<string,mixed>  $payload
     */
    public function update(Order $template, array $payload): Order
    {
        $targetId = (int) Arr::get($payload, 'id', (int) $template->id);
        if ($targetId <= 0) {
            throw new RuntimeException(__('Template id is required.'));
        }

        $attributes = Arr::except($payload, ['id']);

        return DB::transaction(function () use ($template, $attributes, $targetId): Order {
            if ($targetId !== (int) $template->id) {
                $this->guardTemplateIdChange($template, $targetId);
                $template->id = $targetId;
            }

            $template->fill($attributes);
            $template->save();

            return $template->fresh();
        });
    }

    private function guardTemplateIdChange(Order $template, int $targetId): void
    {
        if (Order::query()->whereKey($targetId)->exists()) {
            throw new RuntimeException(__('Template id already exists.'));
        }

        $hasDependencies = $template->orderLogs()->exists()
            || $template->types()->exists();

        if ($hasDependencies) {
            throw new RuntimeException(
                __('Template id cannot be changed because dependent records exist.')
            );
        }
    }
}
