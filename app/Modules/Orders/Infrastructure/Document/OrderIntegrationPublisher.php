<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\OrderWordTemplate;
use App\Models\Personnel;
use App\Modules\Integration\Domain\Contracts\IntegrationOutbox;
use App\Modules\Orders\Application\Document\OrderParticipantFields;
use App\Modules\Orders\Application\Document\OrderWordTemplateRepository;
use App\Support\Language\AzerbaijaniDateFormatter;
use Illuminate\Database\Eloquent\Builder;

class OrderIntegrationPublisher
{
    public function __construct(
        private readonly OrderWordTemplateRepository $templates,
        private readonly IntegrationOutbox $outbox,
        private readonly AzerbaijaniDateFormatter $dates,
    ) {}

    public function publish(OrderLog $order, string $effectDirection, ?int $participantId = null, bool $deferredTransfer = false): void
    {
        if ($effectDirection === 'none') {
            return;
        }

        $snapshot = (array) $order->template_snapshot;
        $template = ($deferredTransfer || ($snapshot['applied_effect'] ?? null) === 'transfer')
            ? new OrderWordTemplate(['effect' => 'transfer', 'label' => is_scalar($snapshot['label'] ?? null) ? (string) $snapshot['label'] : '', 'variables' => []])
            : $this->templates->find((string) ($snapshot['template_code'] ?? ''));

        if (! $template) {
            return;
        }

        $orderFields = (array) ($snapshot['fields'] ?? []);
        $participants = $order->participants()->when($participantId !== null, fn (Builder $query): Builder => $query->whereKey($participantId))->get();

        if ($participants->isEmpty()) {
            $this->outbox->record('orders', (string) $order->order_no, $this->eventPayload(
                $order, $template, $effectDirection, Personnel::query()->find($snapshot['personnel_id'] ?? null), $this->effectFields($template, $orderFields), (array) ($snapshot['effect_state'] ?? []),
            ));

            return;
        }

        // One event per participant, same shape: the counterpart books each person's
        // trip/leave on its own. external_id is per participant; order_external_id ties
        // the events of one order together.
        $people = Personnel::query()->whereKey($participants->pluck('personnel_id')->all())->get()->keyBy('id')->all();
        foreach ($participants as $participant) {
            $fields = $this->effectFields($template, OrderParticipantFields::effective($template, $orderFields, (array) $participant->fields));

            $this->outbox->record('orders', (string) $order->order_no, [
                ...$this->eventPayload($order, $template, $effectDirection, $people[(int) $participant->personnel_id] ?? null, $fields, (array) $participant->effect_state),
                'external_id' => $order->id.'-'.$participant->position,
                'order_external_id' => (string) $order->id,
                'participant_index' => (int) $participant->position,
                'participant_count' => $participantId === null ? $participants->count() : $order->participants()->count(),
            ]);
        }
    }

    /**
     * The outbox payload for one employee of an order.
     *
     * @param  array<string,mixed>  $fields  role => value
     * @return array<string,mixed>
     */
    private function eventPayload(OrderLog $order, OrderWordTemplate $template, string $effectDirection, ?Personnel $personnel, array $fields, array $state): array
    {
        return [
            'external_id' => (string) $order->id,
            'order_no' => (string) $order->order_no,
            'effect' => (string) $template->effect,
            'label' => (string) $template->label,
            'date' => optional($order->given_date)->format('Y-m-d'),
            // The counterpart correlates people by our internal key, never by
            // staff number: that one is editable and cascades, leaving no trace.
            'employee_external_id' => $personnel ? (string) $personnel->id : null,
            'person_uid' => $personnel?->person_uid,
            'status' => $effectDirection === 'applied' ? 'approved' : 'reversed',
            // A hire can be undone here only while the employee has no records yet, so the
            // counterpart must not offer an undo of its own; a revocation still arrives as 'reversed'.
            'reversible' => ! $template->isHire(),
            'start_date' => $template->effect === 'transfer' ? ($state['effective_date'] ?? $this->dateField($fields, 'effective_date')) : $this->dateField($fields, 'start_date'),
            ...($template->effect === 'transfer' ? ['transfer_pending' => (bool) ($state['transfer_pending'] ?? false), 'transfer_applied_at' => $state['transfer_applied_at'] ?? null, 'transfer_target' => $state['transfer_target'] ?? null] : []),
            'end_date' => $this->dateField($fields, 'end_date'),
            'days' => isset($fields['days']) ? (int) $fields['days'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function dateField(array $fields, string $role): ?string
    {
        return $this->dates->parse($fields[$role] ?? null)?->format('Y-m-d');
    }

    /** @param array<string, mixed> $rawFields */
    private function effectFields(OrderWordTemplate $template, array $rawFields): array
    {
        $fields = [];
        foreach ($template->variables ?? [] as $variable) {
            $role = $variable['effect_role'] ?? null;
            $token = $variable['token'] ?? null;
            if ($role && $token && array_key_exists($token, $rawFields)) {
                $fields[$role] = $rawFields[$token];
            }
        }

        return $fields;
    }
}
