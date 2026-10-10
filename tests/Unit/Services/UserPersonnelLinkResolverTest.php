<?php

namespace Tests\Unit\Services;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\UserPersonnelLinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Resolver yalnız açıq bağı oxuyur: e-poçt və ad-soyad uyğunluğu heç vaxt eyniləşdirmə
 * sayılmır və resolver heç vaxt özü bağ yazmır.
 */
class UserPersonnelLinkResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_matching_email_and_name_never_link_a_user(): void
    {
        $personnel = $this->makePersonnel('Togrul', 'Calalli', 'togrul@example.com');
        $user = User::factory()->create(['name' => 'Togrul Calalli', 'email' => 'togrul@example.com']);

        $this->assertNull(app(UserPersonnelLinkResolver::class)->resolve($user));
        $this->assertNull($user->personnel()->first());
        $this->assertDatabaseCount('user_personnel_links', 0);
        $this->assertNotNull($personnel->id);
    }

    public function test_explicit_link_resolves_and_inactive_personnel_does_not(): void
    {
        $personnel = $this->makePersonnel('Aysel', 'Məmmədova', 'aysel@example.com');
        $user = User::factory()->create(['email' => 'someone-else@example.com']);

        $this->linkUserToPersonnel($user, $personnel);

        $resolver = app(UserPersonnelLinkResolver::class);
        $this->assertSame($personnel->id, $resolver->resolve($user));
        $this->assertSame($personnel->id, $user->personnel()->first()?->id);

        $personnel->forceFill(['leave_work_date' => '2026-01-01'])->saveQuietly();
        $resolver->forget((int) $user->id);

        $this->assertNull($resolver->resolve($user));
    }

    public function test_user_ids_by_personnel_uses_links_only(): void
    {
        $linked = $this->makePersonnel('A', 'B', 'linked@example.com');
        $unlinked = $this->makePersonnel('C', 'D', 'unlinked@example.com');
        $user = User::factory()->create();
        User::factory()->create(['email' => 'unlinked@example.com']);

        UserPersonnelLink::query()->create(['user_id' => $user->id, 'personnel_id' => $linked->id, 'resolution_source' => 'manual']);

        $this->assertSame(
            [$linked->id => $user->id],
            app(UserPersonnelLinkResolver::class)->userIdsByPersonnel([$linked->id, $unlinked->id])
        );
    }

    private function makePersonnel(string $name, string $surname, string $email): Personnel
    {
        foreach ([
            'countries' => ['id' => 1, 'code' => 'AZ'],
            'education_degrees' => ['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor'],
            'structures' => ['id' => 1, 'name' => 'DMX', 'shortname' => 'DMX'],
            'work_norms' => ['id' => 1, 'name_az' => 'Tam', 'name_en' => 'Full', 'name_ru' => 'Full'],
            'positions' => ['id' => 1, 'name' => 'Analyst'],
        ] as $table => $row) {
            if (! DB::table($table)->where('id', 1)->exists()) {
                DB::table($table)->insert($row);
            }
        }

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'T'.random_int(10000, 99999),
            'name' => $name,
            'surname' => $surname,
            'patronymic' => 'X',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => $email,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.random_int(100000, 999999),
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2024-01-01',
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}
