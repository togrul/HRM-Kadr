<?php

namespace App\Modules\Orders\Support\Traits\Templates;

use App\Modules\Orders\Application\UseCases\Templates\SetTypeReadUseCase;
use App\Modules\Orders\Application\UseCases\Templates\SetTypeUiConfigLifecycleUseCase;
use App\Services\Orders\TemplatePlaceholderCoverageService;
use Illuminate\Support\Str;
use Throwable;

trait HandlesSetTypeUiConfigLifecycle
{
    public function updatedNewFieldKey($value): void
    {
        $normalized = ltrim(trim((string) $value), '$');
        if ($this->newFieldAlias === '') {
            $this->newFieldAlias = $normalized;
        }

        if ($this->newFieldSelectedName === '' && $normalized !== '') {
            $this->newFieldSelectedName = Str::camel($normalized);
        }
    }

    public function updatedNewFieldInput($value): void
    {
        $input = trim((string) $value);

        if (! in_array($input, ['select', 'radio-list'], true)) {
            return;
        }

        if ($this->newFieldSelectedName === '') {
            $seed = $this->newFieldAlias !== '' ? $this->newFieldAlias : $this->newFieldKey;
            $seed = ltrim(trim((string) $seed), '$');
            if ($seed !== '') {
                $this->newFieldSelectedName = Str::camel($seed);
            }
        }

        if ($input === 'select' && $this->newFieldSearchField === '' && $this->newFieldSelectedName !== '') {
            $this->newFieldSearchField = 'search.'.$this->newFieldSelectedName;
        }
    }

    public function openUiConfig(int $orderTypeId, ?int $versionId = null): void
    {
        if (! $this->ensureTemplateUiPermission('view')) {
            return;
        }

        $orderType = app(SetTypeReadUseCase::class)->orderTypeForUiConfig($orderTypeId);

        if (! $orderType) {
            return;
        }

        if ($orderType->templateSet) {
            app(SetTypeUiConfigLifecycleUseCase::class)->reconcileSingleActiveForSet(
                (int) $orderType->templateSet->id,
                auth()->id(),
                (int) $orderType->id
            );
        }

        $orderType->load([
            'order',
            'templateSet.activeVersion.fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'templateSet.activeVersion.mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'templateSet.versions' => fn ($query) => $query->orderByDesc('version_no'),
        ]);

        $activeVersion = $this->ensureUiMetadataInitialized($orderType);
        if (! $activeVersion) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.active_version_not_found'));
            return;
        }

        $candidateVersionId = $versionId ?? (int) $activeVersion->id;
        $targetVersion = $orderType->templateSet
            ?->versions
            ?->firstWhere('id', (int) $candidateVersionId);

        if (! $targetVersion) {
            $targetVersion = $activeVersion;
        }

        if ((int) $targetVersion->id === (int) $activeVersion->id) {
            $targetVersion = $activeVersion;
        }

        $this->uiConfigVersions = $orderType->templateSet
            ?->versions
            ?->map(fn ($version) => [
                'id' => (int) $version->id,
                'version_no' => (int) $version->version_no,
                'status' => (string) $version->status,
                'is_active' => (bool) $version->is_active,
                'published_at' => $version->published_at?->format('Y-m-d H:i'),
            ])
            ?->values()
            ?->all() ?? [];

        $targetVersion->loadMissing([
            'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $hasTargetRowMappings = $targetVersion->mappings
            ->where('scope', '!=', 'scalar')
            ->isNotEmpty();

        if ($targetVersion->fields->isEmpty() || ! $hasTargetRowMappings) {
            $this->bootstrapMetadataFromTemplate($orderType, $targetVersion, false);
            $targetVersion->refresh()->load([
                'fields' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ]);
        }

        $this->uiConfigOrderTypeId = (int) $orderType->id;
        $this->uiConfigVersionId = (int) $targetVersion->id;
        $this->uiConfigFieldMeta = $targetVersion->fields
            ->map(fn ($field) => [
                'id' => (int) $field->id,
                'field_key' => (string) $field->field_key,
                'label' => (string) $field->label,
                'field_type' => (string) $field->field_type,
                'sort_order' => (int) $field->sort_order,
            ])->all();

        $this->uiConfigDraft = $targetVersion->fields
            ->mapWithKeys(fn ($field) => [(int) $field->id => $this->normalizeUiConfigDraft(
                $field->ui_config,
                (int) $field->sort_order,
                (string) $field->field_key,
                (string) $field->field_type,
                (bool) $field->is_required,
                $field->validation_config
            )])
            ->all();

        $this->sectionBlocksDraft = $this->normalizeSectionBlocksDraft(
            $targetVersion,
            $orderType->order?->blade
        );

        $this->mappingDraft = $targetVersion->mappings
            ->map(fn ($mapping) => [
                'id' => (int) $mapping->id,
                'placeholder' => (string) $mapping->placeholder,
                'field_key' => (string) $mapping->field_key,
                'scope' => (string) ($mapping->scope ?: 'row'),
                'order' => (int) $mapping->sort_order,
                'mapping_config_json' => is_array($mapping->mapping_config)
                    ? json_encode($mapping->mapping_config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : '',
            ])
            ->values()
            ->all();

        if (empty($this->mappingDraft) && ! empty($this->uiConfigFieldMeta)) {
            $this->mappingDraft = collect($this->uiConfigFieldMeta)
                ->map(fn ($field, $index) => [
                    'id' => null,
                    'placeholder' => '$'.ltrim((string) ($field['field_key'] ?? ''), '$'),
                    'field_key' => (string) ($field['field_key'] ?? ''),
                    'scope' => 'row',
                    'order' => (int) (($index + 1) * 10),
                    'mapping_config_json' => '',
                ])
                ->filter(fn ($row) => trim((string) $row['field_key']) !== '')
                ->values()
                ->all();
        }

        $this->uiPlaceholderCoverage = app(TemplatePlaceholderCoverageService::class)
            ->analyzeForVersion($targetVersion, $this->mappingDraft);

        $this->uiConfigAuditTrail = $targetVersion->audits()
            ->with('changedBy:id,name')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn ($audit) => [
                'id' => (int) $audit->id,
                'action' => (string) $audit->action,
                'actor' => (string) ($audit->changedBy?->name ?? __('orders::template_set_type.labels.system')),
                'created_at' => $audit->created_at?->format('Y-m-d H:i'),
                'payload' => is_array($audit->payload) ? $audit->payload : [],
            ])
            ->values()
            ->all();
    }

    public function createUiDraftVersion(): void
    {
        if (! $this->ensureTemplateUiPermission('version')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId) {
            return;
        }

        $created = app(SetTypeUiConfigLifecycleUseCase::class)->createDraftVersion(
            (int) $this->uiConfigOrderTypeId,
            $this->uiConfigVersionId ? (int) $this->uiConfigVersionId : null,
            auth()->id()
        );

        if (! $created) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.could_not_create_draft'));
            return;
        }

        $this->openUiConfig((int) $this->uiConfigOrderTypeId, (int) $created->id);
        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.draft_created'));
    }

