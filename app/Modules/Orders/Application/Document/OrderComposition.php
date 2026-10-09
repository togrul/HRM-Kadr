<?php

namespace App\Modules\Orders\Application\Document;

/**
 * What the author entered in the order composer, frozen for one action. Every composer
 * service works from this instead of reading Livewire state.
 */
final readonly class OrderComposition
{
    /**
     * @param  array<string,mixed>  $fields  manual field key => value (the order-level ones)
     * @param  list<array{personnel_id?:int|string|null,fields?:array<string,mixed>}>  $participants  a multi-participant
     *                                                                                                order's employees in document order, each with their own field values
     */
    public function __construct(
        public string $presetCode,
        public ?int $personnelId,
        public ?int $candidateId,
        public ?int $hireStructureId,
        public ?int $hirePositionId,
        public array $fields,
        public string $orderNumber,
        public string $orderDate,
        public string $organizationCity,
        public ?int $editOrderId = null,
        public array $participants = [],
    ) {}

    /**
     * The participants as [personnel_id, own fields] in order, without blanks. When a
     * multi-participant template is composed with only the single employee picker (an older
     * caller), that employee is the one participant.
     *
     * @return list<array{personnel_id:int,fields:array<string,mixed>}>
     */
    public function participantList(): array
    {
        $list = [];
        foreach ($this->participants as $participant) {
            $id = (int) ($participant['personnel_id'] ?? 0);
            if ($id > 0) {
                $list[] = ['personnel_id' => $id, 'fields' => (array) ($participant['fields'] ?? [])];
            }
        }

        if ($list === [] && $this->personnelId) {
            $list[] = ['personnel_id' => (int) $this->personnelId, 'fields' => []];
        }

        return $list;
    }

    /** The first participant — what the snapshot's personnel_id and employee.* variables refer to. */
    public function leadPersonnelId(): ?int
    {
        return $this->participantList()[0]['personnel_id'] ?? null;
    }

    public function isEditing(): bool
    {
        return $this->editOrderId !== null;
    }

    /**
     * File name for the downloaded .docx: "<type>_<number>.docx", path separators made safe.
     */
    public function downloadName(): string
    {
        $code = $this->presetCode !== '' ? $this->presetCode : 'order';
        $number = $this->orderNumber !== '' ? '_'.$this->orderNumber : '';

        return str_replace(['/', '\\'], '-', $code.$number).'.docx';
    }
}
