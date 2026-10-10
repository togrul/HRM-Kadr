<?php

namespace App\Modules\Leaves\Application\Services;

use App\Models\Setting;

/**
 * How many days a sick-leave certificate may stay open before the home page flags it
 * (Admin → Settings, "Open sick certificate alert (days)"). Defaults to 30; a blank or
 * non-positive value falls back to the default.
 */
class SickCertificateSettings
{
    public const SETTING = 'Open sick certificate alert (days)';

    public const DEFAULT_STALE_DAYS = 30;

    public function staleAfterDays(): int
    {
        $configured = (int) trim((string) Setting::query()->where('name', self::SETTING)->value('value'));

        return $configured > 0 ? $configured : self::DEFAULT_STALE_DAYS;
    }
}
