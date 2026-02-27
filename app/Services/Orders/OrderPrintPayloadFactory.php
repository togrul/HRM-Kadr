<?php

namespace App\Services\Orders;

use App\Models\OrderLog;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrderPrintPayloadFactory
{
    public function __construct(
        private readonly TemplateRegistry $templateRegistry,
        private readonly OrderTemplateSnapshotService $snapshotService,
        private readonly OrderLegacyRenderPayloadBuilder $legacyBuilder,
        private readonly OrderMetadataRenderPayloadBuilder $metadataBuilder,
    ) {
    }

    public function build(OrderLog $orderLog): array
    {
        $orderLog->loadMissing(['order', 'components', 'attributes.component']);

        if (! $orderLog->order) {
            throw new RuntimeException('Order relation is missing for print payload.');
        }

        $orderTypeId = (int) ($orderLog->order_type_id ?? 0);
        $legacyTemplatePath = (string) $orderLog->order->content;
        $snapshot = is_array($orderLog->template_snapshot) ? $orderLog->template_snapshot : [];
        $snapshotMode = trim((string) data_get($snapshot, 'render_mode', (string) ($orderLog->template_render_mode ?? '')));
        $snapshotTemplatePath = trim((string) data_get($snapshot, 'template_path', ''));
        $snapshotVersion = $this->snapshotService->versionFromSnapshot($snapshot);
        $legacyBlocked = $this->templateRegistry->shouldBlockLegacyFallback($orderTypeId, 'print');

        if ($snapshotVersion) {
            $templatePath = $snapshotTemplatePath !== '' ? $snapshotTemplatePath : $legacyTemplatePath;
            if ($legacyBlocked && ($snapshotMode !== 'metadata' || $snapshotVersion->mappings->isEmpty())) {
                throw new RuntimeException('Metadata template mappings are required for this order type.');
            }
            $payload = ($snapshotMode === 'metadata' || $snapshotVersion->mappings->isNotEmpty())
                ? $this->metadataBuilder->build($orderLog, $snapshotVersion)
                : $this->legacyBuilder->build($orderLog);

            if (($payload['mode'] ?? 'legacy') === 'metadata') {
                $payload = $this->mergeWithLegacyCompatibility($payload, $this->legacyBuilder->build($orderLog));
            }

            if (($payload['mode'] ?? 'legacy') === 'legacy') {
                $this->logLegacyFallback($orderLog, 'snapshot_mode_legacy');
            }

            return [
                ...$payload,
                'template_path' => $templatePath,
                'output_base_name' => (string) $orderLog->order->name,
                'context' => [
                    'order_no' => (string) $orderLog->order_no,
                    'order_id' => (int) $orderLog->order_id,
                    'order_type_id' => $orderTypeId,
                    'blade' => (string) ($orderLog->order->blade ?? ''),
                    'render_mode' => (string) ($payload['mode'] ?? 'legacy'),
                    'template_version_id' => $payload['template_version_id'] ?? null,
                    'template_source' => 'snapshot',
                ],
            ];
        }

        $templateVersion = $orderTypeId > 0 ? $this->templateRegistry->activeVersionForOrderType($orderTypeId) : null;
        if ($legacyBlocked && (! $templateVersion || $templateVersion->mappings->isEmpty())) {
            throw new RuntimeException('Metadata template mappings are required for this order type.');
        }
        $templatePath = $this->resolveTemplatePath($templateVersion, $orderTypeId, $legacyTemplatePath);

        if (trim($templatePath) === '') {
            throw new RuntimeException('Order template was not found.');
        }

        $payload = ($templateVersion && $templateVersion->mappings->isNotEmpty())
            ? $this->metadataBuilder->build($orderLog, $templateVersion)
            : $this->legacyBuilder->build($orderLog);

        if (($payload['mode'] ?? 'legacy') === 'metadata') {
            $payload = $this->mergeWithLegacyCompatibility($payload, $this->legacyBuilder->build($orderLog));
        }

        if (($payload['mode'] ?? 'legacy') === 'legacy') {
            $this->logLegacyFallback($orderLog, ! $templateVersion ? 'no_active_template_version' : 'active_template_without_mappings');
        }

        return [
            ...$payload,
            'template_path' => $templatePath,
            'output_base_name' => (string) $orderLog->order->name,
            'context' => [
                'order_no' => (string) $orderLog->order_no,
                'order_id' => (int) $orderLog->order_id,
                'order_type_id' => $orderTypeId,
                'blade' => (string) ($orderLog->order->blade ?? ''),
                'render_mode' => (string) ($payload['mode'] ?? 'legacy'),
                'template_version_id' => $payload['template_version_id'] ?? null,
                'template_source' => 'registry',
            ],
        ];
    }

    private function resolveTemplatePath(?\App\Models\OrderTemplateVersion $templateVersion, int $orderTypeId, string $legacyTemplatePath): string
    {
        if ($templateVersion && trim((string) $templateVersion->template_path) !== '') {
            return (string) $templateVersion->template_path;
        }

        if ($orderTypeId > 0) {
            return (string) $this->templateRegistry->resolveTemplatePathForOrderType($orderTypeId, $legacyTemplatePath);
        }

        return $legacyTemplatePath;
    }

    private function logLegacyFallback(OrderLog $orderLog, string $reason): void
    {
        if (! $this->templateRegistry->shouldLogLegacyFallback()) {
            return;
        }

        Log::warning('orders.template.print_legacy_fallback', [
            'order_no' => (string) $orderLog->order_no,
            'order_id' => (int) $orderLog->order_id,
            'order_type_id' => (int) ($orderLog->order_type_id ?? 0),
            'reason' => $reason,
        ]);
    }

    private function mergeWithLegacyCompatibility(array $metadataPayload, array $legacyPayload): array
    {
        $metadataScalar = is_array($metadataPayload['scalar_values'] ?? null) ? $metadataPayload['scalar_values'] : [];
        $legacyScalar = is_array($legacyPayload['scalar_values'] ?? null) ? $legacyPayload['scalar_values'] : [];
        $mergedScalar = array_merge($legacyScalar, $metadataScalar);

        $metadataRows = is_array($metadataPayload['rows'] ?? null) ? array_values($metadataPayload['rows']) : [];
        $legacyRows = is_array($legacyPayload['rows'] ?? null) ? array_values($legacyPayload['rows']) : [];

        if (empty($metadataRows)) {
            $mergedRows = $legacyRows;
        } else {
            $rowCount = max(count($metadataRows), count($legacyRows));
            $mergedRows = [];
            for ($i = 0; $i < $rowCount; $i++) {
                $legacyRow = is_array($legacyRows[$i] ?? null) ? $legacyRows[$i] : [];
                $metadataRow = is_array($metadataRows[$i] ?? null) ? $metadataRows[$i] : [];
                $mergedRows[] = array_merge($legacyRow, $metadataRow);
            }
        }

        $metadataPayload['scalar_values'] = $mergedScalar;
        $metadataPayload['rows'] = $mergedRows;

        return $metadataPayload;
    }
}
