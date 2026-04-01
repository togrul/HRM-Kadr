<?php

namespace Tests\Unit\Modules\Candidates;

use App\Modules\Candidates\Support\CandidateModeResolver;
use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use App\Services\Profiles\ProfileState;
use Tests\TestCase;

class CandidateModeResolverTest extends TestCase
{
    public function test_it_resolves_explicit_mode_from_config(): void
    {
        config()->set('candidates.mode', 'civilian');
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'default' => 'military',
            'military' => 'military',
        ]);

        $profileState = new ProfileState([], 'military', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
    }

    public function test_it_resolves_auto_mode_from_active_profile_map(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.profile_mode_map', [
            'military' => 'military',
            'public' => 'civilian',
        ]);

        $profileState = new ProfileState([], 'public', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
    }

    public function test_it_normalizes_uppercase_active_profile_when_resolving_mode(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.profile_mode_map', [
            'default' => 'military',
            'public' => 'civilian',
        ]);

        $profileState = new ProfileState([], 'PUBLIC', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
    }

    public function test_it_falls_back_to_military_for_invalid_mode(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.profile_mode_map', [
            'default' => 'unsupported-mode',
        ]);

        $profileState = new ProfileState([], 'default', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::MILITARY, $resolver->resolve());
    }

    public function test_it_uses_workflow_pack_for_legacy_candidate_mode_when_mode_is_auto(): void
    {
        config()->set('candidates.mode', 'auto');
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'default' => 'military',
            'public' => 'public',
        ]);

        $profileState = new ProfileState([], 'public', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
        $this->assertSame('Public', $resolver->label(CandidateModeResolver::CIVILIAN));
    }

    public function test_public_pack_wins_over_explicit_legacy_military_mode(): void
    {
        config()->set('candidates.mode', 'military');
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'default' => 'military',
            'public' => 'public',
        ]);

        $profileState = new ProfileState([], 'public', []);
        $resolver = new CandidateModeResolver($profileState, new CandidateWorkflowPackResolver($profileState));

        $this->assertSame(CandidateModeResolver::CIVILIAN, $resolver->resolve());
        $this->assertSame('Public', $resolver->label(CandidateModeResolver::CIVILIAN));
    }
}
