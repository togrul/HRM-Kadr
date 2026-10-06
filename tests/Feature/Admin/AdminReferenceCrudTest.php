<?php

use App\Models\User;
use App\Modules\Admin\Livewire;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire as LivewireTest;
use Spatie\Permission\Models\Permission;

/*
 * Every admin reference list: renders, creates, edits and deletes a row for an admin,
 * and refuses every mutator (store, delete) with 403 for a user without access-admin.
 *
 * Dataset: [component, create form, edit form, table, where-clause matching the created
 * row, where-clause matching the edited row, id column, optional seed, delete method].
 */

function referenceCrudAdmin(): User
{
    return User::factory()->create()->givePermissionTo(Permission::findOrCreate('access-admin', 'web'));
}

function referenceRowId(string $table, array $where, string $idColumn): int|string|null
{
    return DB::table($table)->where($where)->value($idColumn);
}

dataset('reference components', function (): array {
    $idName = fn (string $class, string $table): array => [
        $class, ['id' => 901, 'name' => 'Yeni qeyd'], ['name' => 'Düzəliş'], $table,
        ['id' => 901, 'name' => 'Yeni qeyd'], ['id' => 901, 'name' => 'Düzəliş'],
    ];
    $locale = fn (string $class, string $table, string $prefix): array => [
        $class, ['id' => 902, "{$prefix}_az" => 'Yeni', "{$prefix}_en" => 'New'], ["{$prefix}_az" => 'Düzəliş'], $table,
        ['id' => 902, "{$prefix}_en" => 'New'], ['id' => 902, "{$prefix}_az" => 'Düzəliş'],
    ];
    $seedAwardType = fn () => DB::table('award_types')->insert(['id' => 1, 'name' => 'Medal']);
    $seedPunishmentType = fn () => DB::table('punishment_types')->insert(['id' => 1, 'name' => 'Töhmət']);
    $seedCountry = function (): void {
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('country_translations')->insert(['country_id' => 1, 'locale' => config('app.locale'), 'title' => 'Azərbaycan']);
    };

    return [
        'document types' => $idName(Livewire\DocumentTypes::class, 'education_document_types'),
        'education types' => $idName(Livewire\EducationTypes::class, 'education_types'),
        'languages' => $idName(Livewire\Languages::class, 'languages'),
        'rank reasons' => $idName(Livewire\RankReasons::class, 'rank_reasons'),
        'scientific degrees' => $idName(Livewire\ScientificDegrees::class, 'scientific_degree_and_names'),
        'social origins' => $idName(Livewire\SocialOrigins::class, 'social_origins'),
        'education degrees' => $locale(Livewire\EducationDegrees::class, 'education_degrees', 'title'),
        'education forms' => $locale(Livewire\EducationForms::class, 'education_forms', 'name'),
        'order categories' => $locale(Livewire\OrderCategories::class, 'order_categories', 'name'),
        'work norms' => $locale(Livewire\WorkNorms::class, 'work_norms', 'name'),
        'kinships' => [
            Livewire\Kinships::class, ['id' => 903, 'name_az' => 'Qardaş', 'is_active' => true], ['name_az' => 'Bacı', 'is_active' => false], 'kinships',
            ['id' => 903, 'name_az' => 'Qardaş', 'is_active' => true], ['id' => 903, 'name_az' => 'Bacı', 'is_active' => false],
        ],
        'educational institutions' => [
            Livewire\EducationalInstitutions::class, ['id' => 904, 'name' => 'Bakı Dövlət Universiteti', 'shortname' => 'BDU', 'old_name_1' => 'ADU'], ['shortname' => 'BSU'],
            'educational_institutions', ['id' => 904, 'old_name_1' => 'ADU'], ['id' => 904, 'shortname' => 'BSU'],
        ],
        'rank categories' => [
            Livewire\RankCategories::class, ['id' => 905, 'name' => 'Zabit', 'vacation_days_count' => 30, 'contract_duration' => 60, 'next_contract_duration' => 120, 'vacation_days_per_month' => 2.5],
            ['contract_duration' => 36], 'rank_categories',
            ['id' => 905, 'name' => 'Zabit', 'next_contract_duration' => 120], ['id' => 905, 'contract_duration' => 36],
        ],
        'weapons' => [
            Livewire\Weapons::class, ['name' => 'AK-74', 'serial_number' => 'SN-1', 'capacity' => 30, 'production_year' => 2001], ['serial_number' => 'SN-2'], 'weapons',
            ['name' => 'AK-74', 'serial_number' => 'SN-1'], ['name' => 'AK-74', 'serial_number' => 'SN-2'],
        ],
        'positions' => [
            Livewire\Positions::class, ['id' => 906, 'name' => 'Rəis', 'approval_rank' => 5], ['name' => 'Baş rəis'], 'positions',
            ['id' => 906, 'name' => 'Rəis'], ['id' => 906, 'name' => 'Baş rəis'],
        ],
        'leave types' => [
            Livewire\LeaveTypes::class, ['name' => 'Saatlıq icazə', 'max_days' => 3], ['max_days' => 5], 'leave_types',
            ['name' => 'Saatlıq icazə', 'max_days' => 3], ['name' => 'Saatlıq icazə', 'max_days' => 5],
        ],
        'order statuses' => [
            Livewire\OrderStatuses::class, ['id' => 907, 'name' => 'Qaralama'], ['name' => 'Təsdiqlənib'], 'order_statuses',
            ['id' => 907, 'name' => 'Qaralama'], ['id' => 907, 'name' => 'Təsdiqlənib'],
        ],
        'appeal statuses' => [
            Livewire\AppealStatus::class, ['id' => 908, 'name' => 'Baxılır'], ['name' => 'Cavablandı'], 'appeal_statuses',
            ['id' => 908, 'name' => 'Baxılır'], ['id' => 908, 'name' => 'Cavablandı'],
        ],
        'awards' => [
            Livewire\Awards::class, ['id' => 909, 'name' => 'Şərəf ordeni', 'award_type_id' => 1], ['name' => 'Şöhrət ordeni'], 'awards',
            ['id' => 909, 'name' => 'Şərəf ordeni'], ['id' => 909, 'name' => 'Şöhrət ordeni'], 'id', $seedAwardType,
        ],
        'punishments' => [
            Livewire\Punishments::class, ['id' => 910, 'name' => 'Xəbərdarlıq', 'punishment_type_id' => 1], ['name' => 'Töhmət'], 'punishments',
            ['id' => 910, 'name' => 'Xəbərdarlıq'], ['id' => 910, 'name' => 'Töhmət'], 'id', $seedPunishmentType,
        ],
        'cities' => [
            Livewire\Cities::class, ['name' => 'Gəncə', 'country_id' => 1], ['name' => 'Şəki'], 'cities',
            ['name' => 'Gəncə', 'country_id' => 1], ['name' => 'Şəki', 'country_id' => 1], 'id', $seedCountry,
        ],
        'countries' => [
            Livewire\Countries::class, ['code' => 'GE', 'country_translations' => ['title' => 'Gürcüstan']], ['code' => 'GG', 'country_translations.title' => 'Sakartvelo'],
            'countries', ['code' => 'GE'], ['code' => 'GG'],
        ],
        'structures' => [
            Livewire\Structures::class, ['name' => 'Kadrlar idarəsi', 'shortname' => 'KI'], ['shortname' => 'KİD'], 'structures',
            ['name' => 'Kadrlar idarəsi', 'shortname' => 'KI'], ['name' => 'Kadrlar idarəsi', 'shortname' => 'KİD'], 'id', null, 'performDelete',
        ],
    ];
});

