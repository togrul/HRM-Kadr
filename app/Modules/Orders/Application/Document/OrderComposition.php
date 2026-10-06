<?php

namespace App\Modules\Orders\Application\Document;

/**
 * What the author entered in the order composer, frozen for one action. Every composer
 * service works from this instead of reading Livewire state.
 */
final readonly class OrderComposition
{
    /**
     * @param  array<string,mixed>  $fields  manual field key => value
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
    ) {}

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
