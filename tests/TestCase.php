<?php

namespace Tests;

use App\Models\Personnel;
use App\Models\User;
use App\Models\UserPersonnelLink;
use App\Services\UserPersonnelLinkResolver;
use App\Support\Http\SafeUrl;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Testlər real DNS-ə çıxmır: SSRF qoruyucusu (SafeUrl) üçün deterministik həlledici.
        $this->app->instance(SafeUrl::class, new SafeUrl(
            fn (string $host): array => $host === 'localhost' ? ['127.0.0.1'] : ['93.184.216.34']
        ));
    }

    /**
     * İstifadəçini əməkdaş kartına açıq bağla bağlayır — sistemdə yeganə eyniləşdirmə yolu
     * (e-poçt / ad uyğunluğu nəzərə alınmır).
     */
    protected function linkUserToPersonnel(User $user, Personnel|int $personnel): User
    {
        UserPersonnelLink::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'personnel_id' => $personnel instanceof Personnel ? $personnel->getKey() : $personnel,
                'resolution_source' => 'manual',
                'resolved_at' => now(),
            ]
        );

        app(UserPersonnelLinkResolver::class)->forget((int) $user->getKey());

        return $user;
    }

    /**
     * Test qurğusu: e-poçtu əməkdaşınkı ilə eyni olan test istifadəçisini həmin karta açıq bağla
     * bağlayır — adminin bağ qurmasının (və ya birdəfəlik köçürmənin) ekvivalenti. Tətbiq kodu
     * e-poçtla eyniləşdirmə APARMIR; bu, yalnız köhnə testlərin qurğusunu sadələşdirir.
     */
    protected function linkFixtureUserByEmail(Personnel $personnel): Personnel
    {
        $user = filled($personnel->email)
            ? User::query()->where('email', $personnel->email)->first()
            : null;

        if ($user) {
            $this->linkUserToPersonnel($user, $personnel);
        }

        return $personnel;
    }

    /**
     * {@see linkFixtureUserByEmail()} — qurğudakı bütün əməkdaşlar üçün (bildiriş alıcısı testləri).
     */
    protected function linkFixtureUsersByEmail(): void
    {
        Personnel::query()->whereNotNull('email')->orderBy('id')->get()->each(function (Personnel $personnel): void {
            $user = User::query()->where('email', $personnel->email)->first();

            if ($user && ! UserPersonnelLink::query()->where('user_id', $user->id)->orWhere('personnel_id', $personnel->id)->exists()) {
                $this->linkUserToPersonnel($user, $personnel);
            }
        });
    }
}