it('renders, creates, edits and deletes a row', function (
    string $component,
    array $create,
    array $edit,
    string $table,
    array $created,
    array $edited,
    string $idColumn = 'id',
    ?Closure $seed = null,
    string $deleteMethod = 'delete',
): void {
    $seed?->__invoke();
    $admin = referenceCrudAdmin();

    $test = LivewireTest::actingAs($admin)->test($component)->assertOk()->call('openCrud');
    foreach ($create as $field => $value) {
        $test->set("form.{$field}", $value);
    }
    $test->call('store')->assertHasNoErrors()->assertDispatched('notify', type: 'success');

    $id = referenceRowId($table, $created, $idColumn);
    expect($id)->not->toBeNull();

    $shown = Arr::first(Arr::flatten($create), fn (mixed $value): bool => is_string($value));
    $test = LivewireTest::actingAs($admin)->test($component)->assertSee($shown)->call('openCrud', $id);
    foreach ($edit as $field => $value) {
        $test->set("form.{$field}", $value);
    }
    $test->call('store')->assertHasNoErrors();

    expect(referenceRowId($table, $edited, $idColumn))->toEqual($id);

    LivewireTest::actingAs($admin)->test($component)
        ->call('deleteModel', $id)
        ->call($deleteMethod);

    expect(DB::table($table)->where($idColumn, $id)->exists())->toBeFalse();
})->with('reference components');

