<?php

namespace Tests\Unit\Services;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPersonnelLinkResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_explicit_link_after_name_based_resolution(): void
    {
        Position::query()->create([
            'id' => 1600,
            'name' => 'Analyst',
        ]);

        $user = User::factory()->create([
            'name' => 'Togrul Calalli',
            'email' => 'togrul@example.com',
        ]);
        Role::findOrCreate('admin', 'web');
        Permission::findOrCreate('get-notification', 'web');

        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'DMX',
            'shortname' => 'DMX',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);

        $personnel = Personnel::query()->create([
            'name' => 'Togrul',
            'surname' => 'Calalli',
            'patronymic' => 'Test',
            'email' => 'different@example.com',
            'tabel_no' => 'UT-RESOLVE-001',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'phone' => '0121111111',
            'mobile' => '0501111111',
            'nationality_id' => 1,
            'pin' => 'XYZ1234',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1600,
            'work_norm_id' => 1,
            'added_by' => $user->id,
            'is_pending' => false,
            'join_work_date' => now()->toDateString(),
        ]);

        $resolvedId = app(UserPersonnelLinkResolver::class)->resolve($user);

        $this->assertSame($personnel->id, $resolvedId);
        $this->assertDatabaseHas('user_personnel_links', [
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'resolution_source' => 'name_match',
        ]);

        $this->assertSame(
            $personnel->id,
            UserPersonnelLink::query()->where('user_id', $user->id)->value('personnel_id')
        );
    }
}
