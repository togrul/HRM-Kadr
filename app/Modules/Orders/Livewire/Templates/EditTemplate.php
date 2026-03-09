<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\TemplateCrud;
use App\Models\Order;
use App\Modules\Orders\Domain\Contracts\OrderTemplateAdmin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use RuntimeException;

class EditTemplate extends Component
{
    use FillComplexArrayTrait;
    use TemplateCrud;

    public $templateModelData;

    public $fileUpdated = false;

    public ?string $currentTemplateChecksum = null;

    public ?string $uploadedTemplateChecksum = null;

    public array $activeVersionBindings = [];

    public function updated($value, $key): void
    {
        if ($value == 'template_data.content') {
            $this->fileUpdated = true;
            $this->uploadedTemplateChecksum = $this->resolveUploadedChecksum($key);
        }
    }

    public function openSetTypeUiConfig(): void
    {
        if (! $this->templateModelData?->id) {
            return;
        }

        $this->dispatch('openSetTypeFromTemplateEdit', templateId: (int) $this->templateModelData->id);
    }

    protected function fillTemplate()
    {
        $this->templateModelData = Order::with([
            'category',
            'types.templateSet.activeVersion',
        ])
            ->where('id', $this->templateModel)
            ->first();

        $updatedData = $this->templateModelData->toArray();

        $this->template_data = $this->mapAttributes(
            attributes: [
                'id', 'name', 'content', 'order_model', 'blade',
            ],
            getFrom: $updatedData
        );

        $this->template_data['order_category_id'] = $this->templateModelData->order_category_id;
        $this->currentTemplateChecksum = $this->resolveStoredChecksum((string) ($this->template_data['content'] ?? ''));
        $this->uploadedTemplateChecksum = null;
        $this->activeVersionBindings = $this->buildActiveVersionBindings();
    }

    public function store()
    {
        $this->validate();

        if ($this->fileUpdated) {
            $filename = "{$this->template_data['name']}.docx";

            $this->template_data['content'] = $this->template_data['content']->storeAs('templates', $filename, 'public');
            $this->currentTemplateChecksum = $this->resolveStoredChecksum((string) $this->template_data['content']);
        }

        try {
            $updated = app(OrderTemplateAdmin::class)->update(
                $this->templateModelData,
                $this->modifyArray($this->template_data)
            );
        } catch (RuntimeException $exception) {
            $this->dispatch('addError', $exception->getMessage());

            return;
        }

        $this->templateModelData = $updated->load('types.templateSet.activeVersion');
        $this->templateModel = (int) $updated->id;
        $this->activeVersionBindings = $this->buildActiveVersionBindings();

        $this->dispatch('templateAdded', __('orders::templates_list.messages.template_updated'));
    }

    private function resolveUploadedChecksum(mixed $file): ?string
    {
        if (! ($file instanceof UploadedFile)) {
            return null;
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            return null;
        }

        $hash = @hash_file('sha256', $realPath);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function resolveStoredChecksum(string $relativePath): ?string
    {
        $path = trim($relativePath);
        if ($path === '') {
            return null;
        }

        $absolutePath = Storage::disk('public')->path($path);
        if (! is_file($absolutePath)) {
            return null;
        }

        $hash = @hash_file('sha256', $absolutePath);

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function buildActiveVersionBindings(): array
    {
        if (! $this->templateModelData) {
            return [];
        }

        $currentPath = trim((string) ($this->template_data['content'] ?? ''));
        $currentChecksum = $this->currentTemplateChecksum;

        return $this->templateModelData->types
            ->map(function ($type) use ($currentPath, $currentChecksum) {
                $activeVersion = $type->templateSet?->activeVersion;
                $versionPath = trim((string) ($activeVersion?->template_path ?? ''));
                $versionChecksum = trim((string) ($activeVersion?->checksum ?? ''));

                $isLinkedByPath = $versionPath !== '' && $versionPath === $currentPath;
                $isLinkedByChecksum = $currentChecksum !== null
                    && $versionChecksum !== ''
                    && hash_equals($versionChecksum, $currentChecksum);

                return [
                    'type_name' => (string) ($type->name ?? ''),
                    'version_id' => (int) ($activeVersion?->id ?? 0),
                    'version_no' => (int) ($activeVersion?->version_no ?? 0),
                    'status' => (string) ($activeVersion?->status ?? '-'),
                    'is_active' => (bool) ($activeVersion?->is_active ?? false),
                    'template_path' => $versionPath,
                    'checksum' => $versionChecksum,
                    'linked' => $isLinkedByPath || $isLinkedByChecksum,
                    'link_reason' => $isLinkedByPath ? 'path' : ($isLinkedByChecksum ? 'checksum' : null),
                ];
            })
            ->sortBy('type_name')
            ->values()
            ->all();
    }
}
