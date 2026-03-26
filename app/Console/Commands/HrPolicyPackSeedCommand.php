<?php

namespace App\Console\Commands;

use App\Models\SelfServiceApprovalRoute;
use App\Services\HrPolicies\HrPolicyPackService;
use Illuminate\Console\Command;

class HrPolicyPackSeedCommand extends Command
{
    protected $signature = 'hr:policy-pack:seed
        {--pack= : Policy pack to seed. Defaults to the active pack}
        {--dry-run : Only inspect what would be seeded}
        {--json : Print JSON summary}';

    protected $description = 'Seed self-service approval route defaults from the selected HR policy pack.';

    public function handle(HrPolicyPackService $service): int
    {
        $pack = (string) ($this->option('pack') ?: $service->activePack());
        $config = $service->packConfig($pack);

        if ($config === []) {
            $this->error("Unknown HR policy pack [{$pack}].");

            return self::FAILURE;
        }

        $routes = (array) ($config['self_service_approval'] ?? []);
        $result = [
            'active_profile' => $service->activeProfile(),
            'pack' => $pack,
            'seeded' => 0,
            'updated' => 0,
            'skipped' => 0,
            'request_types' => [],
        ];

        foreach ($routes as $requestType => $definition) {
            $payload = [
                'request_type' => $requestType,
                'include_primary_approver' => (bool) ($definition['include_primary_approver'] ?? true),
                'include_upper_approver' => (bool) ($definition['include_upper_approver'] ?? false),
                'hr_always_included' => (bool) ($definition['hr_always_included'] ?? true),
                'is_active' => true,
                'personnel_id' => null,
                'structure_id' => null,
                'position_id' => null,
                'approver_personnel_id' => null,
                'fallback_approver_personnel_id' => null,
                'created_by' => null,
            ];

            $existing = SelfServiceApprovalRoute::query()
                ->where('request_type', $requestType)
                ->latest('id')
                ->first();

            $mode = $existing ? 'update' : 'create';
            $result['request_types'][] = [
                'request_type' => $requestType,
                'mode' => $mode,
                'payload' => $payload,
            ];

            if ((bool) $this->option('dry-run')) {
                $result['skipped']++;
                continue;
            }

            if ($existing) {
                $existing->update($payload);
                $result['updated']++;
            } else {
                SelfServiceApprovalRoute::query()->create($payload);
                $result['seeded']++;
            }
        }

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['pack', 'seeded', 'updated', 'skipped'], [[
            $pack,
            $result['seeded'],
            $result['updated'],
            $result['skipped'],
        ]]);

        return self::SUCCESS;
    }
}
