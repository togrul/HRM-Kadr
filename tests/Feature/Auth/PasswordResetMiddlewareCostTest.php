<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsurePasswordResetIsCompleted;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PasswordResetMiddlewareCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_the_reset_flag_costs_no_role_lookup(): void
    {
        $user = User::factory()->create()->fresh();
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $response = (new EnsurePasswordResetIsCompleted)->handle($request, fn () => new Response('ok'));

        $this->assertSame('ok', $response->getContent());
        $this->assertSame(0, $queries);
    }
}
