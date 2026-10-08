<?php

use App\Enums\OrderStatusEnum;
use App\Models\AuditActivity;
use App\Models\Leave;
use App\Models\User;
use App\Modules\Audit\Application\Services\ActivityLogReader;
use App\Modules\Audit\Livewire\ActivityLogDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('az');
});

it('logs a leave being created, updated and deleted', function (): void {
    $leave = Leave::query()->create([
        'tabel_no' => 'LV-001',
        'starts_at' => '2026-10-12',
        'ends_at' => '2026-10-14',
        'duration_unit' => 'day',
        'reason' => 'Ailə vəziyyəti',
        'status_id' => OrderStatusEnum::PENDING->value,
    ]);

    $leave->update(['reason' => 'Həkim qəbulu']);
    $leave->delete();

    $events = AuditActivity::query()
        ->where('subject_type', Leave::class)
        ->where('subject_id', $leave->id)
        ->orderBy('id')
        ->get();

    expect($events->pluck('event')->all())->toBe(['created', 'updated', 'deleted'])
        ->and($events->pluck('log_name')->unique()->all())->toBe(['leaves'])
        ->and(data_get($events[1]->properties, 'attributes.reason'))->toBe('Həkim qəbulu')
        ->and(data_get($events[1]->properties, 'old.reason'))->toBe('Ailə vəziyyəti');
});

it('shows English-stored descriptions, events and log names in Azerbaijani', function (): void {
    $reader = app(ActivityLogReader::class);

    expect($reader->descriptionLabel('created'))->toBe('Qeyd yaradıldı')
        ->and($reader->descriptionLabel('This model has been updated'))->toBe('Qeyd yeniləndi')
        ->and($reader->descriptionLabel('order.approved'))->toBe('Əmr təsdiqləndi')
        ->and($reader->descriptionLabel('Something nobody mapped'))->toBe('Something nobody mapped')
        ->and($reader->eventLabel('approved'))->toBe('Təsdiqləndi')
        ->and($reader->logNameLabel('default'))->toBe('Ümumi')
        ->and($reader->logNameLabel('custom_log'))->toBe('custom_log');

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

    AuditActivity::query()->create([
        'log_name' => 'default',
        'description' => 'This model has been created',
        'event' => 'approved',
        'causer_type' => User::class,
        'causer_id' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(ActivityLogDashboard::class)
        ->assertSee('Qeyd yaradıldı')
        ->assertSee('Təsdiqləndi')
        ->assertSee('Ümumi')
        ->assertDontSee('This model has been created');
});

it('lists only real changes with human labels, hiding ids and no-op pairs', function (): void {
    $activity = AuditActivity::query()->create([
        'log_name' => 'personnel',
        'description' => 'updated',
        'event' => 'updated',
        'properties' => [
            'attributes' => ['id' => 7, 'photo' => null, 'reason' => 'Yeni', 'total_days' => 3, 'updated_at' => '2026-10-08 10:00:00'],
            'old' => ['id' => 7, 'photo' => '', 'reason' => 'Köhnə', 'total_days' => '3', 'updated_at' => '2026-10-07 10:00:00'],
        ],
    ]);

    $rows = app(ActivityLogReader::class)->changeRows($activity);

    expect($rows)->toBe([
        ['key' => 'reason', 'field' => 'Səbəb', 'old' => 'Köhnə', 'new' => 'Yeni'],
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('show-audit-logs', 'web'));

    Livewire::actingAs($user)
        ->test(ActivityLogDashboard::class)
        ->call('selectActivity', $activity->id)
        ->assertSee('Dəyişikliklər')
        ->assertSee('Köhnə')
        ->assertDontSee('Yeni dəyərlər');
});

it('lists a created record without a before value and skips blank fields', function (): void {
    $activity = AuditActivity::query()->create([
        'log_name' => 'leaves',
        'description' => 'created',
        'event' => 'created',
        'properties' => ['attributes' => ['id' => 1, 'reason' => 'Səbəb mətni', 'document_path' => null]],
    ]);

    expect(app(ActivityLogReader::class)->changeRows($activity))->toBe([
        ['key' => 'reason', 'field' => 'Səbəb', 'old' => null, 'new' => 'Səbəb mətni'],
    ]);
});
