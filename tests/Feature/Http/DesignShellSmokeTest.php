<?php

use function Pest\Laravel\get;

it('renders the redesigned login screen with both columns', function () {
    $response = get('/login');

    $response->assertOk()
        ->assertSee(__('ui::auth.actions.log_in'), false)
        ->assertSee(__('ui::auth.marketing.eyebrow'), false)
        ->assertSee(__('ui::auth.marketing.headline'), false);
});
