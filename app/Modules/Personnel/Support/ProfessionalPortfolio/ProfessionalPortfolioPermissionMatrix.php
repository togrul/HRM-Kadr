<?php

namespace App\Modules\Personnel\Support\ProfessionalPortfolio;

use App\Models\User;

class ProfessionalPortfolioPermissionMatrix
{
    public static function portfolioViewPermissions(): array
    {
        return [
            'view-professional-portfolio',
            'manage-personnel-event-records',
            'manage-personnel-media-records',
            'manage-personnel-project-records',
            'verify-professional-portfolio-records',
            'verify-personnel-event-records',
            'verify-personnel-media-records',
            'verify-personnel-project-records',
            'view-professional-portfolio-analytics',
            'view-restricted-media-records',
        ];
    }

    public static function analyticsViewPermissions(): array
    {
        return [
            'view-professional-portfolio-analytics',
            'view-professional-portfolio',
            'verify-professional-portfolio-records',
        ];
    }

    public static function eventViewPermissions(): array
    {
        return [
            'view-professional-portfolio',
            'manage-personnel-event-records',
            'verify-professional-portfolio-records',
            'verify-personnel-event-records',
        ];
    }

    public static function mediaViewPermissions(): array
    {
        return [
            'view-professional-portfolio',
            'manage-personnel-media-records',
            'verify-professional-portfolio-records',
            'verify-personnel-media-records',
            'view-restricted-media-records',
        ];
    }

    public static function projectViewPermissions(): array
    {
        return [
            'view-professional-portfolio',
            'manage-personnel-project-records',
            'verify-professional-portfolio-records',
            'verify-personnel-project-records',
        ];
    }

    public static function eventVerifyPermissions(): array
    {
        return ['verify-professional-portfolio-records', 'verify-personnel-event-records'];
    }

    public static function mediaVerifyPermissions(): array
    {
        return ['verify-professional-portfolio-records', 'verify-personnel-media-records'];
    }

    public static function projectVerifyPermissions(): array
    {
        return ['verify-professional-portfolio-records', 'verify-personnel-project-records'];
    }

    public static function canViewPortfolio(?User $user): bool
    {
        return $user?->canAny(self::portfolioViewPermissions()) ?? false;
    }

    public static function canViewAnalytics(?User $user): bool
    {
        return $user?->canAny(self::analyticsViewPermissions()) ?? false;
    }

    public static function canVerifyEvents(?User $user): bool
    {
        return $user?->canAny(self::eventVerifyPermissions()) ?? false;
    }

    public static function canVerifyMedia(?User $user): bool
    {
        return $user?->canAny(self::mediaVerifyPermissions()) ?? false;
    }

    public static function canVerifyProjects(?User $user): bool
    {
        return $user?->canAny(self::projectVerifyPermissions()) ?? false;
    }
}
