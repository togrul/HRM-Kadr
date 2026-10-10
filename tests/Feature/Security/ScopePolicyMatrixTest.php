<?php

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Models\OrderLog;
use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use App\Models\PersonnelVacation;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Modules\BusinessTrips\Policies\BusinessTripPolicy;
use App\Modules\Candidates\Policies\CandidateApplicationPolicy;
use App\Modules\Candidates\Policies\CandidatePolicy;
use App\Modules\Leaves\Policies\LeavePolicy;
use App\Modules\Leaves\Policies\LeaveSickCertificatePolicy;
use App\Modules\Orders\Policies\OrderLogPolicy;
use App\Modules\Personnel\Policies\PersonnelPolicy;
use App\Modules\Vacation\Policies\VacationPolicy;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/**
 * Bütün qeyd səviyyəli policy-lər üçün eyni matris: bütün icazələri olan, amma yalnız
 * 1-ci struktura baxan istifadəçi 2-ci strukturun qeydinə heç bir əməliyyat edə bilməz;
 * struktursuz istifadəçi heç nəyə; «bütün strukturlar» isə hamısına. Yeni record policy
 * əlavə olunanda bu siyahıya da əlavə et.
 */
beforeEach(function (): void {
    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    Structure::query()->firstOrCreate(['id' => 2], ['name' => 'Maliyyə', 'shortname' => 'MAL', 'code' => 2, 'level' => 1]);
    Position::query()->firstOrCreate(['id' => 1], ['name' => 'Məsləhətçi']);

    $this->people = [
        'in' => scopeMatrixPersonnel('PM-IN', 1),
        'out' => scopeMatrixPersonnel('PM-OUT', 2),
    ];
});

function scopeMatrixPersonnel(string $tabelNo, int $structureId): Personnel
{
    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate([
        'tabel_no' => $tabelNo,
        'surname' => 'Matris',
        'name' => $tabelNo,
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

function scopeMatrixOrder(Personnel $personnel): OrderLog
{
    $order = OrderLog::query()->create([
        'order_no' => 'PM-'.$personnel->tabel_no,
        'given_date' => now(),
        'given_by' => 'Test',
        'given_by_rank' => '',
        'status_id' => 10,
        'template_render_mode' => 'docx',
        'template_snapshot' => ['template_code' => 'leave', 'personnel_id' => $personnel->id],
    ]);
    $order->personnels()->attach($personnel->tabel_no);

    return $order;
}

function scopeMatrixSickCertificate(Personnel $personnel): LeaveSickCertificate
{
    $leave = new Leave(['tabel_no' => $personnel->tabel_no]);
    $certificate = new LeaveSickCertificate;
    $certificate->setRelation('leave', $leave);

    return $certificate;
}

function scopeMatrixCandidate(Personnel $personnel): Candidate
{
    return (new Candidate)->forceFill(['id' => $personnel->id, 'structure_id' => $personnel->structure_id]);
}

function scopeMatrixApplication(Personnel $personnel): CandidateApplication
{
    $application = (new CandidateApplication)->forceFill(['candidate_id' => $personnel->id, 'job_opening_id' => null]);
    $application->setRelation('candidate', scopeMatrixCandidate($personnel));

    return $application;
}

/**
 * Hər policy üçün: [policy, qeyd yaradan funksiya, yoxlanılan qabiliyyətlər].
 *
 * @return array<string, array{0: class-string, 1: Closure(Personnel): object, 2: list<string>}>
 */
function scopeMatrixCases(): array
{
    return [
        'personnel' => [PersonnelPolicy::class, fn (Personnel $p): Personnel => $p, ['view', 'update', 'delete', 'restore', 'forceDelete']],
        'vacation' => [VacationPolicy::class, fn (Personnel $p): PersonnelVacation => new PersonnelVacation(['tabel_no' => $p->tabel_no]), ['view', 'update', 'delete', 'restore', 'forceDelete']],
        'business trip' => [BusinessTripPolicy::class, fn (Personnel $p): PersonnelBusinessTrip => new PersonnelBusinessTrip(['tabel_no' => $p->tabel_no]), ['view', 'update', 'delete', 'restore', 'forceDelete']],
        'leave' => [LeavePolicy::class, fn (Personnel $p): Leave => new Leave(['tabel_no' => $p->tabel_no]), ['view', 'update', 'delete', 'restore', 'forceDelete']],
        'sick certificate' => [LeaveSickCertificatePolicy::class, fn (Personnel $p): LeaveSickCertificate => scopeMatrixSickCertificate($p), ['view']],
        'order' => [OrderLogPolicy::class, fn (Personnel $p): OrderLog => scopeMatrixOrder($p), ['view', 'update', 'delete', 'restore', 'forceDelete', 'revert']],
        // Namizəd hələ işçi deyil — onun (və müraciətin) strukturu işçinin strukturundan götürülür.
        'candidate' => [CandidatePolicy::class, fn (Personnel $p): Candidate => scopeMatrixCandidate($p), ['view', 'update', 'delete', 'restore', 'forceDelete']],
        'candidate application' => [CandidateApplicationPolicy::class, fn (Personnel $p): CandidateApplication => scopeMatrixApplication($p), ['view', 'transition', 'reject', 'appoint']],
    ];
}

function scopeMatrixUser(): User
{
    $user = User::factory()->create();

    foreach ([
        'show-personnels', 'edit-personnels', 'delete-personnels',
        'show-vacations', 'edit-vacations', 'delete-vacations',
        'show-business_trips', 'edit-business_trips', 'delete-business_trips',
        'show-leaves', 'edit-leaves', 'delete-leaves', 'show-sick-certificates', 'manage-sick-certificates',
        'show-orders', 'add-orders', 'edit-orders', 'delete-orders', 'revert-orders', 'export-orders',
        'show-candidates', 'add-candidates', 'edit-candidates', 'delete-candidates',
    ] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user;
}

it('denies every record ability outside the structure scope', function (string $case): void {
    [$policyClass, $make, $abilities] = scopeMatrixCases()[$case];
    $policy = app($policyClass);

    $limited = grantStructures(scopeMatrixUser(), [1]);
    $none = scopeMatrixUser();
    $all = grantAllStructures(scopeMatrixUser());

    $inside = $make($this->people['in']);
    $outside = $make($this->people['out']);

    foreach ($abilities as $ability) {
        expect($policy->{$ability}($limited, $outside))->toBeFalse("{$case}: limited {$ability} outside")
            ->and($policy->{$ability}($none, $inside))->toBeFalse("{$case}: no-structure {$ability}");
    }

    expect($policy->view($limited, $inside))->toBeTrue("{$case}: limited view inside")
        ->and($policy->view($all, $outside))->toBeTrue("{$case}: all-structures view outside");
})->with(array_keys(scopeMatrixCases()));
