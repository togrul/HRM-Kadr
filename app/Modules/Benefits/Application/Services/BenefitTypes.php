<?php

namespace App\Modules\Benefits\Application\Services;

/**
 * İmtiyaz növləri kataloqu (config/benefits.php → types) və hər növün standart vergi rejimi.
 * Hüquqi əsas: docs/payroll-legal-basis.md, «İmtiyazlar — natura formasında gəlir» bölməsi; mühasib paket səviyyəsində dəyişə bilər.
 */
class BenefitTypes
{
    /** Vergi rejiminin izahında göstərilən rəsmi mətnlər. */
    public const TAX_CODE_URL = 'https://e-qanun.az/framework/46948';

    public const SOCIAL_INSURANCE_LAW_URL = 'https://e-qanun.az/framework/3813';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys((array) config('benefits.types', []));
    }

    public static function exists(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    /**
     * @return array{taxable: bool, affects_social: bool, basis: string, exclusive: bool, allows_dependents: bool}
     */
    public static function defaults(string $type): array
    {
        $config = (array) config('benefits.types.'.$type, config('benefits.types.other', []));

        return [
            'taxable' => (bool) ($config['taxable'] ?? true),
            'affects_social' => (bool) ($config['affects_social'] ?? true),
            'basis' => (string) ($config['basis'] ?? 'vm_98_1'),
            'exclusive' => (bool) ($config['exclusive'] ?? false),
            'allows_dependents' => (bool) ($config['allows_dependents'] ?? false),
        ];
    }

    public static function exclusive(string $type): bool
    {
        return self::defaults($type)['exclusive'];
    }

    /**
     * @return list<string>
     */
    public static function bases(): array
    {
        return array_values(array_unique(array_map(
            static fn (string $type): string => self::defaults($type)['basis'],
            self::all(),
        )));
    }
}
