<?php

namespace App\Services\Orders;

use App\Models\OrderLog;
use App\Modules\Orders\Domain\Contracts\OrderTemplateRegistry;
use App\Services\Orders\DTO\OrderPrintPayloadData;
use RuntimeException;

class OrderPrintPayloadFactory
{
    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly OrderTemplateSnapshotService $snapshotService,
        private readonly OrderMetadataRenderPayloadBuilder $metadataBuilder,
    ) {
    }

    public function build(OrderLog $orderLog): array
    {
        $orderLog->loadMissing(['order', 'components', 'attributes.component']);

        if (! $orderLog->order) {
            throw new RuntimeException(__('orders::template_runtime.messages.order_relation_missing_for_print_payload'));
        }

        $orderTypeId = (int) ($orderLog->order_type_id ?? 0);
        $snapshot = is_array($orderLog->template_snapshot) ? $orderLog->template_snapshot : [];
        $snapshotMode = trim((string) data_get($snapshot, 'render_mode', (string) ($orderLog->template_render_mode ?? '')));
        $snapshotTemplatePath = trim((string) data_get($snapshot, 'template_path', ''));
        $snapshotVersion = $this->snapshotService->versionFromSnapshot($snapshot);

        if ($snapshotVersion) {
            $templatePath = $this->resolveStrictSnapshotTemplatePath($snapshotTemplatePath, $snapshotVersion);
            if ($snapshotMode !== 'metadata' || ! $this->hasRowMappings($snapshotVersion)) {
                throw new RuntimeException(__('orders::template_runtime.messages.metadata_mappings_required_for_order_type'));
            }
            $payload = $this->metadataBuilder->build($orderLog, $snapshotVersion);

            if (($payload['mode'] ?? 'metadata') !== 'metadata') {
                throw new RuntimeException(__('orders::template_runtime.messages.metadata_print_payload_not_generated'));
            }

            return $this->toPayloadDto(
                templatePath: $templatePath,
                payload: $payload,
                orderLog: $orderLog,
                orderTypeId: $orderTypeId,
                templateSource: 'snapshot',
            )->toArray();
        }

        $templateVersion = $orderTypeId > 0 ? $this->templateRegistry->activeVersionForOrderType($orderTypeId) : null;
        if (! $templateVersion || ! $this->hasRowMappings($templateVersion)) {
            throw new RuntimeException(__('orders::template_runtime.messages.metadata_mappings_required_for_order_type'));
        }
        $templatePath = $this->resolveTemplatePath(
            templateVersion: $templateVersion,
            orderTypeId: $orderTypeId
        );

        if (trim($templatePath) === '') {
            throw new RuntimeException(__('orders::template_runtime.messages.order_template_not_found'));
        }

        $payload = $this->metadataBuilder->build($orderLog, $templateVersion);

        if (($payload['mode'] ?? 'metadata') !== 'metadata') {
            throw new RuntimeException(__('orders::template_runtime.messages.metadata_print_payload_not_generated'));
        }

        return $this->toPayloadDto(
            templatePath: $templatePath,
            payload: $payload,
            orderLog: $orderLog,
            orderTypeId: $orderTypeId,
            templateSource: 'registry',
        )->toArray();
    }

    private function resolveTemplatePath(
        ?\App\Models\OrderTemplateVersion $templateVersion,
        int $orderTypeId
    ): string
    {
        if ($templateVersion && trim((string) $templateVersion->template_path) !== '') {
            return (string) $templateVersion->template_path;
        }

        return $orderTypeId > 0
            ? (string) $this->templateRegistry->resolveTemplatePathForOrderType($orderTypeId)
            : '';
    }

    private function resolveStrictSnapshotTemplatePath(string $snapshotTemplatePath, \App\Models\OrderTemplateVersion $snapshotVersion): string
    {
        $path = trim($snapshotTemplatePath);
        if ($path !== '') {
            return $path;
        }

        $versionPath = trim((string) $snapshotVersion->template_path);
        if ($versionPath !== '') {
            return $versionPath;
        }

        throw new RuntimeException(__('orders::template_runtime.messages.metadata_template_file_missing_in_snapshot'));
    }

    private function hasRowMappings(\App\Models\OrderTemplateVersion $version): bool
    {
        return collect($version->mappings ?? [])
            ->contains(fn ($mapping) => trim((string) ($mapping->scope ?? 'row')) !== 'scalar');
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function toPayloadDto(
        string $templatePath,
        array $payload,
        OrderLog $orderLog,
        int $orderTypeId,
        string $templateSource
    ): OrderPrintPayloadData {
        return new OrderPrintPayloadData(
            templatePath: $templatePath,
            scalarValues: (array) ($payload['scalar_values'] ?? []),
            rows: (array) ($payload['rows'] ?? []),
            outputBaseName: (string) $orderLog->order->name,
            context: [
                'order_no' => (string) $orderLog->order_no,
                'order_id' => (int) $orderLog->order_id,
                'order_type_id' => $orderTypeId,
                'blade' => (string) ($orderLog->order->blade ?? ''),
                'render_mode' => 'metadata',
                'template_version_id' => $payload['template_version_id'] ?? null,
                'template_source' => $templateSource,
            ],
        );
    }
}
