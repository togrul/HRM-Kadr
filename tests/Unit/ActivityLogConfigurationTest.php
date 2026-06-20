<?php

use App\Models\AuditActivity;
use Tests\TestCase;

uses(TestCase::class);

test('activity log uses the audit activity model', function () {
    expect(config('activitylog.activity_model'))->toBe(AuditActivity::class);
});

test('audit activity model resolves configured activity log connection', function () {
    config(['activitylog.database_connection' => 'audit']);

    expect((new AuditActivity)->getConnectionName())->toBe('audit');
});
