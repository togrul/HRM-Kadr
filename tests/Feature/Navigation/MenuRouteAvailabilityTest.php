<?php

use App\Models\User;

it('skips unavailable menu routes when rendering the header', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $html = view('includes.header', [
        'menus' => collect([
            (object) [
                'url' => 'missing.route',
                'name' => 'Missing route label',
                'icon' => 'holiday-icon',
                'permission' => null,
            ],
        ]),
    ])->render();

    expect($html)
        ->not->toContain('missing.route')
        ->not->toContain('Missing route label')
        ->not->toContain('holiday-icon');
});
