<?php

namespace App\Modules\Personnel\Support\ProfessionalPortfolio;

class ProfessionalPortfolioOptions
{
    public static function eventTypes(): array
    {
        return ['conference', 'seminar', 'forum', 'workshop', 'panel', 'other'];
    }

    public static function participationRoles(): array
    {
        return ['participant', 'speaker', 'moderator', 'panelist', 'presenter', 'organizer'];
    }

    public static function attendanceFormats(): array
    {
        return ['offline', 'online', 'hybrid'];
    }

    public static function strategicLevels(): array
    {
        return ['informational', 'development', 'representation', 'strategic'];
    }

    public static function eventVisibilities(): array
    {
        return ['internal', 'public'];
    }

    public static function mediaPublisherTypes(): array
    {
        return ['tv', 'website', 'newspaper', 'magazine', 'youtube', 'social_media', 'official_portal', 'other'];
    }

    public static function mediaMentionTypes(): array
    {
        return ['interview', 'news_mention', 'article_author', 'appearance', 'quote', 'press_release', 'other'];
    }

    public static function mediaSentiments(): array
    {
        return ['positive', 'neutral', 'negative'];
    }

    public static function mediaVisibilities(): array
    {
        return ['internal', 'public', 'restricted'];
    }

    public static function mediaStatuses(): array
    {
        return ['pending', 'verified', 'rejected', 'broken_link', 'archived_only'];
    }

    public static function projectTypes(): array
    {
        return ['internal', 'interagency', 'international', 'digital', 'security', 'infrastructure', 'other'];
    }

    public static function verificationStatuses(): array
    {
        return ['pending', 'verified', 'rejected'];
    }
}