it('forbids saving and deleting without access-admin', function (
    string $component,
    array $create,
    array $edit,
    string $table,
    array $created,
    array $edited,
    string $idColumn = 'id',
    ?Closure $seed = null,
    string $deleteMethod = 'delete',
): void {
    $seed?->__invoke();

    $test = LivewireTest::actingAs(referenceCrudAdmin())->test($component)->call('openCrud');
    foreach ($create as $field => $value) {
        $test->set("form.{$field}", $value);
    }
    $test->call('store');
    $id = referenceRowId($table, $created, $idColumn);

    $outsider = User::factory()->create();

    $test = LivewireTest::actingAs($outsider)->test($component)->call('openCrud', $id);
    foreach ($edit as $field => $value) {
        $test->set("form.{$field}", $value);
    }
    $test->call('store')->assertForbidden();

    LivewireTest::actingAs($outsider)->test($component)
        ->call('deleteModel', $id)
        ->call($deleteMethod)
        ->assertForbidden();

    expect(referenceRowId($table, $created, $idColumn))->toEqual($id);
})->with('reference components');

it('renders, creates, edits and deletes a type from its child panel', function (string $component, string $table): void {
    $admin = referenceCrudAdmin();

    LivewireTest::actingAs($admin)->test($component)
        ->assertSeeHtml('childForm.name')
        ->set('childForm.id', 911)
        ->set('childForm.name', 'Birinci')
        ->call('store')
        ->assertHasNoErrors()
        ->assertDispatched('close-child');

    LivewireTest::actingAs($admin)->test($component, ['model' => 911])
        ->assertSet('childForm.name', 'Birinci')
        ->set('childForm.name', 'İkinci')
        ->call('store')
        ->assertHasNoErrors();

    expect(DB::table($table)->where('id', 911)->value('name'))->toBe('İkinci');

    $outsider = User::factory()->create();
    LivewireTest::actingAs($outsider)->test($component, ['model' => 911])->call('store')->assertForbidden();
    LivewireTest::actingAs($outsider)->test($component, ['model' => 911])->call('delete')->assertForbidden();

    LivewireTest::actingAs($admin)->test($component, ['model' => 911])
        ->call('deleteModel')
        ->assertDispatched('confirm-action')
        ->call('delete')
        ->assertDispatched('notify', type: 'success');

    expect(DB::table($table)->where('id', 911)->exists())->toBeFalse();
})->with([
    'award types' => [Livewire\AwardTypes::class, 'award_types'],
    'punishment types' => [Livewire\PunishmentTypes::class, 'punishment_types'],
]);
