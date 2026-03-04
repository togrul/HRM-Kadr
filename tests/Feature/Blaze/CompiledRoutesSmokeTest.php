<?php

use App\Models\User;

dataset('blaze-smoke-routes', [
    '/orders',
    '/staffs',
    '/candidates',
    '/leaves',
    '/vacations',
    '/business-trips',
    '/services',
    '/admin/dashboard',
    '/notifications',
]);

it('does not return server errors on key module routes under blaze canary', function (string $uri) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get($uri);

    $exception = $response->exception;

    $this->assertTrue(
        $response->status() < 500,
        sprintf(
            'Route [%s] returned status %d. Exception: %s %s',
            $uri,
            $response->status(),
            $exception ? $exception::class : '-',
            $exception?->getMessage() ?? ''
        )
    );
})->with('blaze-smoke-routes');
