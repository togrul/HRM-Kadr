<?php

use App\Modules\Notifications\Livewire\NotificationList;
use App\Modules\Notifications\Livewire\Notifications;
use App\Modules\Notifications\Livewire\NotificationsCounter;
use Livewire\Livewire;

it('renders notifications dropdown component for guest without crashing', function () {
    Livewire::test(Notifications::class)
        ->call('getNotifications')
        ->assertSet('hasLoaded', true)
        ->assertSet('isLoading', false)
        ->assertSee(__('notifications::common.labels.no_new_notifications'));
});

it('renders notification list page component for guest without root-tag issues', function () {
    Livewire::test(NotificationList::class)
        ->assertSee(__('notifications::common.labels.no_notifications_found'));
});

it('renders notifications counter component for guest', function () {
    Livewire::test(NotificationsCounter::class)
        ->assertSet('notificationCount', null);
});
