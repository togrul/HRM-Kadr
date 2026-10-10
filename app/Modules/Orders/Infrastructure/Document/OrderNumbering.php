<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\OrderLog;
use App\Models\Setting;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Automatic order numbering, configured per install in Admin → Settings.
 *
 * With no format configured nothing changes: the author types the number and it is
 * required. With a format, the author may leave the number empty — the order then
 * carries a provisional placeholder (unique, never printed) until it is approved, when
 * the next number of its sequence is taken under a row lock inside the approval
 * transaction. A number typed by hand is still accepted and kept. A number is assigned
 * once: a later revert keeps it and it is never handed out again.
 *
 * Format tokens: {N} the counter, {N:3} the counter zero-padded to 3 digits, {il} the
 * order's year, {növ} the order type's short code. A format without a counter gets
 * "-{N}" appended, so two orders can never render to the same number.
 */
class OrderNumbering
{
    public const SETTING_FORMAT = 'orders::order_numbering.settings.format';

    public const SETTING_SCOPE = 'orders::order_numbering.settings.scope';

    public const SETTING_YEARLY_RESET = 'orders::order_numbering.settings.yearly_reset';

    public const SETTING_TYPE_CODES = 'orders::order_numbering.settings.type_codes';

    /** Counter shared by every order type. */
    public const SCOPE_GLOBAL = 'global';

    /** One counter per order type (template). */
    public const SCOPE_TYPE = 'type';

    /** Marks a placeholder number held by an order that has not been approved yet. */
    public const PROVISIONAL_PREFIX = '~';

    /** @var array<string,string|null>|null */
    private ?array $settings = null;

    public function isAutomatic(): bool
    {
        return $this->format() !== '';
    }

    /** The configured number format ('' when numbering is manual). */
    public function format(): string
    {
        return trim((string) ($this->settings()[self::SETTING_FORMAT] ?? ''));
    }

    public function scope(): string
    {
        $value = Str::lower(trim((string) ($this->settings()[self::SETTING_SCOPE] ?? '')));

        // Stored as an on/off switch ("1" = a counter per order type); the earlier free-text
        // values ("type", "növ", …) are still understood.
        if (in_array($value, [self::SCOPE_TYPE, 'növ', 'nov', 'per_type'], true)) {
            return self::SCOPE_TYPE;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? self::SCOPE_TYPE : self::SCOPE_GLOBAL;
    }

    public function resetsYearly(): bool
    {
        $value = $this->settings()[self::SETTING_YEARLY_RESET] ?? null;

        return $value === null ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** True for a placeholder number — the order is still waiting for its real one. */
    public static function isProvisional(?string $orderNo): bool
    {
        return str_starts_with((string) $orderNo, self::PROVISIONAL_PREFIX);
    }

    /** A fresh, unique placeholder for an order issued without a number. */
    public function provisional(): string
    {
        return self::PROVISIONAL_PREFIX.Str::lower((string) Str::ulid());
    }

    /** The number as printed or shown: empty while it is still provisional. */
    public static function display(?string $orderNo): string
    {
        return self::isProvisional($orderNo) ? '' : (string) $orderNo;
    }

    /**
     * Take the next number of the order type's sequence. Must run inside the approval
     * transaction: the sequence row stays locked until it commits, so two approvals can
     * never draw the same value, and a rolled-back approval gives its value back.
     *
     * @throws DomainException when no format is configured
     */
    public function assign(string $templateCode, CarbonInterface $date): string
    {
        $format = $this->format();
        if ($format === '') {
            throw new DomainException(__('orders::order_composer.errors.number_missing'));
        }

        $scopeKey = $this->scope() === self::SCOPE_TYPE ? 'type:'.$templateCode : self::SCOPE_GLOBAL;
        $year = $this->resetsYearly() ? (int) $date->year : 0;

        DB::table('order_number_sequences')->insertOrIgnore([
            'scope_key' => $scopeKey,
            'year' => $year,
            'last_value' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('order_number_sequences')
            ->where('scope_key', $scopeKey)
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        $value = (int) ($sequence->last_value ?? 0);
        $typeCode = $this->typeCode($templateCode);

        // A hand-typed number may already hold the next value: skip past it, never reuse.
        do {
            $value++;
            $number = $this->render($format, $value, (int) $date->year, $typeCode);
        } while (OrderLog::withTrashed()->where('order_no', $number)->exists());

        DB::table('order_number_sequences')
            ->where('scope_key', $scopeKey)
            ->where('year', $year)
            ->update(['last_value' => $value, 'updated_at' => now()]);

        return $number;
    }

    /** Fill a format's tokens. */
    public function render(string $format, int $value, int $year, string $typeCode): string
    {
        if (preg_match('/\{N(?::\d+)?\}/u', $format) !== 1) {
            $format .= '-{N}';
        }

        $number = (string) preg_replace_callback(
            '/\{N(?::(\d+))?\}/u',
            static fn (array $match): string => isset($match[1])
                ? str_pad((string) $value, (int) $match[1], '0', STR_PAD_LEFT)
                : (string) $value,
            $format,
        );

        return strtr($number, ['{il}' => (string) $year, '{növ}' => $typeCode, '{nov}' => $typeCode]);
    }

    /**
     * The order type's short code for {növ}: from the "code=SHORT, …" mapping setting,
     * else the template code upper-cased.
     */
    public function typeCode(string $templateCode): string
    {
        foreach (preg_split('/[,;\n]+/u', (string) ($this->settings()[self::SETTING_TYPE_CODES] ?? '')) ?: [] as $pair) {
            [$code, $short] = array_pad(array_map('trim', explode('=', $pair, 2)), 2, '');
            if ($code !== '' && $short !== '' && $code === $templateCode) {
                return $short;
            }
        }

        return Str::upper($templateCode);
    }

    /**
     * @return array<string,string|null>
     */
    private function settings(): array
    {
        return $this->settings ??= Setting::query()
            ->whereIn('name', [self::SETTING_FORMAT, self::SETTING_SCOPE, self::SETTING_YEARLY_RESET, self::SETTING_TYPE_CODES])
            ->toBase()
            ->pluck('value', 'name')
            ->map(fn (mixed $value): ?string => $value === null ? null : (string) $value)
            ->all();
    }
}
