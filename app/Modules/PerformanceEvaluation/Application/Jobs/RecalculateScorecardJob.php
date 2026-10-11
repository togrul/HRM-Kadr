<?php

namespace App\Modules\PerformanceEvaluation\Application\Jobs;

use App\Models\PerformanceScorecard;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\CauserResolver;
use Throwable;

/**
 * KPI kartının xallarını növbədə yenidən hesablayır. Düstur dəyişmir: yalnız
 * ScorecardService::recalculate() çağırılır; job kartın bu andakı təsdiqlənmiş faktlarını
 * oxuyur, ona görə eyni kart üçün gözləyən təkrar sorğular bir icraya birləşir.
 *
 * İcazə yoxlaması çağıran tərəfdə qalır (ScorecardService::authorizeRole); job yalnız
 * artıq icazə verilmiş dəyişikliyin nəticəsini hesablayır və çağıranın oxuya biləcəyindən
 * geniş məlumat götürmür - bir kartın öz maddələri, faktları, forması və kalibrləmələri.
 */
class RecalculateScorecardJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** Növbə kilidi transaksiya geri qaytarılsa belə bu qədər saniyədən sonra açılır. */
    public int $uniqueFor = 120;

    /** Üst-üstə düşmə səbəbindən təkrar buraxılma üçün yox, istisna üçün gecikmə. */
    public array $backoff = [5, 15, 60, 300];

    /**
     * @param  int|null  $actorUserId  dəyişikliyi edən istifadəçi; fəaliyyət jurnalında səbəbkar kimi bərpa olunur
     */
    public function __construct(public readonly int $scorecardId, public readonly ?int $actorUserId = null)
    {
        $this->afterCommit = true;
        $queue = config('performance_evaluation.kpi.queue_name');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function uniqueId(): string
    {
        return 'kpi-scorecard-recalculate:'.$this->scorecardId;
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('kpi-scorecard-'.$this->scorecardId))->releaseAfter(10)->expireAfter(120)];
    }

    public function handle(ScorecardService $scorecards): void
    {
        $card = PerformanceScorecard::query()->find($this->scorecardId);

        // Kart silinibsə və ya bağlanıbsa (snapshot dondurulub) toxunmuruq: sinxron yolda da
        // bağlanmış kart yenidən hesablanmırdı.
        if ($card === null || $card->status === 'closed') {
            return;
        }

        $actor = $this->actorUserId === null ? null : User::query()->find($this->actorUserId);

        if ($actor !== null) {
            CauserResolver::setCauser($actor);
        }

        try {
            $scorecards->recalculate($card);
        } finally {
            if ($actor !== null) {
                CauserResolver::setCauser(null);
            }
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('KPI kartının yenidən hesablanması uğursuz oldu.', [
            'scorecard_id' => $this->scorecardId,
            'actor_user_id' => $this->actorUserId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
