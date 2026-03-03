<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatusEnum;
use App\Models\AppealStatus;
use App\Models\Candidate;
use App\Models\Component;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Order;
use App\Models\OrderCategory;
use App\Models\OrderLog;
use App\Models\OrderLogComponentAttributes;
use App\Models\OrderStatus;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Services\OrderConfirmedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderConfirmedServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_employment_order_and_creates_related_records(): void
    {
        $ctx = $this->buildEmploymentContext();

        (new OrderConfirmedService($ctx['orderLog']))->handle([
            ['id' => $ctx['candidate']->id],
        ], 'create');

        $ctx['candidate']->refresh();
        $ctx['personnel']->refresh();

        $this->assertSame(70, (int) $ctx['candidate']->status_id);
        $this->assertFalse((bool) $ctx['personnel']->is_pending);
        $this->assertSame('2026-03-05', (string) optional($ctx['personnel']->join_work_date)->format('Y-m-d'));

        $this->assertDatabaseHas('personnel_ranks', [
            'tabel_no' => $ctx['personnel']->tabel_no,
            'rank_id' => 10,
            'order_no' => $ctx['orderLog']->order_no,
        ]);
        $labor = DB::table('personnel_labor_activities')
            ->where('tabel_no', $ctx['personnel']->tabel_no)
            ->where('is_current', 1)
            ->where('position', 'Programmer')
            ->first();
        $this->assertNotNull($labor);
        $this->assertSame('2026-03-05', Carbon::parse((string) $labor->join_date)->format('Y-m-d'));
    }

    public function test_it_falls_back_to_attached_pending_personnels_on_update_when_payload_is_empty(): void
    {
        $ctx = $this->buildEmploymentContext(orderNo: 'IG-2026-2002');

        (new OrderConfirmedService($ctx['orderLog']))->handle([], 'update');

        $ctx['candidate']->refresh();
        $ctx['personnel']->refresh();

        $this->assertSame(70, (int) $ctx['candidate']->status_id);
        $this->assertFalse((bool) $ctx['personnel']->is_pending);
        $this->assertDatabaseHas('personnel_labor_activities', [
            'tabel_no' => $ctx['personnel']->tabel_no,
            'is_current' => 1,
        ]);
    }

    /**
     * @return array{candidate:Candidate,personnel:Personnel,orderLog:OrderLog}
     */
    private function buildEmploymentContext(
        string $orderNo = 'IG-2026-1001'
    ): array {
        $user = User::factory()->create();

        Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);
        EducationDegree::query()->create([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bakalavr',
        ]);
        WorkNorm::query()->create([
            'id' => 1,
            'name_az' => 'Tam',
            'name_en' => 'Full',
            'name_ru' => 'Polniy',
        ]);
        Rank::query()->create([
            'id' => 10,
            'name_az' => 'Leytenant',
            'name_en' => 'Lieutenant',
            'name_ru' => 'Leytenant',
            'is_active' => true,
        ]);

        $structure = Structure::query()->create([
            'name' => 'Technical Department',
            'shortname' => 'TECH',
            'parent_id' => null,
            'coefficient' => 1.25,
            'code' => 18,
            'level' => 2,
        ]);
        $position = Position::query()->create([
            'id' => 100,
            'name' => 'Programmer',
        ]);

        AppealStatus::query()->create([
            'id' => 30,
            'locale' => 'az',
            'name' => 'Hazir',
        ]);
        AppealStatus::query()->create([
            'id' => 70,
            'locale' => 'az',
            'name' => 'Qebul',
        ]);

        $candidate = Candidate::query()->create([
            'surname' => 'Calalli',
            'name' => 'Togrul',
            'patronymic' => 'Ismayil',
            'structure_id' => $structure->id,
            'height' => 180,
            'status_id' => 30,
            'creator_id' => $user->id,
        ]);
        $tabelNo = 'NMZD'.$candidate->id;

        $personnel = Personnel::withoutEvents(function () use ($user, $structure, $position, $tabelNo) {
            return Personnel::query()->create([
                'tabel_no' => $tabelNo,
                'surname' => 'Calalli',
                'name' => 'Togrul',
                'patronymic' => 'Ismayil',
                'birthdate' => '1990-01-01',
                'gender' => 1,
                'mobile' => '994501112233',
                'nationality_id' => 1,
                'pin' => 'P1234567',
                'residental_address' => 'Main st',
                'education_degree_id' => 1,
                'structure_id' => $structure->id,
                'position_id' => $position->id,
                'work_norm_id' => 1,
                'join_work_date' => '2026-01-01',
                'added_by' => $user->id,
                'is_pending' => true,
            ]);
        });

        OrderCategory::query()->create([
            'id' => 7100,
            'name_az' => 'Kateqoriya',
            'name_en' => 'Category',
            'name_ru' => 'Kategoriya',
        ]);
        app(\App\Services\Orders\TemplateAdminService::class)->create([
            'id' => Order::IG_EMR,
            'order_category_id' => 7100,
            'name' => 'İşə qəbul',
            'content' => 'templates/ise-qebul.docx',
            'order_model' => '\\App\\Models\\Personnel',
            'blade' => Order::BLADE_DEFAULT,
        ]);
        $orderType = OrderType::query()->create([
            'order_id' => Order::IG_EMR,
            'name' => 'İşə qəbul',
        ]);
        OrderStatus::query()->create([
            'id' => OrderStatusEnum::APPROVED->value,
            'locale' => 'az',
            'name' => 'Təsdiqlənib',
        ]);

        $orderLog = OrderLog::query()->create([
            'order_id' => Order::IG_EMR,
            'order_type_id' => $orderType->id,
            'order_no' => $orderNo,
            'given_date' => '2026-03-01 10:00:00',
            'given_by' => 'Ferid Əsgərov',
            'given_by_rank' => 'general-mayor',
            'status_id' => OrderStatusEnum::APPROVED->value,
            'creator_id' => $user->id,
        ]);

        $component = $this->createComponentForType(orderId: Order::IG_EMR, orderTypeId: (int) $orderType->id);

        DB::table('order_log_personnels')->insert([
            'order_no' => $orderLog->order_no,
            'component_id' => $component->id,
            'tabel_no' => $personnel->tabel_no,
        ]);

        OrderLogComponentAttributes::query()->create([
            'order_no' => $orderLog->order_no,
            'component_id' => $component->id,
            'attributes' => [
                '$day' => ['value' => '5'],
                '$month' => ['value' => 'mart'],
                '$year' => ['value' => '2026'],
                '$rank' => ['id' => 10, 'value' => 'Leytenant'],
            ],
            'row_number' => 1,
        ]);

        return [
            'candidate' => $candidate,
            'personnel' => $personnel,
            'orderLog' => $orderLog,
        ];
    }

    private function createComponentForType(int $orderId, int $orderTypeId): Component
    {
        $payload = [
            'name' => 'İşə qəbul komponenti',
            'title' => 'İşə qəbul komponenti',
            'content' => '$fullname',
            'dynamic_fields' => [],
        ];

        if (Schema::hasColumn('components', 'order_type_id')) {
            $payload['order_type_id'] = $orderTypeId;
        } else {
            $payload['order_id'] = $orderId;
        }

        return Component::query()->forceCreate($payload);
    }
}
