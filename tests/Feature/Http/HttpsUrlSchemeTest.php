<?php

use App\Providers\AppServiceProvider;

it('generates https urls when app.url is https, without relying on proxy headers', function () {
    config()->set('app.url', 'https://hrm.test');

    (new AppServiceProvider($this->app))->boot();

    expect(url('/reports'))->toStartWith('https://');
});

it('leaves url generation alone when app.url is plain http', function () {
    config()->set('app.url', 'http://hrm.test');

    (new AppServiceProvider($this->app))->boot();

    expect(url('/reports'))->toStartWith('http://');
});
