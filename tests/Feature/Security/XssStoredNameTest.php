<?php

use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Notifications\Support\NotificationTemplateRenderer;
use App\Modules\Personnel\Livewire\VacationList;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Saxlanılan XSS: işçinin soyadına yazılmış HTML heç bir başlıqda xam (unescaped) çap olunmamalıdır.
 */
function xssPayload(): string
{
    return '<img src=x onerror=alert(1)>';
}

function xssPersonnel(): Personnel
{
    $structure = Structure::query()->create(['name' => 'XSS bölmə', 'shortname' => 'X']);
    $position = Position::query()->create(['id' => random_int(1000, 999999), 'name' => 'mütəxəssis']);

    return Personnel::withoutEvents(fn () => Personnel::query()->create([
        'tabel_no' => 'XS'.Str::upper(Str::random(6)),
        'surname' => xssPayload(),
        'name' => 'Nicat',
        'patronymic' => 'Elman',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'email' => Str::lower(Str::random(8)).'@example.com',
        'mobile' => '994501112233',
        'nationality_id' => 1,
        'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
        'residental_address' => 'Main st',
        'education_degree_id' => 1,
        'work_norm_id' => 1,
        'structure_id' => $structure->id,
        'position_id' => $position->id,
        'join_work_date' => '2024-03-15',
        'added_by' => 1,
        'is_pending' => false,
    ]));
}

it('escapes the employee name in the vacation list title', function (): void {
    $this->actingAs(grantAllStructures(User::factory()->create())->givePermissionTo(
        Permission::findOrCreate('edit-personnels', 'web'),
    ));
    $personnel = xssPersonnel();

    Livewire::test(VacationList::class, ['personnelModel' => $personnel->tabel_no])
        ->assertDontSeeHtml(xssPayload())
        ->assertSeeHtml(e(xssPayload()));
});

it('keeps blade titles built from model data free of raw markup', function (): void {
    // Başlığı PHP-də HTML ilə qurub {!! !!} ilə çap edən yerlərdə ad mütləq e() ilə keçməlidir.
    foreach ([
        app_path('Modules/Personnel/Livewire/VacationList.php'),
        app_path('Modules/Candidates/Support/Traits/CandidateCrud.php'),
    ] as $file) {
        expect(file_get_contents($file))->not->toMatch('/<span[^>]*>\{\$this->[a-zA-Z]+->fullname\}/');
    }
});

it('escapes payload values in html notification templates only', function (): void {
    $renderer = app(NotificationTemplateRenderer::class);
    $payload = ['personnel' => ['fullname' => xssPayload()]];

    expect($renderer->render('<p>Salam, {{ personnel.fullname }}</p>', $payload, true))
        ->toBe('<p>Salam, '.e(xssPayload()).'</p>')
        ->and($renderer->render('Salam, {{ personnel.fullname }}', $payload))
        ->toBe('Salam, '.xssPayload());
});
