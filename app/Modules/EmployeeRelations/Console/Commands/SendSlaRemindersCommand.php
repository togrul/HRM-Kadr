<?php

namespace App\Modules\EmployeeRelations\Console\Commands;

use App\Modules\EmployeeRelations\Application\Services\SlaReminderService;
use Illuminate\Console\Command;

class SendSlaRemindersCommand extends Command
{
    protected $signature = 'employee-relations:sla-reminders
        {--cooldown-hours= : Eyni işin təkrar xatırladılması üçün minimum saat}
        {--max= : Bir işə salınmada maksimum iş sayı}
        {--json}';

    protected $description = 'İşçi münasibətləri: SLA-sı keçmiş işlər üzrə araşdırıcılara xatırlatma göndərir.';

    public function handle(SlaReminderService $service): int
    {
        $result = $service->run(
            $this->option('cooldown-hours') !== null ? (int) $this->option('cooldown-hours') : null,
            $this->option('max') !== null ? (int) $this->option('max') : null,
        );

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Metric', 'Value'], collect($result)->map(fn ($value, $key): array => [$key, is_bool($value) ? ($value ? 'true' : 'false') : $value])->values()->all());

        return self::SUCCESS;
    }
}
