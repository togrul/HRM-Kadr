<?php

namespace App\Support\Ui;

/**
 * What the page told the layout about its context panel during this request.
 *
 * <x-context-panel> records whether it rendered any content; the page view renders
 * before the layout does, so the layout can start an empty panel collapsed on the
 * server — no flash of an empty column on load.
 */
class ContextPanelState
{
    private ?bool $hasContent = null;

    public function record(bool $hasContent): void
    {
        $this->hasContent = ($this->hasContent ?? false) || $hasContent;
    }

    /** True only when a panel rendered and none of them had anything in it. */
    public function isEmpty(): bool
    {
        return $this->hasContent === false;
    }

    /** Read once by the layout, then cleared so nothing leaks into a later render. */
    public function pull(): bool
    {
        $empty = $this->isEmpty();
        $this->hasContent = null;

        return $empty;
    }
}
