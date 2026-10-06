<?php

namespace Tests\Unit;

use App\Services\Profiles\ProfileState;
use PHPUnit\Framework\TestCase;

class ProfileStateTest extends TestCase
{
    public function test_active_profile_is_resolved_case_insensitively(): void
    {
        $profiles = [
            'public' => ['features' => ['military_service' => true], 'modules' => []],
        ];

        // APP_TYPE may be supplied upper-cased (e.g. PUBLIC) — it must still match the 'public' key.
        $state = new ProfileState($profiles, 'PUBLIC');

        $this->assertSame('public', $state->active());
        $this->assertTrue($state->features()['military_service']);
    }
}