    public function publishUiConfigVersion(int $versionId): void
    {
        if (! $this->ensureTemplateUiPermission('version')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId) {
            return;
        }

        $version = \App\Models\OrderTemplateVersion::query()
            ->with([
                'mappings' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->find($versionId);

        if (! $version) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.selected_version_not_found'));
            return;
        }

        $mappings = $version->mappings
            ->map(fn ($mapping) => [
                'placeholder' => (string) $mapping->placeholder,
                'field_key' => (string) $mapping->field_key,
                'scope' => (string) $mapping->scope,
            ])
            ->values()
            ->all();

        $coverage = app(TemplatePlaceholderCoverageService::class)
            ->analyzeForVersion($version, $mappings);

        if (empty($coverage['inspectable'])) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.coverage_unavailable_upload_docx'));
            return;
        }

        if (! empty($coverage['missing_placeholders'])) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.cannot_publish_missing_mappings', [
                'placeholders' => implode(', ', $coverage['missing_placeholders']),
            ]));
            return;
        }

        try {
            $published = app(SetTypeUiConfigLifecycleUseCase::class)
                ->publishVersion($versionId, auth()->id());
        } catch (Throwable $exception) {
            $this->dispatch('typesUpdated', $exception->getMessage());
            return;
        }

        if (! $published) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.could_not_publish_version'));
            return;
        }

        $this->openUiConfig((int) $this->uiConfigOrderTypeId, (int) $published->id);
        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.version_published'));
    }

    public function rollbackUiConfigVersion(int $versionId): void
    {
        if (! $this->ensureTemplateUiPermission('version')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId) {
            return;
        }

        try {
            $rolledBack = app(SetTypeUiConfigLifecycleUseCase::class)
                ->rollbackVersion($versionId, auth()->id());
        } catch (Throwable $exception) {
            $this->dispatch('typesUpdated', $exception->getMessage());
            return;
        }

        if (! $rolledBack) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.could_not_rollback'));
            return;
        }

        $this->openUiConfig((int) $this->uiConfigOrderTypeId, (int) $rolledBack->id);
        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.rollback_completed'));
    }

    public function deleteUiDraftVersion(int $versionId): void
    {
        if (! $this->ensureTemplateUiPermission('version')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId) {
            return;
        }

        $deleted = app(SetTypeUiConfigLifecycleUseCase::class)
            ->deleteVersion($versionId, auth()->id());
        if (! $deleted) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.delete_version_guard'));
            return;
        }

        $this->openUiConfig((int) $this->uiConfigOrderTypeId);
        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.version_deleted'));
    }

    public function bootstrapUiConfigMetadata(): void
    {
        if (! $this->ensureTemplateUiPermission('metadata')) {
            return;
        }

        if (! $this->uiConfigOrderTypeId) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.open_ui_first'));
            return;
        }

        $orderType = app(SetTypeReadUseCase::class)
            ->orderTypeForMetadataBootstrap((int) $this->uiConfigOrderTypeId);

        if (! $orderType) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.order_type_not_found'));
            return;
        }

        $versionBefore = $orderType->templateSet?->activeVersion;
        $previousCount = $versionBefore ? $versionBefore->fields()->count() : 0;

        $activeVersion = $this->ensureUiMetadataInitialized($orderType, true);
        if (! $activeVersion) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.active_version_not_found'));
            return;
        }

        $currentCount = $activeVersion->fields()->count();
        $this->openUiConfig((int) $this->uiConfigOrderTypeId);

        if ($currentCount === 0) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.no_placeholders_detected'));
            return;
        }

        if ($currentCount === $previousCount) {
            $this->dispatch('typesUpdated', __('orders::template_set_type.messages.metadata_up_to_date'));
            return;
        }

        $this->dispatch('typesUpdated', __('orders::template_set_type.messages.metadata_generated'));
    }

    public function closeUiConfig(): void
    {
        $this->uiConfigOrderTypeId = null;
        $this->uiConfigVersionId = null;
        $this->uiConfigFieldMeta = [];
        $this->uiConfigDraft = [];
        $this->sectionBlocksDraft = [];
        $this->mappingDraft = [];
        $this->uiPlaceholderCoverage = [];
        $this->uiConfigAuditTrail = [];
        $this->uiConfigVersions = [];
        $this->resetNewFieldDraft();
        $this->resetValidation(['uiConfigDraft.*', 'sectionBlocksDraft.*', 'mappingDraft.*']);
    }
}
