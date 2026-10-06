<?php

namespace Tests\Feature\Vacation;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\Vacation;
use App\Services\Vacation\VacationBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VacationBalancePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_shows_the_balance_without_creating_the_years_row(): void
    {
        DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
        DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
        Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
        Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);
        $person = Personnel::withoutEvents(fn () => Personnel::factory()->create([
            'tabel_no' => 'VB-1', 'surname' => 'Test', 'name' => 'Person', 'patronymic' => 'Oglu',
            'birthdate' => '1990-01-01', 'gender' => 1, 'email' => 'vb-1@example.test', 'mobile' => '0500000000',
            'nationality_id' => 1, 'pin' => 'PINVB1', 'residental_address' => 'Baku', 'education_degree_id' => 1,
            'structure_id' => 1, 'position_id' => 1, 'work_norm_id' => 1, 'join_work_date' => '2020-01-05', 'added_by' => 1,
        ]));
        $balances = app(VacationBalanceService::class);
        $year = (int) now()->year;

        $preview = $balances->previewSnapshot($person, $year);

        $this->assertSame(0, Vacation::query()->count(), 'Displaying the balance must not write it.');
        $this->assertSame($balances->snapshot($person, $year), $preview);
        $this->assertSame(1, Vacation::query()->count());
    }
}
