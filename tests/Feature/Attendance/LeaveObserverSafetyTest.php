<?php

namespace Tests\Feature\Attendance;

use App\Enums\OrderStatusEnum;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Leave;
use App\Models\OrderStatus;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class LeaveObserverSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_creation_does_not_require_admin_role_to_exist(): void
    {
        Notification::fake();

        $personnel = $this->makePersonnel();
        $this->seedOrderStatuses();

        $leave = Leave::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'leave_type_id' => null,
            'starts_at' => '2026-03-10',
            'ends_at' => '2026-03-11',
            'status_id' => OrderStatusEnum::PENDING->value,
        ]);

        $this->assertNotNull($leave->id);
    }

    private function seedOrderStatuses(): void
    {
        foreach ([
            [OrderStatusEnum::PENDING->value, 'Pending'],
            [OrderStatusEnum::APPROVED->value, 'Approved'],
            [OrderStatusEnum::CANCELLED->value, 'Cancelled'],
        ] as [$id, $name]) {
            OrderStatus::query()->firstOrCreate([
                'id' => $id,
                'locale' => 'en',
            ], [
                'name' => $name,
            ]);
        }
    }

    private function makePersonnel(): Personnel
    {
        $user = User::query()->first() ?? User::factory()->create();

        $country = Country::query()->first() ?? Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);

        if (! EducationDegree::query()->whereKey(1)->exists()) {
            EducationDegree::query()->create([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bakalavr',
            ]);
        }

        if (! WorkNorm::query()->whereKey(1)->exists()) {
            WorkNorm::query()->create([
                'id' => 1,
                'name_az' => 'Tam',
                'name_en' => 'Full',
                'name_ru' => 'Polniy',
            ]);
        }

        $position = Position::query()->first() ?? Position::query()->create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        $payload = [
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'John',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => $country->id,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ];

        return Personnel::withoutEvents(fn () => Personnel::query()->create($payload));
    }
}
