<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Modules\Orders\Domain\Contracts\OrderTemplateAdmin;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TemplateAdminService implements OrderTemplateAdmin
{
    public function __construct(
        private readonly OrderTemplateRepository $templates
    ) {}

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

        if ($this->templates->existsById($id)) {
            throw new RuntimeException(__('Template id already exists.'));
        }

        $attributes = Arr::except($payload, ['id']);

        return DB::transaction(fn (): Order => $this->templates->createWithId($id, $attributes));
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

            return $this->templates->update($template, $attributes);
        });
    }

    private function guardTemplateIdChange(Order $template, int $targetId): void
    {
        if ($this->templates->existsById($targetId)) {
            throw new RuntimeException(__('Template id already exists.'));
        }

        $hasDependencies = $this->templates->hasDependencies($template);

        if ($hasDependencies) {
            throw new RuntimeException(
                __('Template id cannot be changed because dependent records exist.')
            );
        }
    }
}
