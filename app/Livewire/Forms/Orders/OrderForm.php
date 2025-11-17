<?php

namespace App\Livewire\Forms\Orders;

use App\Models\OrderLog;
use Livewire\Form;

class OrderForm extends Form
{
    public ?int $order_type_id = null;
    public ?int $order_id = null;
    public ?string $order_no = null;
    public ?string $given_date = null;
    public ?string $given_by = null;
    public ?string $given_by_rank = null;
    public ?int $status_id = null;
    public array $description = [];

    public function fillDefaults(?int $orderId, array $settings): void
    {
        $this->fill(array_merge($this->defaults(), [
            'order_id' => $orderId,
            'given_by' => data_get($settings, 'Chief'),
            'given_by_rank' => data_get($settings, 'Chief rank'),
        ]));
    }

    public function fillFromModel(OrderLog $order): void
    {
        $this->fill([
            'order_type_id' => $order->order_type_id,
            'order_id' => $order->order_id,
            'order_no' => $order->order_no,
            'given_date' => optional($order->given_date)->format('Y-m-d'),
            'given_by' => $order->given_by,
            'given_by_rank' => $order->given_by_rank,
            'status_id' => $order->status_id,
            'description' => $order->description ?? $this->descriptionDefaults(),
        ]);
    }

    public function payload(): array
    {
        return [
            'order_type_id' => $this->order_type_id,
            'order_id' => $this->order_id,
            'order_no' => $this->order_no,
            'given_date' => $this->given_date,
            'given_by' => $this->given_by,
            'given_by_rank' => $this->given_by_rank,
            'status_id' => $this->status_id,
            'description' => $this->description,
        ];
    }

    public function descriptionDefaults(): array
    {
        return [
            'start_date' => null,
            'end_date' => null,
            'location' => null,
            'description' => null,
        ];
    }

    protected function defaults(): array
    {
        return [
            'order_type_id' => null,
            'order_id' => null,
            'order_no' => null,
            'given_date' => now()->format('Y-m-d'),
            'given_by' => null,
            'given_by_rank' => null,
            'status_id' => null,
            'description' => $this->descriptionDefaults(),
        ];
    }
}
