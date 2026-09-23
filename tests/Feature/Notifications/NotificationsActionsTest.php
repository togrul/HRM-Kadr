<?php

use App\Models\User;
use App\Modules\Notifications\Livewire\NotificationList;
use App\Modules\Notifications\Livewire\Notifications;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function seedUserNotification(User $user, array $data = [], ?string $readAt = null, ?int $minutesAgo = null): DatabaseNotification
{
    $createdAt = now();
    if (is_int($minutesAgo) && $minutesAgo > 0) {
        $createdAt = now()->subMinutes($minutesAgo);
    }

    return DatabaseNotification::query()->create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\SystemNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => array_merge([
            'type' => 'Personnel',
            'action' => 'create',
            'added_by' => 'System',
            'name' => 'Test User',
            'message' => 'has created new personnel',
            'category' => 'New personnel',
        ], $data),
        'read_at' => $readAt ? Carbon::parse($readAt) : null,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ]);
}

it('marks all unread notifications as read from dropdown action', function () {
    $user = User::factory()->create();
    seedUserNotification($user);
    seedUserNotification($user);
    seedUserNotification($user, ['name' => 'Read One'], now()->toDateTimeString());

    $this->actingAs($user);

    Livewire::test(Notifications::class)
        ->call('getNotifications')
        ->call('markAllAsRead');

    expect($user->unreadNotifications()->count())->toBe(0);
});

it('marks single notification as read and redirects based on notification type', function () {
    $user = User::factory()->create();
    $notification = seedUserNotification($user, [
        'type' => 'Leave',
        'name' => 'Leave Request',
        'action' => 'leave',
    ]);

    $this->actingAs($user);

    Livewire::test(Notifications::class)
        ->call('markAsRead', $notification->id)
        ->assertRedirect(route('leaves'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('reads nothing for the dropdown until it is opened, then lists the newest ten', function () {
    $user = User::factory()->create();
    foreach (range(1, 12) as $index) {
        seedUserNotification($user, ['name' => 'Dropdown-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT)], null, $index);
    }

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class)
        ->assertSet('notificationCount', '10+')
        ->assertDontSee('Dropdown-01');

    $component->call('getNotifications')
        ->assertSet('hasLoaded', true)
        ->assertSet('isLoading', false)
        ->assertSee('Dropdown-01')
        ->assertSee('Dropdown-10')
        ->assertDontSee('Dropdown-11');

    expect($component->instance()->dropdownNotifications)->toHaveCount(10);
});

it('keeps no eloquent collection in the snapshot and re-reads only the listed columns', function () {
    $user = User::factory()->create();
    seedUserNotification($user);
    seedUserNotification($user);

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class)->call('getNotifications');

    expect(json_encode($component->snapshot['data']))->not->toContain('DatabaseNotification');

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    // Any later round trip (here: the refresh event another component dispatches).
    $component->dispatch('notifications-refresh-count')->assertSee('Test User');

    expect($queries)->toHaveCount(1)
        ->and($queries[0])->not->toContain('select *')
        ->and($queries[0])->toContain('limit 10');
});

it('updates the badge and the list in the same response after mark all as read', function () {
    $user = User::factory()->create();
    seedUserNotification($user, ['name' => 'Unread One']);
    seedUserNotification($user, ['name' => 'Unread Two']);

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class)
        ->assertSet('notificationCount', 2)
        ->call('getNotifications')
        ->assertSeeHtml('bg-emerald-500')
        ->call('markAllAsRead')
        ->assertSet('notificationCount', 0)
        ->assertSee('Unread One')
        ->assertDontSeeHtml('bg-emerald-500');

    // The badge refresh goes to the standalone counter, never back to this component.
    expect(collect($component->effects['dispatches'] ?? [])->pluck('component')->unique()->all())
        ->toBe(['notification.notifications-counter']);
});

it('picks up a new notification on the next refresh event', function () {
    $user = User::factory()->create();
    seedUserNotification($user, ['name' => 'Older']);

    $this->actingAs($user);

    $component = Livewire::test(Notifications::class)
        ->call('getNotifications')
        ->assertSet('notificationCount', 1);

    seedUserNotification($user, ['name' => 'Brand New']);

    $component->dispatch('notifications-refresh-count')
        ->assertSet('notificationCount', 2)
        ->assertSee('Brand New');
});

it('never shows another user\'s notifications', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    seedUserNotification($other, ['name' => 'Not Yours']);
    seedUserNotification($owner, ['name' => 'Yours']);

    $this->actingAs($owner);

    Livewire::test(Notifications::class)
        ->assertSet('notificationCount', 1)
        ->call('getNotifications')
        ->assertSee('Yours')
        ->assertDontSee('Not Yours');
});

it('clears all notifications from notification list', function () {
    $user = User::factory()->create();
    seedUserNotification($user, ['name' => 'N1']);
    seedUserNotification($user, ['name' => 'N2']);

    $this->actingAs($user);

    Livewire::test(NotificationList::class)
        ->call('clearNotifications');

    expect($user->notifications()->count())->toBe(0);
});

it('paginates notification list route with threshold 20', function () {
    $user = User::factory()->create();

    foreach (range(1, 25) as $index) {
        $label = 'Notif-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
        seedUserNotification($user, ['name' => $label], null, 26 - $index);
    }

    $this->actingAs($user);

    $firstPage = $this->get(route('notifications'));
    $firstPage->assertOk();
    $firstPage->assertSee('Notif-25');
    $firstPage->assertDontSee('Notif-01');

    $secondPage = $this->get(route('notifications', ['page' => 2]));
    $secondPage->assertOk();
    $secondPage->assertSee('Notif-01');
});
