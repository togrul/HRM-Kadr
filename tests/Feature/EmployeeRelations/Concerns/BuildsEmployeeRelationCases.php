<?php

namespace Tests\Feature\EmployeeRelations\Concerns;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Modules\EmployeeRelations\Application\Services\CaseService;
use App\Modules\EmployeeRelations\Domain\Models\RelationCase;
use App\Modules\EmployeeRelations\Domain\Models\RelationCaseType;
use App\Modules\Orders\Contracts\LinkedOrderCancellation;
use App\Modules\Orders\Contracts\OrderDrafter;
use App\Services\StructureService;
use App\Services\UserPersonnelLinkResolver;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * İşçi münasibətləri testləri üçün ümumi qurğu: struktur, işçi, icazəli istifadəçi,
 * istifadəçi↔işçi bağı, saxta əmr qaralaması (OrderDrafter) və saxta məzuniyyət mənbəyi.
 */
trait BuildsEmployeeRelationCases
{
    /** @var list<array{template: string, personnel_id: int, fields: array<string, string>, number: string}> */
    public array $draftedOrders = [];

    /** @var array<string, string> */
    public array $fakeTemplates = ['intizam_tenbehi' => 'İntizam tənbehi'];

    /**
     * Saxta Orders: əmr id → vəziyyət (LinkedOrderCancellation::STATE_*). Göstərilməyən
     * əmr «təsdiq gözləyir» sayılır; testlər əmrin ləğvini burada simulyasiya edir.
     *
     * @var array<int, string>
     */
    public array $fakeOrderStates = [];

    protected function erStructure(string $name): Structure
    {
        return Structure::query()->create(['name' => $name, 'shortname' => Str::upper(Str::substr($name, 0, 3))]);
    }

    protected function erPersonnel(string $surname, int $structureId, string $name = 'Ad'): Personnel
    {
        $position = Position::query()->create(['name' => 'Vəzifə '.Str::random(4)]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'ER'.Str::upper(Str::random(6)),
            'surname' => $surname, 'name' => $name, 'patronymic' => 'Ata',
            'birthdate' => '1985-01-01', 'gender' => 1,
            'email' => Str::lower(Str::random(8)).'@example.com', 'mobile' => '994500000000', 'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'X', 'education_degree_id' => 1, 'work_norm_id' => 1,
            'structure_id' => $structureId, 'position_id' => $position->id,
            'join_work_date' => '2015-01-01', 'added_by' => 1, 'is_pending' => false,
        ]));
    }

    /**
     * @param  list<string>  $permissions
     * @param  list<int>  $structureIds
     */
    protected function erUser(array $permissions, array $structureIds = [], bool $allStructures = false, string $name = ''): User
    {
        $user = User::factory()->create($name !== '' ? ['name' => $name] : []);

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $role = Role::query()->create(['name' => 'er-test-'.Str::random(8), 'guard_name' => 'web']);
        $role->forceFill(['all_structures' => $allStructures])->save();
        $role->structures()->sync($structureIds);
        $user->assignRole($role);

        app(StructureService::class)->forgetUser((int) $user->getKey());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user->fresh();
    }

    protected function erLink(User $user, Personnel $personnel): void
    {
        UserPersonnelLink::query()->create([
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'manual',
            'resolved_at' => now(),
        ]);
        app(UserPersonnelLinkResolver::class)->forget();
    }

    protected function erTypeId(string $code = 'disciplinary'): int
    {
        return (int) RelationCaseType::query()->where('code', $code)->value('id');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function erOpenCase(User $actor, ?Personnel $subject, array $overrides = []): RelationCase
    {
        return app(CaseService::class)->openByStaff($actor, [
            'case_type_id' => $this->erTypeId(),
            'title' => 'İşə gecikmə',
            'description' => 'Əməkdaş ay ərzində dəfələrlə işə gecikib.',
            'occurred_at' => today()->subDays(5)->toDateString(),
            'discovered_at' => today()->subDays(2)->toDateString(),
            'subject_personnel_id' => $subject?->id,
            ...$overrides,
        ]);
    }

    protected function erFakeDrafter(): void
    {
        $test = $this;
        $this->erFakeLinkedOrders();

        $this->app->instance(OrderDrafter::class, new class($test) implements OrderDrafter
        {
            public function __construct(private readonly object $test) {}

            public function hasTemplate(string $code): bool
            {
                return array_key_exists($code, $this->test->fakeTemplates);
            }

            public function personnelTemplates(string|array|null $effect = null): array
            {
                return $this->test->fakeTemplates;
            }

            public function draft(string $templateCode, Personnel $personnel, array $fieldsByLabel, string $orderNumber): OrderLog
            {
                $this->test->draftedOrders[] = [
                    'template' => $templateCode,
                    'personnel_id' => (int) $personnel->id,
                    'fields' => $fieldsByLabel,
                    'number' => $orderNumber,
                ];

                return (new OrderLog)->forceFill(['id' => 9000 + count($this->test->draftedOrders), 'order_no' => $orderNumber]);
            }
        });
    }

    /**
     * Orders-un LinkedOrderCancellation kontraktının saxtası: yalnız `fakeOrderStates`-i
     * oxuyur, heç nəyi ləğv etmir (real ləğv Orders axınındadır).
     */
    protected function erFakeLinkedOrders(): void
    {
        $test = $this;

        $this->app->instance(LinkedOrderCancellation::class, new class($test) implements LinkedOrderCancellation
        {
            public function __construct(private readonly object $test) {}

            public function stateOf(int $orderId): string
            {
                return $this->test->fakeOrderStates[$orderId] ?? self::STATE_PENDING;
            }

            public function isVoided(string $state): bool
            {
                return in_array($state, [self::STATE_CANCELLED, self::STATE_DELETED, self::STATE_MISSING], true);
            }

            public function orderNumber(int $orderId): ?string
            {
                return 'EMR-'.$orderId;
            }

            public function orderUrl(int $orderId, User $user): ?string
            {
                return '/orders?search[order_no]=EMR-'.$orderId;
            }

            public function cancellationOrderUrl(User $user): ?string
            {
                return '/orders?create=1&preset=emrin_legvi';
            }

            public function userMayCancel(User $user, int $orderId): bool
            {
                return true;
            }
        });
    }

    /**
     * Saxta məzuniyyət mənbəyi: verilən tabel nömrəsi üçün [from, to] günləri.
     */
    protected function erFakeVacation(string $tabelNo, string $from, string $to): void
    {
        $source = new class($tabelNo, $from, $to) implements AbsenceSource
        {
            public function __construct(private readonly string $tabelNo, private readonly string $from, private readonly string $to) {}

            public function overlapping(string $tabelNo, CarbonImmutable $from, CarbonImmutable $to): array
            {
                if ($tabelNo !== $this->tabelNo) {
                    return [];
                }

                $start = CarbonImmutable::parse($this->from);
                $end = CarbonImmutable::parse($this->to);

                if ($start->gt($to) || $end->lt($from)) {
                    return [];
                }

                return [AbsencePeriod::days(AbsencePeriod::TYPE_VACATION, 1, $start, $end)];
            }
        };

        $this->app->instance('er-test-vacation-source', $source);
        $this->app->tag(['er-test-vacation-source'], AbsenceSource::TAG);
    }
}
