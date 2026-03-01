<?php

namespace App\Modules\Orders\Application\UseCases\Templates;

use App\Models\OrderTemplateSet;
use App\Models\OrderTemplateVersion;
use App\Models\OrderType;
use App\Services\Orders\OrderTemplateAuditLogger;
use App\Services\Orders\OrderTemplateMetadataSyncService;
use Illuminate\Support\Facades\DB;

class SetTypeMetadataBootstrapUseCase
{
    public function __construct(
        private readonly OrderTemplateMetadataSyncService $metadataSyncService,
        private readonly OrderTemplateAuditLogger $auditLogger,
    ) {}

    public function ensureInitialized(
        OrderType $orderType,
        bool $syncMissing = false,
        bool $allowAutoCreateVersion = true,
        ?int $actorId = null
    ): ?OrderTemplateVersion {
        $set = $orderType->templateSet;
        if (! $set) {
            if (! $allowAutoCreateVersion) {
                return null;
            }

            $set = OrderTemplateSet::query()->create([
                'order_type_id' => (int) $orderType->id,
                'name' => (string) $orderType->name,
                'description' => 'Auto-created from UI config editor',
            ]);
        }

        $activeVersion = $set->relationLoaded('activeVersion')
            ? $set->getRelation('activeVersion')
            : $set->activeVersion()->first();

        if (! $activeVersion) {
            $loadedVersions = $set->relationLoaded('versions')
                ? collect($set->getRelation('versions'))->sortByDesc('id')->sortByDesc('version_no')->values()
                : null;

            $latestVersion = $loadedVersions
                ? $loadedVersions->first()
                : $set->versions()->orderByDesc('version_no')->orderByDesc('id')->first();

            if ($latestVersion) {
                $winner = $loadedVersions
                    ? ($loadedVersions->firstWhere('status', 'published') ?? $latestVersion)
                    : ($set->versions()
                        ->where('status', 'published')
                        ->orderByDesc('version_no')
                        ->orderByDesc('id')
                        ->first() ?? $latestVersion);

                DB::transaction(function () use ($set, $winner, $actorId): void {
                    $set->versions()->update([
                        'is_active' => false,
                        'updated_by' => $actorId,
                    ]);

                    $winner->update([
                        'is_active' => true,
                        'updated_by' => $actorId,
                    ]);
                });

                $activeVersion = $winner->fresh();
            } else {
                if (! $allowAutoCreateVersion) {
                    return null;
                }

                $templatePath = trim((string) ($orderType->order?->content ?? ''));
                if ($templatePath === '') {
                    return null;
                }

                $activeVersion = $set->versions()->create([
                    'version_no' => 1,
                    'template_name' => (string) ($orderType->order?->name ?? $orderType->name),
                    'template_path' => $templatePath,
                    'checksum' => null,
                    'status' => 'published',
                    'is_active' => true,
                    'published_at' => now(),
                    'meta' => [
                        'order_id' => (int) ($orderType->order?->id ?? 0),
                        'order_type_id' => (int) $orderType->id,
                        'blade' => (string) ($orderType->order?->blade ?? ''),
                        'source' => 'ui_config_bootstrap',
                    ],
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }
        }

        $hasRowMappings = $activeVersion->relationLoaded('mappings')
            ? $activeVersion->mappings->where('scope', '!=', 'scalar')->isNotEmpty()
            : $activeVersion->mappings()->where('scope', '!=', 'scalar')->exists();

        $hasFields = $activeVersion->relationLoaded('fields')
            ? $activeVersion->fields->isNotEmpty()
            : $activeVersion->fields()->exists();

        $hasSectionBlocks = is_array(data_get($activeVersion->meta, 'form.section_blocks'));

        if ($syncMissing || ! $hasFields || ! $hasRowMappings || ! $hasSectionBlocks) {
            $this->syncLegacyMetadata($orderType, $activeVersion, $syncMissing, $actorId);
            $activeVersion->refresh();
        }

        return $activeVersion;
    }

    public function syncLegacyMetadata(
        OrderType $orderType,
        OrderTemplateVersion $version,
        bool $strictSync = false,
        ?int $actorId = null
    ): array {
        $result = $this->metadataSyncService->sync(
            $version,
            (int) $orderType->id,
            (string) ($orderType->order?->blade ?? data_get($version->meta, 'blade', '')),
            $strictSync,
            $actorId
        );

        $this->auditLogger->log((int) $version->id, 'metadata_bootstrapped', [
            'order_type_id' => (int) $orderType->id,
            'strict_sync' => $strictSync,
            'token_count' => (int) ($result['token_count'] ?? 0),
            'created_fields' => (int) ($result['created_fields'] ?? 0),
            'updated_fields' => (int) ($result['updated_fields'] ?? 0),
            'deleted_fields' => (int) ($result['deleted_fields'] ?? 0),
            'created_mappings' => (int) ($result['created_mappings'] ?? 0),
            'updated_mappings' => (int) ($result['updated_mappings'] ?? 0),
            'deleted_mappings' => (int) ($result['deleted_mappings'] ?? 0),
        ], $actorId);

        return $result;
    }
}
