<?php

namespace App\Modules\Staff\Contracts;

/**
 * Result of a ştat check for one (structure, position). The warning text comes from the
 * Staff module's translations so a consumer can show it as-is.
 */
final readonly class StaffSlotCheck
{
    public const AVAILABLE = 'available';

    public const FULL = 'full';

    public const MISSING = 'missing';

    public const UNKNOWN = 'unknown';

    private function __construct(
        public string $status,
        public int $total = 0,
        public int $filled = 0,
        public bool $blocking = false,
    ) {}

    public static function available(int $total, int $filled): self
    {
        return new self(self::AVAILABLE, $total, $filled);
    }

    public static function full(int $total, int $filled, bool $blocking): self
    {
        return new self(self::FULL, $total, $filled, $blocking);
    }

    public static function missing(bool $blocking): self
    {
        return new self(self::MISSING, blocking: $blocking);
    }

    /** Structure or position not chosen yet (or the Staff module is off). */
    public static function unknown(): self
    {
        return new self(self::UNKNOWN);
    }

    public function vacant(): int
    {
        return max(0, $this->total - $this->filled);
    }

    public function hasWarning(): bool
    {
        return $this->status === self::FULL || $this->status === self::MISSING;
    }

    /** True when the install hard-blocks and there is no free slot. */
    public function blocks(): bool
    {
        return $this->blocking && $this->hasWarning();
    }

    public function message(): ?string
    {
        return match ($this->status) {
            self::FULL => __('staff::common.slot.full', ['filled' => $this->filled, 'total' => $this->total]),
            self::MISSING => __('staff::common.slot.missing'),
            default => null,
        };
    }
}
