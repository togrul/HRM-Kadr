<?php

namespace Tests\Feature\EmployeeLifecycle;

use App\Models\User;
use App\Modules\EmployeeLifecycle\Application\Services\LifecycleDashboardReadService;
use App\Modules\EmployeeLifecycle\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LifecycleDashboardBoundedQueuesTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_cards_count_exactly_what_the_full_lists_counted_while_lists_stay_bounded(): void
    {
        self::seedLargeFixture(130);
        $service = app(LifecycleDashboardReadService::class);

        $allReviews = $service->probationReviews();
        $allMovements = $service->movements();
        $allCases = $service->offboardingCases();
        $unreviewedOpenProbationEvents = DB::table('employee_lifecycle_events as e')
            ->where('e.type', 'probation')
            ->whereNotIn('e.status', ['completed', 'cancelled'])
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('employee_lifecycle_probation_reviews as r')->whereColumn('r.event_id', 'e.id'))
            ->count();

        $payload = $service->dashboard();

        $this->assertSame($allReviews->where('status', 'pending')->count() + $unreviewedOpenProbationEvents, $payload['summary']['probation_queue']);
        $this->assertSame($allMovements->whereNotIn('status', ['completed', 'cancelled'])->count(), $payload['summary']['movement_queue']);
        $this->assertSame($allCases->whereNotIn('status', ['completed', 'cancelled'])->count(), $payload['summary']['offboarding_queue']);
        $this->assertSame(['probation' => 130, 'movement' => 130, 'offboarding' => 130], $payload['queueTotals']);

        $this->assertEquals($allReviews->take(LifecycleDashboardReadService::QUEUE_PAGE)->values(), $payload['probationReviews']->values());
        $this->assertEquals($allMovements->take(LifecycleDashboardReadService::QUEUE_PAGE)->values(), $payload['movements']->values());
        $this->assertEquals($allCases->take(LifecycleDashboardReadService::QUEUE_PAGE)->values(), $payload['offboardingCases']->values());
    }

    public function test_offboarding_queue_still_falls_back_to_open_offboarding_events_without_open_cases(): void
    {
        DB::table('employee_lifecycle_events')->insert([
            ['type' => 'offboarding', 'status' => 'in_progress', 'title' => '', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'offboarding', 'status' => 'completed', 'title' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $payload = app(LifecycleDashboardReadService::class)->dashboard();

        $this->assertSame(1, $payload['summary']['offboarding_queue']);
        $this->assertSame(0, $payload['summary']['movement_queue']);
        $this->assertSame(['probation' => 0, 'movement' => 0, 'offboarding' => 0], $payload['queueTotals']);
    }

    public function test_show_more_grows_one_queue_by_a_page_and_the_bound_cannot_be_lifted_by_the_client(): void
    {
        self::seedLargeFixture(45);
        $this->actingAs($this->manager());

        $component = Livewire::test(Dashboard::class)
            ->assertSee(__('employee-lifecycle::dashboard.actions.show_more', ['count' => 25]));

        $this->assertCount(20, $component->viewData('movements'));

        $component->call('showMoreQueue', 'movement');
        $this->assertCount(40, $component->viewData('movements'));
        $this->assertCount(20, $component->viewData('probationReviews'));

        $component->call('showMoreQueue', 'movement');
        $this->assertCount(45, $component->viewData('movements'));

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('queueLimits.probation', 100000);
    }

    public function test_completion_selects_search_in_sql_stay_limited_and_keep_the_selected_row(): void
    {
        app()->setLocale('az');
        self::seedLargeFixture(80);
        $service = app(LifecycleDashboardReadService::class);

        $options = $service->probationReviewOptions();
        $this->assertCount(LifecycleDashboardReadService::OPTION_LIMIT, $options);
        $first = $service->probationReviews(1)->first();
        $this->assertSame(['id' => $first['id'], 'label' => $first['employee_name'].' · '.$first['review_due_at']], $options[0]);

        $matches = $service->probationReviewOptions('surname7');
        $this->assertNotEmpty($matches);
        foreach ($matches as $option) {
            $this->assertStringContainsString('Surname7', $option['label']);
        }

        $lastId = (int) DB::table('employee_lifecycle_probation_reviews')->max('id');
        $withSelected = $service->probationReviewOptions('surname7', $lastId);
        $this->assertSame($lastId, end($withSelected)['id']);
        $this->assertCount(1, array_filter($service->probationReviewOptions('', $options[0]['id']), fn (array $option): bool => $option['id'] === $options[0]['id']));

        $promotions = $service->movementOptions(mb_strtolower(__('employee-lifecycle::dashboard.movement_types.promotion')));
        $this->assertNotEmpty($promotions);
        foreach ($promotions as $option) {
            $this->assertStringEndsWith(' · '.__('employee-lifecycle::dashboard.movement_types.promotion'), $option['label']);
        }

        $case = $service->offboardingCases(1)->first();
        $this->assertSame($case['employee_name'].' · '.$case['last_working_date'], $service->offboardingCaseOptions()[0]['label']);
    }

    public function test_the_completion_panel_renders_searchable_bounded_selects(): void
    {
        self::seedLargeFixture(80);
        $this->actingAs($this->manager());

        Livewire::test(Dashboard::class)
            ->call('openPanel', 'complete')
            ->assertSeeHtml('wire:model.live.debounce.300ms="probationOptionSearch"')
            ->assertSeeHtml('wire:model.live.debounce.300ms="movementOptionSearch"')
            ->assertSeeHtml('wire:model.live.debounce.300ms="offboardingOptionSearch"')
            ->set('movementOptionSearch', 'Surname12')
            ->assertSee('Surname12 Name12');
    }

    private function manager(): User
    {
        foreach (['show-employee-lifecycle', 'manage-employee-lifecycle'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['show-employee-lifecycle', 'manage-employee-lifecycle']);

        return $user;
    }

    /**
     * Seeds $perQueue probation reviews, movements and offboarding cases (each with its event,
     * statuses cycling through every value), plus unreviewed probation events and tasks.
     */
    public static function seedLargeFixture(int $perQueue): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full']);
        foreach ([1 => 'Lifecycle HQ', 2 => 'Lifecycle Target'] as $id => $name) {
            DB::table('structures')->insertOrIgnore(['id' => $id, 'name' => $name, 'shortname' => 'L'.$id, 'parent_id' => null, 'coefficient' => 1.10, 'code' => 30 + $id, 'level' => 1]);
            DB::table('positions')->insertOrIgnore(['id' => $id, 'name' => 'Position '.$id]);
        }
        $userId = DB::table('users')->insertGetId(['name' => 'Queue Owner', 'email' => 'queue-owner-'.uniqid().'@example.test', 'password' => 'x', 'is_active' => true]);

        $now = now()->toDateTimeString();
        $personnel = [];
        for ($i = 1; $i <= $perQueue; $i++) {
            $personnel[] = [
                'tabel_no' => sprintf('Q%06d', $i),
                'surname' => 'Surname'.$i,
                'name' => 'Name'.$i,
                'patronymic' => 'Patronymic',
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => 1,
                'pin' => sprintf('Q%06d', $i),
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => 1,
                'position_id' => 1,
                'work_norm_id' => 1,
                'join_work_date' => '2026-01-01',
                'added_by' => $userId,
                'is_pending' => false,
            ];
        }
        foreach (array_chunk($personnel, 300) as $chunk) {
            DB::table('personnels')->insert($chunk);
        }
        $personnelIds = DB::table('personnels')->where('tabel_no', 'like', 'Q%')->orderBy('id')->pluck('id', 'tabel_no')->all();

        $queues = [
            'probation' => ['employee_lifecycle_probation_reviews', ['pending', 'completed', 'pending'], 'review_due_at'],
            'movement' => ['employee_lifecycle_movements', ['planned', 'in_progress', 'completed', 'cancelled'], 'effective_date'],
            'offboarding' => ['employee_lifecycle_offboarding_cases', ['open', 'in_progress', 'completed', 'cancelled'], 'last_working_date'],
        ];

        foreach ($queues as $type => [$table, $statuses, $dateColumn]) {
            $events = [];
            $i = 0;
            foreach ($personnelIds as $tabelNo => $personnelId) {
                $events[] = [
                    'personnel_id' => $personnelId,
                    'tabel_no' => $tabelNo,
                    'type' => $type,
                    'status' => in_array($statuses[$i % count($statuses)], ['completed', 'cancelled'], true) ? $statuses[$i % count($statuses)] : 'in_progress',
                    'title' => '',
                    'deadline_at' => now()->addDays(($i % 60) - 30)->toDateString(),
                    'owner_user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $i++;
            }
            foreach (array_chunk($events, 300) as $chunk) {
                DB::table('employee_lifecycle_events')->insert($chunk);
            }
            $eventIds = DB::table('employee_lifecycle_events')->where('type', $type)->orderBy('id')->pluck('id')->all();

            $rows = [];
            $i = 0;
            foreach ($personnelIds as $tabelNo => $personnelId) {
                $row = [
                    'event_id' => $eventIds[$i],
                    'personnel_id' => $i % 10 === 0 ? null : $personnelId,
                    'tabel_no' => $tabelNo,
                    $dateColumn => now()->addDays(($i % 60) - 30)->toDateString(),
                    'status' => $statuses[$i % count($statuses)],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if ($type === 'movement') {
                    $row += ['movement_type' => ['transfer', 'promotion', 'role_change'][$i % 3], 'current_structure_id' => 1, 'current_position_id' => 1, 'target_structure_id' => 2, 'target_position_id' => 2];
                }
                if ($type === 'probation') {
                    $row += ['manager_user_id' => $userId, 'hr_reviewer_user_id' => $userId];
                }
                if ($type === 'offboarding') {
                    $row += ['owner_user_id' => $userId, 'exit_interview_completed_at' => $i % 2 === 0 ? $now : null];
                }
                $rows[] = $row;
                $i++;
            }
            foreach (array_chunk($rows, 300) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }

        // Open probation events nobody has reviewed yet count towards the probation queue.
        DB::table('employee_lifecycle_events')->insert(array_map(fn (int $n): array => [
            'type' => 'probation', 'status' => 'planned', 'title' => '', 'created_at' => $now, 'updated_at' => $now,
        ], range(1, 7)));
    }
}
