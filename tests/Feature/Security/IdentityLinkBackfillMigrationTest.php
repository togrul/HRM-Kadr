<?php

use App\Models\UserPersonnelLink;

require_once __DIR__.'/Support/identity_fixtures.php';

/*
 * Birdəfəlik köçürmə: yalnız birmənalı (tam, unikal, böyük/kiçik hərfə həssas olmayan)
 * e-poçt uyğunluqları açıq bağa çevrilir; ad uyğunluğu ilə avtomatik yaranmış bağlar silinir.
 */

function runIdentityBackfill(): void
{
    (require base_path('app/Modules/Personnel/Database/Migrations/2026_10_10_210100_backfill_explicit_user_personnel_links.php'))->up();
}

it('links only exact, unique, case-insensitive e-mail matches', function (): void {
    $unique = identityPersonnel('BF-ONE', ['email' => 'One@Example.test ']);
    $uniqueUser = identityUser([], ['email' => 'one@example.test']);

    identityPersonnel('BF-DUP1', ['email' => 'dup@example.test']);
    identityPersonnel('BF-DUP2', ['email' => 'DUP@example.test']);
    $dupUser = identityUser([], ['email' => 'dup@example.test']);

    identityPersonnel('BF-GONE', ['email' => 'gone@example.test', 'leave_work_date' => '2025-01-01']);
    $goneUser = identityUser([], ['email' => 'gone@example.test']);

    $noMatch = identityUser([], ['email' => 'nobody@example.test']);

    runIdentityBackfill();

    expect(UserPersonnelLink::query()->where('user_id', $uniqueUser->id)->value('personnel_id'))->toBe($unique->id)
        ->and(UserPersonnelLink::query()->where('user_id', $uniqueUser->id)->value('resolution_source'))->toBe('email_backfill')
        ->and(UserPersonnelLink::query()->whereIn('user_id', [$dupUser->id, $goneUser->id, $noMatch->id])->exists())->toBeFalse();
});

it('drops automatic name-match links and keeps admin-made ones', function (): void {
    $nameMatched = identityPersonnel('BF-NAME', ['email' => null]);
    $manual = identityPersonnel('BF-MAN', ['email' => null]);
    $userA = identityUser();
    $userB = identityUser();

    UserPersonnelLink::query()->create(['user_id' => $userA->id, 'personnel_id' => $nameMatched->id, 'resolution_source' => 'name_match']);
    UserPersonnelLink::query()->create(['user_id' => $userB->id, 'personnel_id' => $manual->id, 'resolution_source' => 'manual']);

    runIdentityBackfill();

    $this->assertDatabaseMissing('user_personnel_links', ['user_id' => $userA->id]);
    $this->assertDatabaseHas('user_personnel_links', ['user_id' => $userB->id, 'personnel_id' => $manual->id, 'resolution_source' => 'manual']);
});
