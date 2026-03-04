<?php

namespace Tests\Unit\Modules\Candidates;

use App\Modules\Candidates\Support\CandidateModeResolver;
use App\Services\Profiles\ProfileState;
use Tests\TestCase;

class CandidateModeResolverTest extends TestCase
{
    public function test_it_resolves_explicit_mode_from_config(): void
    {
        config()->set('candidates.mode', 'civilian');

        $resolver = new CandidateModeResolver(new ProfileState([], 'military', []));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
    }

    public function test_it_resolves_auto_mode_from_active_profile_map(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.profile_mode_map', [
            'military' => 'military',
            'public' => 'civilian',
        ]);

        $resolver = new CandidateModeResolver(new ProfileState([], 'public', []));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
    }

    public function test_it_falls_back_to_military_for_invalid_mode(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.profile_mode_map', [
            'default' => 'unsupported-mode',
        ]);

        $resolver = new CandidateModeResolver(new ProfileState([], 'default', []));

        $this->assertSame(CandidateModeResolver::MILITARY, $resolver->resolve());
    }
}

