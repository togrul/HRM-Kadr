<?php

namespace Tests\Feature\PerformanceEvaluation;

use App\Models\PerformanceScorecard;
use App\Models\Position;
use App\Models\User;
use App\Modules\PerformanceEvaluation\Application\Jobs\RecalculateScorecardJob;
use App\Modules\PerformanceEvaluation\Application\Services\Kpi\ScorecardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\Feature\PerformanceEvaluation\Concerns\BuildsKpiCards;
use Tests\TestCase;

/**
 * KPI yenidən hesablamasının növbəyə atılması: bayraq söndürülü olanda əvvəlki sinxron
 * davranış, yanıq olanda job, təkrar sorğuların birləşməsi və uğursuzluq.
 */
class KpiQueuedRecalculationTest extends TestCase
{
    use BuildsKpiCards;
    use RefreshDatabase;

    private Position $position;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();
        User::created(fn (User $user) => grantAllStructures($user));

        $this->position = Position::query()->create(['name' => 'Satış meneceri']);
        $this->hr = User::factory()->create();
        $this->hr->givePermissionTo(Permission::findOrCreate('manage-performance-evaluation', 'web'));
        $this->actingAs($this->hr);
    }

    private function activeCard(): PerformanceScorecard
    {
        $card = $this->specExampleCard();
        app(ScorecardService::class)->transition($card, 'activate', $this->hr);

        return $card->refresh()->load('items');
    }

    public function test_flag_off_recalculates_synchronously_and_dispatches_nothing(): void
    {
        config(['performance_evaluation.kpi.queue_recalculation' => false]);
        $card = $this->activeCard();
        Queue::fake();

        app(ScorecardService::class)->recordActual($card->items[0], 110000, $this->hr);

        Queue::assertNothingPushed();
        $this->assertNotNull($card->refresh()->kpi_score);
    }

    public function test_flag_on_dispatches_the_job_with_the_card_and_actor(): void
    {
        config(['performance_evaluation.kpi.queue_recalculation' => true]);
        $card = $this->activeCard();
        Queue::fake();

        app(ScorecardService::class)->recordActual($card->items[0], 110000, $this->hr);

        Queue::assertPushed(RecalculateScorecardJob::class, fn (RecalculateScorecardJob $job): bool => $job->scorecardId === $card->id
            && $job->actorUserId === $this->hr->id
            && $job->afterCommit === true);
        $this->assertNull($card->refresh()->kpi_score, 'Faktdan sonra xal job-un işini gözləyir.');
    }

    public function test_flag_on_with_sync_connection_scores_the_same_as_the_direct_path(): void
    {
        $values = [110000, 18, 71, 100];

        config(['performance_evaluation.kpi.queue_recalculation' => true, 'queue.default' => 'sync']);
        $card = $this->activeCard();
        foreach ($values as $index => $value) {
            app(ScorecardService::class)->recordActual($card->items[$index], $value, $this->hr);
        }

        $this->assertSame('81.5000', $card->refresh()->kpi_score);
        $this->assertSame('81.5000', $card->final_score);
    }

    public function test_repeated_dispatch_for_the_same_card_coalesces_into_one_job(): void
    {
        config(['performance_evaluation.kpi.queue_recalculation' => true]);
        $card = $this->activeCard();
        Queue::fake();
        $service = app(ScorecardService::class);

        $service->requestRecalculation($card, $this->hr);
        $service->requestRecalculation($card, $this->hr);
        $service->requestRecalculation($card, $this->hr);

        Queue::assertPushed(RecalculateScorecardJob::class, 1);
    }

    public function test_job_skips_a_closed_or_missing_card(): void
    {
        $card = $this->activeCard();
        PerformanceScorecard::query()->whereKey($card->id)->update(['status' => 'closed', 'kpi_score' => 50]);

        app()->call([new RecalculateScorecardJob($card->id, $this->hr->id), 'handle']);
        app()->call([new RecalculateScorecardJob(999999), 'handle']);

        $this->assertSame('50.0000', $card->refresh()->kpi_score);
    }

    public function test_failure_is_logged_and_propagates_on_sync(): void
    {
        config(['performance_evaluation.kpi.queue_recalculation' => true]);
        $card = $this->activeCard();
        $this->app->bind(ScorecardService::class, fn () => new class extends ScorecardService
        {
            public function __construct() {}

            public function recalculate(PerformanceScorecard $card): void
            {
                throw new RuntimeException('hesablama xətası');
            }
        });
        Log::shouldReceive('error')->once()->withArgs(fn (string $message, array $context): bool => $context['scorecard_id'] === $card->id
            && $context['exception'] === 'hesablama xətası');

        $this->expectException(RuntimeException::class);

        // sync bağlantısı failed()-i özü çağırır və istisnanı çağırana ötürür.
        RecalculateScorecardJob::dispatchSync($card->id, $this->hr->id);
    }
}
