<?php

namespace App\Modules\Personnel\Console\Commands;

use App\Models\PersonnelVacation;
use App\Models\User;
use App\Modules\Personnel\Application\Services\MyHr\MyHrRequestReviewService;
use Illuminate\Console\Command;

class RepairLegacySelfServiceVacationOrdersCommand extends Command
{
    protected $signature = 'personnel:repair-legacy-self-service-vacation-orders
        {--reviewer-id= : User id to attribute generated operational orders to}
        {--dry-run : Only inspect matching records}
        {--json : Return JSON summary}';

    protected $description = 'Bind missing operational orders for approved legacy self-service vacation requests.';

    public function handle(MyHrRequestReviewService $service): int
    {
        $reviewer = $this->resolveReviewer();

        if (! $reviewer) {
            $this->error('No active reviewer account could be resolved for vacation order repair.');

            return self::FAILURE;
        }

        $rows = PersonnelVacation::query()
            ->where('submission_source', 'employee_self_service')
            ->where('approval_status', 'approved')
            ->where(function ($query): void {
                $query->whereNull('order_no')
                    ->orWhere('order_no', '');
            })
            ->orderBy('id')
            ->get();

        $result = [
            'reviewer_id' => $reviewer->id,
            'matched' => $rows->count(),
            'repaired' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $vacation) {
            if ($this->option('dry-run')) {
                $result['skipped']++;
                continue;
            }

            try {
                $service->bindOperationalVacationOrder($vacation, $reviewer);
                $result['repaired']++;
            } catch (\Throwable $e) {
                $result['errors'][] = [
                    'vacation_id' => $vacation->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Metric', 'Value'], [
            ['reviewer_id', $result['reviewer_id']],
            ['matched', $result['matched']],
            ['repaired', $result['repaired']],
            ['skipped', $result['skipped']],
            ['errors', count($result['errors'])],
        ]);

        foreach ($result['errors'] as $error) {
            $this->warn(sprintf('#%d %s', $error['vacation_id'], $error['message']));
        }

        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }

    private function resolveReviewer(): ?User
    {
        $explicitId = (int) $this->option('reviewer-id');

        if ($explicitId > 0) {
            return User::query()->where('is_active', true)->find($explicitId);
        }

        return User::query()
            ->where('is_active', true)
            ->whereHas('permissions', fn ($query) => $query->whereIn('name', [
                'review-all-self-service-requests',
                'review-self-service-requests',
            ]))
            ->orderBy('id')
            ->first()
            ?: User::query()->where('is_active', true)->orderBy('id')->first();
    }
}
