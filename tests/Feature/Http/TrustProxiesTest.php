<?php

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

it('trusts the reverse proxy scheme header so generated urls stay https', function () {
    $request = Request::create('http://hrm.test/livewire/update', 'POST', server: [
        'REMOTE_ADDR' => '10.0.0.7',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);

    app(TrustProxies::class)->handle($request, fn (Request $forwarded) => response($forwarded->getSchemeAndHttpHost()));

    expect($request->isSecure())->toBeTrue()
        ->and($request->getSchemeAndHttpHost())->toStartWith('https://');
});
