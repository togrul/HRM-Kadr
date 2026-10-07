<?php

namespace Tests\Feature\Console;

use App\Models\Country;
use App\Models\CountryTranslation;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportPersonnelFromXlsxCommandTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = ['Ad', 'Soyad', 'Ata adı', 'Vətəndaşlıq', 'Cinsi', 'Doğum tarixi', 'Vəzifəsi', 'Struktur', 'Mobil', 'FİN', 'Yaşayış ünvanı', 'Qeydiyyat ünvanı', 'Təhsil dərəcəsi', 'İşə başlama tarixi', 'Əməyin ödənilməsi', 'Tabel #'];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://acme.example.az');
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');
        User::factory()->create();
        Country::query()->create(['id' => 1, 'code' => 'AZ']);
        CountryTranslation::query()->create(['country_id' => 1, 'locale' => 'az', 'title' => 'Azərbaycan']);
        EducationDegree::query()->create(['id' => 100, 'title_az' => 'ali']);
        WorkNorm::query()->create(['id' => 10, 'name_az' => 'ştat']);
        Structure::query()->create(['id' => 1, 'name' => 'ACME MMC', 'shortname' => 'ACME', 'code' => 1, 'level' => 0]);
    }

    public function test_it_imports_valid_rows_idempotently_and_fills_the_staff_schedule(): void
    {
        $file = $this->xlsx([
            ['Samid', 'Nəcəfli', 'Maarif oğlu', 'Azərbaycan', 'Kişi', 33671, 'Direktor', 'Rəhbərlik', '+994502122724', '5abc12d', 'Bakı, Nəsimi', 'Bakı', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 1],
            ['Bənövşə', 'Əliyeva', 'Aqil qızı', 'Azərbaycan', 'Qadın', 32827, 'Şöbə rəisi', 'İnsan resursları', '+994514004944', '6XYZ34K', 'Bakı, Yasamal', 'Bakı', 'Ali təhsil - bakalavriat', '03.07.2026', 'Vaxtamuzd', 2],
        ]);

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'ACME.example.az', '--parent' => 1, '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(2, Personnel::query()->count());
        $this->assertDatabaseHas('personnels', ['tabel_no' => '1', 'pin' => '5ABC12D', 'gender' => 1, 'birthdate' => '1992-03-08', 'education_degree_id' => 100, 'is_pending' => false]);
        $this->assertDatabaseHas('personnels', ['tabel_no' => '2', 'gender' => 2, 'join_work_date' => '2026-07-03']);
        $this->assertNotNull(Personnel::query()->where('tabel_no', '1')->value('person_uid'));
        $this->assertDatabaseHas('structures', ['name' => 'Rəhbərlik', 'parent_id' => 1, 'level' => 1]);
        $this->assertSame([1, 1], StaffSchedule::query()->where('structure_id', '!=', 1)->orderBy('id')->pluck('filled')->map(fn ($v): int => (int) $v)->all());

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'acme.example.az', '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(2, Personnel::query()->count());
    }

    public function test_it_refuses_the_wrong_install_and_fills_placeholders_for_missing_fin_and_address(): void
    {
        $file = $this->xlsx([
            ['A', 'B', 'C', 'Azərbaycan', 'Kişi', 33671, 'Sürücü', 'Rəhbərlik', '+994500000000', '1122334', 'X', 'Y', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 7],
            ['D', 'E', 'F', 'Azərbaycan', 'Kişi', 33671, 'Sürücü', 'Rəhbərlik', '+994500000001', '1122334', 'X', 'Y', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 8],
        ]);

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'other.example.az', '--apply' => true, '--force' => true])
            ->assertFailed();
        $this->assertSame(0, Personnel::query()->count());

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'acme.example.az', '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('personnels', ['tabel_no' => '7', 'pin' => 'Z000007', 'residental_address' => 'Məlum deyil', 'registered_address' => null]);
        $this->assertDatabaseHas('personnels', ['tabel_no' => '8', 'pin' => 'Z000008']);
    }

    public function test_a_tabel_number_held_by_someone_else_blocks_the_import(): void
    {
        $file = $this->xlsx([
            ['Samid', 'Nəcəfli', 'Maarif oğlu', 'Azərbaycan', 'Kişi', 33671, 'Direktor', 'Rəhbərlik', '+994502122724', '5ABC12D', 'Bakı, Nəsimi', 'Bakı', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 1],
        ]);
        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'acme.example.az', '--apply' => true, '--force' => true])->assertSuccessful();
        Personnel::query()->where('tabel_no', '1')->update(['surname' => 'Başqası']);

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'acme.example.az'])
            ->expectsOutputToContain('already belongs to Başqası Samid')
            ->assertFailed();
    }

    public function test_it_reuses_existing_departments_named_with_sobesi_and_explicit_mappings(): void
    {
        $finance = Structure::query()->create(['parent_id' => 1, 'name' => 'Maliyyə şöbəsi', 'shortname' => 'M', 'code' => 1, 'level' => 1]);
        $hr = Structure::query()->create(['parent_id' => 1, 'name' => ' Kadrlarla iş şöbəsi', 'shortname' => 'K', 'code' => 2, 'level' => 1]);
        Position::query()->create(['id' => 5, 'name' => 'İnformasiya texnologiyaları üzrə aparıcı mütəxəssis']);

        $file = $this->xlsx([
            ['A', 'B', 'C', 'Azərbaycan', 'Kişi', 33671, 'Mühasib', 'Maliyyə', '+994500000000', '', 'X', 'Y', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 11],
            ['D', 'E', 'F', 'Azərbaycan', 'Qadın', 33671, 'İnformasiya texnologiyaları üzrə aparıcı mütəxəssis', 'İnsan resursları', '+994500000001', '', 'X', 'Y', 'Ali təhsil - bakalavriat', 45043, 'Vaxtamuzd', 12],
        ]);

        $this->artisan('personnel:import-xlsx', ['file' => $file, '--target' => 'acme.example.az', '--parent' => 1, '--structure' => ['İnsan resursları='.$hr->id], '--apply' => true, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(3, Structure::query()->count());
        $this->assertSame(1, Position::query()->where('name', 'like', 'İnformasiya%')->count());
        $this->assertDatabaseHas('personnels', ['tabel_no' => '11', 'structure_id' => $finance->id]);
        $this->assertDatabaseHas('personnels', ['tabel_no' => '12', 'structure_id' => $hr->id, 'position_id' => 5]);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([self::HEADER, ...$rows]);
        $path = tempnam(sys_get_temp_dir(), 'hrm').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
