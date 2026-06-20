<?php

namespace Tests\Unit\Modules\Attendance;

use App\Modules\Attendance\Support\LeaveLegendPresenter;
use Tests\TestCase;

class LeaveLegendPresenterTest extends TestCase
{
    private LeaveLegendPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new LeaveLegendPresenter;
    }

    public function test_legend_code_prefers_type_code_then_absence_then_initials(): void
    {
        $this->assertSame('AN', $this->presenter->resolveLeaveLegendCode('an', '', ''));
        $this->assertSame('LEAVE', $this->presenter->resolveLeaveLegendCode('', '', 'leave'));
        // Two-word name → first letter of each word.
        $this->assertSame('MƏ', $this->presenter->resolveLeaveLegendCode('', 'Məzuniyyət Əmək', ''));
        $this->assertSame('', $this->presenter->resolveLeaveLegendCode('', '', ''));
    }

    public function test_legend_icon_only_for_bare_leave_absence(): void
    {
        $this->assertSame('icons.personal-affair-icon', $this->presenter->resolveLeaveLegendIcon('', 'LEAVE'));
        $this->assertNull($this->presenter->resolveLeaveLegendIcon('an', 'LEAVE'));
        $this->assertNull($this->presenter->resolveLeaveLegendIcon('', 'OTHER'));
    }

    public function test_family_key_priority_id_code_name_absence(): void
    {
        $this->assertSame('leave-family:id:5', $this->presenter->resolveLeaveLegendFamilyKey(5, 'x', 'y', 'z'));
        $this->assertSame('leave-family:code:AN', $this->presenter->resolveLeaveLegendFamilyKey(null, 'AN', 'y', 'z'));
        $this->assertSame('leave-family:name:Name', $this->presenter->resolveLeaveLegendFamilyKey(null, '', 'Name', 'z'));
        $this->assertSame('leave-family:absence:Z', $this->presenter->resolveLeaveLegendFamilyKey(null, '', '', 'Z'));
        $this->assertSame('leave-family:absence:unknown', $this->presenter->resolveLeaveLegendFamilyKey(null, '', '', ''));
    }

    public function test_tone_is_deterministic_and_within_palette(): void
    {
        $palette = ['blue', 'purple', 'red', 'sky', 'green', 'secondary'];

        $tone = $this->presenter->resolveLeaveTone(42, 'Name', 'AN');
        $this->assertContains($tone, $palette);
        // Same seed → same tone (crc32-stable).
        $this->assertSame($tone, $this->presenter->resolveLeaveTone(42, 'Other', 'XX'));
    }

    public function test_tone_class_helpers_map_known_tones_and_fall_back(): void
    {
        $this->assertSame('bg-blue-50/80', $this->presenter->resolveLeaveToneClasses('blue'));
        $this->assertSame('bg-zinc-50/80', $this->presenter->resolveLeaveToneClasses('unknown'));

        $this->assertSame('text-emerald-600', $this->presenter->resolveLeaveToneIconColor('green'));
        $this->assertSame('text-zinc-600', $this->presenter->resolveLeaveToneIconColor('unknown'));

        $this->assertSame('secondary', $this->presenter->resolveLeaveToneBadgeMode('unknown'));
        $this->assertSame('red', $this->presenter->resolveLeaveToneBadgeMode('red'));

        $this->assertStringContainsString('text-sky-700', $this->presenter->resolveLeaveToneCodeClasses('sky'));
        $this->assertStringContainsString('text-zinc-700', $this->presenter->resolveLeaveToneCodeClasses('unknown'));
    }
}
