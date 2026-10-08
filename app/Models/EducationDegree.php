<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationDegree extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'title_az',
        'title_en',
        'title_ru',
    ];

    public $timestamps = false;

    /**
     * Degree names mixed "Ali təhsil - 1993-cü ilə qədər" with "Ali təhsil — 1997-ci ilə qədər".
     * A separating dash (one with spaces around it) is always an em dash; hyphens inside
     * words ("1993-cü") are left alone.
     */
    public static function normalizeTitle(?string $title): ?string
    {
        if ($title === null) {
            return null;
        }

        return trim((string) preg_replace('/\s+[-–—]+\s+/u', ' — ', $title));
    }

    /** @return Attribute<string|null, string|null> */
    protected function titleAz(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value,
            set: fn (?string $value): ?string => self::normalizeTitle($value),
        );
    }

    /** @return Attribute<string|null, string|null> */
    protected function titleEn(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value,
            set: fn (?string $value): ?string => self::normalizeTitle($value),
        );
    }

    /** @return Attribute<string|null, string|null> */
    protected function titleRu(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?string => $value,
            set: fn (?string $value): ?string => self::normalizeTitle($value),
        );
    }
}
