<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;

abstract class BaseWordExportService
{
    protected function bootWord(array $sectionOptions, string $fontName = 'Arial', int $fontSize = 12): array
    {
        Settings::setDefaultFontName($fontName);
        Settings::setDefaultFontSize($fontSize);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection($sectionOptions);

        return [$phpWord, $section];
    }

    protected function saveWord(PhpWord $phpWord, string $prefix): string
    {
        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);

        $suffix = Str::uuid()->toString();
        $path = $dir.'/'.$prefix.'_'.$suffix.'.docx';

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    protected function cm(float $value): int
    {
        return Converter::cmToTwip($value);
    }

    protected function rowHeight(int $pixels): int
    {
        return Converter::pixelToTwip($pixels);
    }

    protected function formatDate($value): string
    {
        if (! $value) {
            return '';
        }
        if ($value instanceof \Carbon\Carbon) {
            return $value->format('d.m.Y');
        }
        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return (string) $value;
    }
}
