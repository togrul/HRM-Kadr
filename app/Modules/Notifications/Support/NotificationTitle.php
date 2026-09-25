<?php

namespace App\Modules\Notifications\Support;

/**
 * Duplicated campaigns get a "(surət)" / "(copy)" marker per copy. Screens show the title
 * without them (and a copy badge instead); duplicating starts again from the base title.
 */
final class NotificationTitle
{
    /**
     * The title with its copy markers removed — every marker, or with `$trailingOnly`
     * only the run at the end.
     */
    public static function normalize(?string $title, bool $trailingOnly = false): string
    {
        return trim((string) preg_replace(self::pattern($trailingOnly ? '+$' : ''), '', (string) $title));
    }

    /** How many copy markers the title carries. */
    public static function copyCount(?string $title): int
    {
        return (int) preg_match_all(self::pattern(), (string) $title);
    }

    /**
     * The active locale's suffix and badge label, plus the az/en literals so a title
     * copied under another locale still normalizes.
     */
    private static function pattern(string $tail = ''): string
    {
        $parts = array_filter([
            preg_quote(trim((string) __('notifications::common.badges.copy_suffix')), '/'),
            '\('.preg_quote(trim((string) __('notifications::common.badges.copy_label')), '/').'\)',
            '\(surət\)',
            '\(copy\)',
        ]);

        return '/(?:\s*(?:'.implode('|', $parts).'))'.$tail.'/iu';
    }
}
