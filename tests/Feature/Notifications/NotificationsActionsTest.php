<?php

use App\Models\User;
use App\Modules\Notifications\Livewire\NotificationList;
use App\Modules\Notifications\Livewire\Notifications;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
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
