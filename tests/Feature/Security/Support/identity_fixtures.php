<?php

use App\Models\Personnel;
use App\Models\Structure;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

/*
 * Identity*Test faylları üçün ortaq qurğu (Pest funksiyaları qlobal olduğundan bir dəfə yüklənir).
 */

function identityReferenceData(): void
{
    DB::table('countries')->insertOrIgnore(['id' => 1, 'code' => 'AZ']);
    DB::table('education_degrees')->insertOrIgnore(['id' => 1, 'title_az' => 'Bakalavr']);
    DB::table('work_norms')->insertOrIgnore(['id' => 1, 'name_az' => 'Tam ştat']);
    Structure::query()->firstOrCreate(['id' => 1], ['name' => 'İR', 'shortname' => 'IR', 'code' => 1, 'level' => 1]);
    DB::table('positions')->insertOrIgnore(['id' => 1, 'name' => 'Məsləhətçi']);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function identityPersonnel(string $tabelNo, array $attributes = []): Personnel
{
    identityReferenceData();

    return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate(array_merge([
        'tabel_no' => $tabelNo,
        'surname' => 'Soyad'.$tabelNo,
        'name' => 'Ad',
        'patronymic' => 'Ata',
        'birthdate' => '1990-01-01',
        'gender' => 1,
        'mobile' => '0501112233',
        'email' => strtolower($tabelNo).'@example.test',
        'nationality_id' => 1,
        'pin' => strtoupper(substr(md5($tabelNo), 0, 7)),
        'residental_address' => 'Bakı',
        'education_degree_id' => 1,
        'structure_id' => 1,
        'position_id' => 1,
        'work_norm_id' => 1,
        'join_work_date' => '2021-01-01',
        'added_by' => 1,
        'is_pending' => false,
    ], $attributes)));
}

/**
 * @param  list<string>  $permissions
 * @param  array<string, mixed>  $attributes
 */
function identityUser(array $permissions = [], array $attributes = []): User
{
    $user = User::factory()->create(array_merge(['is_active' => true], $attributes));

    foreach ($permissions as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }

    return $user->fresh();
}

function identityLink(User $user, Personnel $personnel): void
{
    UserPersonnelLink::query()->updateOrCreate(
        ['user_id' => $user->id],
        ['personnel_id' => $personnel->id, 'resolution_source' => 'manual', 'resolved_at' => now()],
    );

    app(UserPersonnelLinkResolver::class)->forget((int) $user->id);
}
