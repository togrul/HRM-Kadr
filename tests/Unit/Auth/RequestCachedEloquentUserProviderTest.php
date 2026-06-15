<?php

namespace Tests\Unit\Auth;

use App\Auth\RequestCachedEloquentUserProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RequestCachedEloquentUserProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reuses_user_models_retrieved_by_id_during_the_request(): void
    {
        $user = User::factory()->create();
        $provider = new RequestCachedEloquentUserProvider($this->app['hash'], User::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $first = $provider->retrieveById($user->id);
        $second = $provider->retrieveById($user->id);

        DB::disableQueryLog();

        $this->assertTrue($first->is($user));
        $this->assertSame($first, $second);
        $this->assertCount(1, DB::getQueryLog());
    }
}
