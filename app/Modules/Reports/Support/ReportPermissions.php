<?php

namespace App\Modules\Reports\Support;

/**
 * Hesabatlar modulunun icazə matrisi — tək mənbə.
 *
 * `show-reports` modulun girişidir (panel, ümumi baxış). Hər hesabat isə bir qrupa aiddir
 * (kadr, davamiyyət, məzuniyyət, əmək haqqı, təlim, performans) və qrupun öz baxış icazəsini
 * tələb edir. Bəzi qruplar əlavə olaraq mənbə modulun icazəsini də istəyir: əmək haqqı
 * hesabatı maaş məlumatı daşıdığı üçün `show-payroll` olmadan açılmır.
 */
final class ReportPermissions
{
    public const VIEW = 'show-reports';

    public const EXPORT = 'export-reports';

    public const MANAGE_PRESETS = 'manage-report-presets';

    public const SCHEDULE = 'schedule-reports';

    public const GROUP_PERSONNEL = 'personnel';

    public const GROUP_ATTENDANCE = 'attendance';

    public const GROUP_LEAVE = 'leave';

    public const GROUP_PAYROLL = 'payroll';

    public const GROUP_TRAINING = 'training';

    public const GROUP_PERFORMANCE = 'performance';

    /**
     * Hesabat qrupu => qrupun baxış icazəsi.
     *
     * @var array<string,string>
     */
    public const GROUPS = [
        self::GROUP_PERSONNEL => 'show-personnel-reports',
        self::GROUP_ATTENDANCE => 'show-attendance-reports',
        self::GROUP_LEAVE => 'show-leave-reports',
        self::GROUP_PAYROLL => 'show-payroll-reports',
        self::GROUP_TRAINING => 'show-training-reports',
        self::GROUP_PERFORMANCE => 'show-performance-reports',
    ];

    /**
     * Qrup icazəsinə əlavə olaraq tələb olunan mənbə modul icazələri (hamısı lazımdır).
     *
     * @var array<string,list<string>>
     */
    public const GROUP_PREREQUISITES = [
        self::GROUP_PAYROLL => ['show-payroll'],
    ];

    /**
     * Standart hesabat açarı => qrup.
     *
     * @var array<string,string>
     */
    public const STANDARD_REPORT_GROUPS = [
        'headcount' => self::GROUP_PERSONNEL,
        'demographics' => self::GROUP_PERSONNEL,
        'movements' => self::GROUP_PERSONNEL,
        'attendance' => self::GROUP_ATTENDANCE,
        'training' => self::GROUP_TRAINING,
        'performance' => self::GROUP_PERFORMANCE,
    ];

    /**
     * Dinamik hesabat mənbəyi => qrup.
     *
     * @var array<string,string>
     */
    public const DYNAMIC_SOURCE_GROUPS = [
        'personnel' => self::GROUP_PERSONNEL,
        'attendance' => self::GROUP_ATTENDANCE,
        'training' => self::GROUP_TRAINING,
        'performance' => self::GROUP_PERFORMANCE,
    ];

    /**
     * Müqayisə kartı => qrup.
     *
     * @var array<string,string>
     */
    public const COMPARISON_GROUPS = [
        'headcount_years' => self::GROUP_PERSONNEL,
        'attendance_months' => self::GROUP_ATTENDANCE,
        'training_years' => self::GROUP_TRAINING,
        'performance_distribution' => self::GROUP_PERFORMANCE,
    ];

    /**
     * Modulun bütün icazələri (miqrasiya və rol matrisi üçün).
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::EXPORT,
            ...array_values(self::GROUPS),
            self::MANAGE_PRESETS,
            self::SCHEDULE,
        ];
    }

    public static function groupOfStandardReport(string $report): ?string
    {
        return self::STANDARD_REPORT_GROUPS[$report] ?? null;
    }

    public static function groupOfDynamicSource(string $source): ?string
    {
        return self::DYNAMIC_SOURCE_GROUPS[$source] ?? null;
    }
}
