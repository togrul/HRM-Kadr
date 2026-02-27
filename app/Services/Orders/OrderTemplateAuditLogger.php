<?php

namespace App\Services\Orders;

use App\Models\OrderTemplateVersion;
use App\Models\OrderTemplateVersionAudit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrderTemplateAuditLogger
{
    private ?bool $isAuditTableReady = null;

    public function log(
        OrderTemplateVersion|int $version,
        string $action,
        array $payload = [],
        ?int $changedBy = null
    ): void {
        $versionId = $version instanceof OrderTemplateVersion ? (int) $version->id : (int) $version;
        if ($versionId <= 0 || trim($action) === '' || ! $this->auditTableReady()) {
            return;
        }

        try {
            OrderTemplateVersionAudit::query()->create([
                'order_template_version_id' => $versionId,
                'action' => Str::limit(trim($action), 64, ''),
                'changed_by' => $changedBy ?? auth()->id(),
                'payload' => $this->normalizePayload($payload),
                'created_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isMissingAuditTableQuery($exception)) {
                throw $exception;
            }

            $this->isAuditTableReady = false;
        }
    }

    private function auditTableReady(): bool
    {
        if ($this->isAuditTableReady !== null) {
            return $this->isAuditTableReady;
        }

        return $this->isAuditTableReady = Schema::hasTable('order_template_version_audits');
    }

    private function normalizePayload(array $payload): array
    {
        if (empty($payload)) {
            return [];
        }

        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($encoded)) {
            return [];
        }

        if (strlen($encoded) <= 60000) {
            return $payload;
        }

        return [
            '_meta' => [
                'truncated' => true,
                'original_bytes' => strlen($encoded),
            ],
        ];
    }

    private function isMissingAuditTableQuery(QueryException $exception): bool
    {
        $message = strtolower((string) $exception->getMessage());

        return str_contains($message, 'order_template_version_audits')
            && (
                str_contains($message, "doesn't exist")
                || str_contains($message, 'does not exist')
                || str_contains($message, 'unknown table')
                || str_contains($message, 'base table or view not found')
            );
    }
}
