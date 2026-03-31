<?php

namespace Tests\Unit\Modules\Candidates;

use App\Modules\Candidates\Support\CandidateWorkflowPackResolver;
use App\Services\Profiles\ProfileState;
use Tests\TestCase;

class CandidateWorkflowPackResolverTest extends TestCase
{
    public function test_it_resolves_explicit_workflow_pack_from_config(): void
    {
        config()->set('candidates.workflow_pack', 'public');

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'military', []));

        $this->assertSame(CandidateWorkflowPackResolver::PUBLIC, $resolver->resolve());
    }

    public function test_it_resolves_auto_workflow_pack_from_active_profile_map(): void
    {
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'military' => 'military',
            'public' => 'public',
            'private' => 'private',
        ]);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'private', []));

        $this->assertSame(CandidateWorkflowPackResolver::PRIVATE, $resolver->resolve());
    }

    public function test_it_normalizes_uppercase_active_profile_when_resolving_workflow_pack(): void
    {
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'default' => 'military',
            'public' => 'public',
        ]);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'PUBLIC', []));

        $this->assertSame(CandidateWorkflowPackResolver::PUBLIC, $resolver->resolve());
    }

    public function test_it_falls_back_to_military_for_invalid_workflow_pack(): void
    {
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_pack_map', [
            'default' => 'unsupported-pack',
        ]);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'default', []));

        $this->assertSame(CandidateWorkflowPackResolver::MILITARY, $resolver->resolve());
    }

    public function test_it_limits_visible_packs_by_active_profile_map(): void
    {
        config()->set('candidates.workflow_pack', 'auto');
        config()->set('candidates.workflow_visible_packs', 'auto');
        config()->set('candidates.workflow_visible_pack_map', [
            'default' => ['military'],
            'public' => ['public'],
            'private' => ['private'],
        ]);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'public', []));

        $this->assertSame([CandidateWorkflowPackResolver::PUBLIC], $resolver->available());
        $this->assertTrue($resolver->isLocked());
    }

    public function test_it_can_expose_multiple_visible_packs_from_config(): void
    {
        config()->set('candidates.workflow_visible_packs', ['military', 'public', 'private']);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'military', []));

        $this->assertSame([
            CandidateWorkflowPackResolver::MILITARY,
            CandidateWorkflowPackResolver::PUBLIC,
            CandidateWorkflowPackResolver::PRIVATE,
        ], $resolver->available());
        $this->assertFalse($resolver->isLocked());
    }

    public function test_explicit_workflow_pack_locks_visible_packs_when_visibility_is_auto(): void
    {
        config()->set('candidates.workflow_pack', 'private');
        config()->set('candidates.workflow_visible_packs', 'auto');
        config()->set('candidates.workflow_visible_pack_map', [
            'default' => ['military'],
            'private' => ['private'],
        ]);

        $resolver = new CandidateWorkflowPackResolver(new ProfileState([], 'default', []));

        $this->assertSame(CandidateWorkflowPackResolver::PRIVATE, $resolver->resolve());
        $this->assertSame([CandidateWorkflowPackResolver::PRIVATE], $resolver->available());
        $this->assertTrue($resolver->isLocked());
    }
}
