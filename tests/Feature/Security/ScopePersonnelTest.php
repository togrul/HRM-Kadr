<?php

use App\Models\Personnel;
use App\Models\PersonnelDocument;
use App\Models\Position;
use App\Models\Role;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\TablePanel;
use App\Modules\Personnel\Policies\PersonnelPolicy;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Services\Livewire\Roles\SetPermission;
use App\Modules\Staff\Livewire\Staffs;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Struktur görünürlüyü fail closed-dur: struktur verilməmiş rol heç nə görmür, «bütün
 * strukturlar» bayrağı bütün təşkilatı açır, işçi kartı/çap/fayl isə siyahıda olduğu
 * kimi strukturla məhdudlaşır. Müştərinin göndərdiyi struktur filtri həmişə görünürlüklə
 * kəsişdirilir.
 */
beforeEach(function (): void {
    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    Structure::query()->firstOrCreate(['id' => 2], ['name' => 'Maliyyə', 'shortname' => 'MAL', 'code' => 2, 'level' => 1]);
    Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

    $this->inside = scopePersonnelFixture('SP-IN', 'Görünənov', 1);
    $this->outside = scopePersonnelFixture('SP-OUT', 'Gizlinov', 2);
});

function scopePersonnelFixture(string $tabelNo, string $surname, int $structureId): Personnel
{
    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate([
        'tabel_no' => $tabelNo,
        'surname' => $surname,
        'name' => 'Test',
        'patronymic' => 'Ata',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0501112233',
        'nationality_id' => 1,
        'pin' => strtoupper(substr(md5($tabelNo), 0, 7)),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => $structureId,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2021-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

/**
 * @param  list<string>  $permissions
 */
function scopePersonnelUser(array $permissions = ['show-personnels', 'edit-personnels', 'delete-personnels', 'export-personnels']): User
{
    $user = User::factory()->create();

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

it('fails closed: a role without structures and without the flag sees nothing', function (): void {
    $user = scopePersonnelUser();
    $user->assignRole(Role::query()->create(['name' => 'Boş rol', 'guard_name' => 'web']));

    $scope = app(StructureService::class)->scopeFor($user);

    expect($scope->isNone())->toBeTrue()
        ->and(app(StructureService::class)->getAccessibleStructures($user))->toBe([])
        ->and(Structure::query()->accessible($user)->count())->toBe(0);
});

it('opens the whole organisation for an all-structures role and lists every structure id', function (): void {
    $user = grantAllStructures(scopePersonnelUser());

    expect(app(StructureService::class)->scopeFor($user)->isAll())->toBeTrue()
        ->and(collect(app(StructureService::class)->getAccessibleStructures($user))->sort()->values()->all())->toBe([1, 2])
        ->and(Structure::query()->accessible($user)->count())->toBe(2);
});

it('grants Admin and HR Admin the all-structures flag in the migration', function (): void {
    $migration = require base_path('app/Modules/Services/Database/Migrations/2026_10_10_200000_add_all_structures_flag_to_roles_table.php');

    $admin = Role::findOrCreate('Admin', 'web');
    $hrAdmin = Role::findOrCreate('HR Admin', 'web');
    $other = Role::findOrCreate('HR Employee', 'web');
    Role::query()->whereKey([$admin->id, $hrAdmin->id, $other->id])->update(['all_structures' => false]);

    $migration->up();

    expect((bool) $admin->fresh()->all_structures)->toBeTrue()
        ->and((bool) $hrAdmin->fresh()->all_structures)->toBeTrue()
        ->and((bool) $other->fresh()->all_structures)->toBeFalse();
});

it('invalidates the cached scope when the flag is toggled on the role screen', function (): void {
    $role = Role::query()->create(['name' => 'Struktur rolu', 'guard_name' => 'web']);
    $member = scopePersonnelUser();
    $member->assignRole($role);

    expect(app(StructureService::class)->scopeFor($member)->isNone())->toBeTrue();

    $admin = grantAllStructures(scopePersonnelUser(['access-settings', 'manage-roles']));
    Livewire::actingAs($admin)
        ->test(SetPermission::class, ['roleModel' => $role->id])
        ->set('allStructures', true)
        ->call('store');

    expect((bool) $role->fresh()->all_structures)->toBeTrue()
        ->and(app(StructureService::class)->scopeFor($member)->isAll())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(SetPermission::class, ['roleModel' => $role->id])
        ->set('allStructures', false)
        ->call('store');

    expect(app(StructureService::class)->scopeFor($member)->isNone())->toBeTrue();
});

it('invalidates the cached scope of every role member when structures are synced', function (): void {
    $role = Role::query()->create(['name' => 'Struktur rolu', 'guard_name' => 'web']);
    $member = scopePersonnelUser();
    $member->assignRole($role);

    expect(app(StructureService::class)->scopeFor($member)->ids())->toBe([]);

    Livewire::actingAs(grantAllStructures(scopePersonnelUser(['access-settings', 'manage-roles'])))
        ->test(SetPermission::class, ['roleModel' => $role->id])
        ->set('permissionStructureList', [2])
        ->call('store');

    expect(app(StructureService::class)->scopeFor($member)->ids())->toBe([2]);
});

it('requires the personnel structure in scope for every record ability', function (): void {
    $policy = app(PersonnelPolicy::class);
    $limited = grantStructures(scopePersonnelUser(), [1]);
    $none = scopePersonnelUser();
    $all = grantAllStructures(scopePersonnelUser());

    foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
        expect($policy->{$ability}($limited, $this->inside))->toBeTrue("limited {$ability} inside")
            ->and($policy->{$ability}($limited, $this->outside))->toBeFalse("limited {$ability} outside")
            ->and($policy->{$ability}($none, $this->inside))->toBeFalse("none {$ability}")
            ->and($policy->{$ability}($all, $this->outside))->toBeTrue("all {$ability}");
    }

    $narrow = Personnel::query()->select(['id', 'tabel_no'])->findOrFail($this->outside->id);
    expect($policy->delete($limited, $narrow))->toBeFalse();
});

it('refuses the profile, service-book and CV prints of an out-of-scope employee', function (): void {
    $this->actingAs(grantStructures(scopePersonnelUser(), [1]));

    $this->get(route('personnel.show', $this->outside->id))->assertForbidden();
    $this->get(route('print.personnel', $this->outside->id))->assertForbidden();
    $this->get(route('print.personnel.word', $this->outside->id))->assertForbidden();
    $this->get(route('print.cv', $this->outside->id))->assertForbidden();
    $this->get(route('print.cv.word', $this->outside->id))->assertForbidden();
});

it('refuses to stream a file of an out-of-scope employee', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('personnel-files/x.pdf', '%PDF-1.4');
    $document = PersonnelDocument::query()->create([
        'tabel_no' => $this->outside->tabel_no,
        'file' => 'personnel-files/x.pdf',
        'filename' => 'Diplom',
    ]);

    $this->actingAs(grantStructures(scopePersonnelUser(), [1]));
    $this->get(route('personnel.files.download', $document->id))->assertForbidden();
});

it('streams the same file to an all-structures user', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('personnel-files/x.pdf', '%PDF-1.4');
    $document = PersonnelDocument::query()->create([
        'tabel_no' => $this->outside->tabel_no,
        'file' => 'personnel-files/x.pdf',
        'filename' => 'Diplom',
    ]);

    $this->actingAs(grantAllStructures(scopePersonnelUser()));
    $this->get(route('personnel.files.download', $document->id))->assertOk();
});

it('refuses a limited role manager granting all structures or structures they cannot see', function (): void {
    $role = Role::query()->create(['name' => 'Başqa rol', 'guard_name' => 'web']);
    $manager = grantStructures(scopePersonnelUser(['access-settings', 'manage-roles']), [1]);

    Livewire::actingAs($manager)
        ->test(SetPermission::class, ['roleModel' => $role->id])
        ->set('allStructures', true)
        ->call('store');

    Livewire::actingAs($manager)
        ->test(SetPermission::class, ['roleModel' => $role->id])
        ->set('permissionStructureList', [2])
        ->call('store');

    expect((bool) $role->fresh()->all_structures)->toBeFalse()
        ->and($role->structures()->count())->toBe(0);
});

it('intersects a client structure filter with the scope in the list, counts and export', function (): void {
    $service = app(PersonnelQueryService::class);
    $scope = StructureScope::of([1]);

    $listed = fn (array $selected): array => $service->build('all', [], $selected, $scope)->pluck('personnels.tabel_no')->all();

    expect($listed([]))->toBe(['SP-IN'])
        ->and($listed([2]))->toBe([])
        ->and($listed([1, 2]))->toBe(['SP-IN'])
        ->and($service->statusCounts([], [2], $scope)['all'])->toBe(0)
        ->and($service->buildExport('all', [], [2], $scope)->count())->toBe(0)
        ->and($service->build('all', [], [], StructureScope::none())->count())->toBe(0)
        ->and($service->build('all', [], [2], StructureScope::all())->pluck('personnels.tabel_no')->all())->toBe(['SP-OUT'])
        ->and($service->quickFind('Gizlinov', $scope)->all())->toBe([]);
});

it('ignores an out-of-scope ?structure= filter on the personnel screens', function (): void {
    $user = grantStructures(scopePersonnelUser(), [1]);

    $panel = Livewire::actingAs($user)->test(TablePanel::class, ['status' => 'all', 'structure' => [2]]);
    expect(collect($panel->instance()->personnels->items())->pluck('tabel_no')->all())->toBe([]);

    $list = Livewire::actingAs($user)->withQueryParams(['structure' => [2]])->test(AllPersonnel::class);
    expect($list->instance()->statusCounts()['all'])->toBe(0);

    $list->call('selectStructure', 2);
    expect($list->get('structure'))->toBe([]);
});

it('returns nothing to the palette search outside the scope', function (): void {
    $this->actingAs(grantStructures(scopePersonnelUser(), [1]));

    $this->getJson(route('personnel.palette-search', ['q' => 'Gizlinov']))->assertOk()->assertJsonPath('results', []);
    $this->getJson(route('personnel.palette-search', ['q' => 'Görünənov']))->assertOk()->assertJsonCount(1, 'results');
});

it('keeps the staff schedule tree inside the scope even with a forged structure filter', function (): void {
    Permission::findOrCreate('show-staff', 'web');
    $user = grantStructures(scopePersonnelUser(['show-staff']), [1]);
    DB::table('staff_schedules')->insert([
        ['structure_id' => 1, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1],
        ['structure_id' => 2, 'position_id' => 1, 'total' => 1, 'filled' => 0, 'vacant' => 1],
    ]);

    $component = Livewire::actingAs($user)->test(Staffs::class)->set('structure', [2]);
    $rows = (fn () => $this->staffSnapshot()['rows'])->call($component->instance());

    expect($rows->pluck('structure_id')->all())->toBe([]);
});
