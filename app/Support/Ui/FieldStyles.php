<?php

namespace App\Support\Ui;

/**
 * The single source of truth for form-control styling (shadcn geometry — 36px height,
 * 10px radius, 3px focus ring — over this app's ink/hairline tokens and the gray resting
 * fill a control needs to be visible on a white card).
 *
 * Every shared field component builds its class list from here, so the whole app changes
 * skin from one file instead of ~500 hand-written class strings.
 */
final class FieldStyles
{
    /** Shape, colour and focus behaviour shared by every control. */
    private const BASE = 'w-full min-w-0 rounded-[10px] border border-hairline bg-[#f4f4f5] text-ink shadow-sm outline-none transition-colors '
        .'placeholder:text-ink-faint '
        .'focus:border-ink focus:bg-white focus:ring-[3px] focus:ring-[#e4e4e7] '
        .'disabled:cursor-not-allowed disabled:opacity-50 '
        .'aria-invalid:border-rose-300 aria-invalid:ring-rose-100';

    /** Single-line control (input). */
    public static function input(string $extra = ''): string
    {
        return self::merge(self::BASE.' h-9 px-3 text-[12.5px]', $extra);
    }

    /** Native select — chevron drawn by the component, so the right padding is reserved. */
    public static function select(string $extra = ''): string
    {
        return self::merge(
            self::BASE.' h-9 appearance-none bg-none px-3 pr-9 text-[12.5px] [&::-ms-expand]:hidden',
            $extra
        );
    }

    /** Multi-line control (textarea). */
    public static function textarea(string $extra = ''): string
    {
        return self::merge(self::BASE.' px-3 py-2 text-[12.5px] leading-relaxed', $extra);
    }

    private static function merge(string $classes, string $extra): string
    {
        return trim($classes.' '.$extra);
    }
}
