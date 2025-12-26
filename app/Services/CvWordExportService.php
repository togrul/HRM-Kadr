<?php

namespace App\Services;

use App\Models\Personnel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\Style\Image;
use PhpOffice\PhpWord\Style\Tab;

class CvWordExportService
{
    public function export(Personnel $personnel, array $cvData): string
    {
        Settings::setDefaultFontName('Arial');
        Settings::setDefaultFontSize(12);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'paperSize' => 'A4',
            'marginTop' => Converter::inchToTwip(0.1),
            'marginRight' => Converter::inchToTwip(0.3),
            'marginBottom' => Converter::inchToTwip(0.3),
            'marginLeft' => Converter::inchToTwip(0.7),
        ]);

        $watermarkPath = $this->watermarkImagePath();
        if ($watermarkPath) {
            $header = $section->addHeader();
            $header->addWatermark($watermarkPath, [
                'width' => Converter::cmToPixel(18),
                'height' => Converter::cmToPixel(18),
                'posHorizontal' => Image::POSITION_HORIZONTAL_CENTER,
                'posHorizontalRel' => Image::POSITION_RELATIVE_TO_PAGE,
                'posVertical' => Image::POSITION_VERTICAL_CENTER,
                'posVerticalRel' => Image::POSITION_RELATIVE_TO_PAGE,
                'wrappingStyle' => Image::WRAPPING_STYLE_BEHIND,
            ]);
        }

        $photoWidthCm = 3.51;
        $photoHeightCm = 4.5;
        if (! empty($personnel->photo) && Storage::disk('public')->exists($personnel->photo)) {
            $section->addImage(Storage::disk('public')->path($personnel->photo), [
                'width' => Converter::cmToPoint($photoWidthCm),
                'height' => Converter::cmToPoint($photoHeightCm),
                'ratio' => false,
                'positioning' => Image::POSITION_ABSOLUTE,
                'posHorizontal' => Image::POSITION_HORIZONTAL_RIGHT,
                'posHorizontalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
                'posVertical' => Image::POSITION_VERTICAL_TOP,
                'posVerticalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
                'wrappingStyle' => Image::WRAPPING_STYLE_SQUARE,
            ]);
        } else {
            $placeholder = $section->addTextBox([
                'width' => Converter::cmToPoint($photoWidthCm),
                'height' => Converter::cmToPoint($photoHeightCm),
                'borderSize' => 1,
                'borderColor' => '000000',
                'posHorizontal' => Image::POSITION_HORIZONTAL_RIGHT,
                'posHorizontalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
                'posVertical' => Image::POSITION_VERTICAL_TOP,
                'posVerticalRel' => Image::POSITION_RELATIVE_TO_MARGIN,
                'wrappingStyle' => Image::WRAPPING_STYLE_SQUARE,
                'innerMargin' => 0,
            ]);
            $placeholder->addText('Fotoşəkil', ['size' => 12], ['alignment' => Jc::CENTER]);
            $placeholder->addText('üçün yer', ['size' => 12], ['alignment' => Jc::CENTER]);
            $placeholder->addText('(3.5 x 4.5 sm)', ['size' => 12], ['alignment' => Jc::CENTER]);
        }

        $section->addText($cvData['rank'] ?? '', ['bold' => true, 'size' => 16], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 80,
        ]);
        $section->addText($cvData['fullname'] ?? '', ['bold' => true, 'size' => 16], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 80,
        ]);

        $structureLine = trim($cvData['structure_label'] ?? '');
        $structureFirstLine = $structureLine;
        $structureRemainder = '';
        if ($structureLine !== '') {
            $splitPos = null;
            $marker = mb_strrpos($structureLine, '2-ci');
            if ($marker !== false) {
                $splitPos = $marker + mb_strlen('2-ci');
            } else {
                $marker = mb_strrpos($structureLine, 'İdarəsinin');
                if ($marker !== false) {
                    $splitPos = $marker + mb_strlen('İdarəsinin');
                }
            }

            if (! $splitPos && mb_strlen($structureLine) > 50) {
                $chunk = mb_substr($structureLine, 0, 50);
                $space = mb_strrpos($chunk, ' ');
                $splitPos = $space !== false ? $space : 50;
            }

            if ($splitPos) {
                $structureFirstLine = trim(mb_substr($structureLine, 0, $splitPos));
                $structureRemainder = trim(mb_substr($structureLine, $splitPos));
            }

            $section->addText($structureFirstLine, ['size' => 14, 'underline' => 'single'], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 20,
            ]);
        }

        $positionLine = trim(\Illuminate\Support\Str::lower($cvData['position_label'] ?? ''));
        $disposalTag = trim($cvData['hasActiveDisposal'] ?? '');
        if ($disposalTag !== '') {
            $positionLine = trim($positionLine.' '.$disposalTag);
        }
        $secondLine = trim($structureRemainder.' '.$positionLine);
        if ($secondLine !== '') {
            $section->addText($secondLine, ['size' => 14, 'underline' => 'single'], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 160,
            ]);
        }

        $labelStyle = ['bold' => true];
        $valueStyle = ['italic' => true];

        $birthLine = trim(
            ($cvData['birth']['day'] ?? '')
            .' '
            .\Illuminate\Support\Str::lower($cvData['birth']['month'] ?? '')
            .' '
            .(($cvData['birth']['year'] ?? '') ? ($cvData['birth']['year'].' '.__('year')) : '')
            .', '
            .($cvData['birth']['city'] ?? '')
            .' şəhəri'
        );
        $educationLine = '';
        if (! empty($cvData['education']['institution'])) {
            $educationLine =
                \Illuminate\Support\Str::ucfirst($cvData['education']['degree'] ?? '')
                .', '
                .($cvData['education']['graduation_year'] ?? '')
                .' ildə '
                .($cvData['education']['institution'] ?? '')
                .' bitirib.';
        }
        $addressLine = '';
        if (($cvData['similarity_percentage'] ?? 0) > 90) {
            $addressLine = $cvData['residental_address'] ?? '';
        } else {
            $addressLine = 'Qeyd: '.($cvData['registered_address'] ?? '')."\n"
                .'Yaş: '.($cvData['residental_address'] ?? '');
        }

        $tabStop = Converter::cmToTwip(6.2);
        $this->addInfoLines($section, 'Doğulduğu gün, ay, il və yer:', $birthLine, $labelStyle, $valueStyle, $tabStop);
        $this->addInfoLines($section, 'Təhsili:', $educationLine, $labelStyle, $valueStyle, $tabStop);
        $this->addInfoLines($section, 'Mükafatlandırılıb:', (string)($cvData['awards_count'] ?? ''), $labelStyle, $valueStyle, $tabStop);
        $this->addInfoLines($section, 'İntizam cəzaları:', (string)($cvData['punishments_count'] ?? ''), $labelStyle, $valueStyle, $tabStop);
        $this->addInfoLines($section, 'Ailə vəziyyəti:', $cvData['family_status'] ?? '', $labelStyle, $valueStyle, $tabStop);
        $this->addInfoLines($section, 'Ünvan:', $addressLine, $labelStyle, $valueStyle, $tabStop);

        $section->addText('Fəaliyyətləri barədə məlumat:', ['bold' => true]);

        $contentWidthCm = 18.5;
        $dateWidth = Converter::cmToTwip(2.8);
        $descWidth = Converter::cmToTwip($contentWidthCm - 5.6);

        $serviceTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $serviceTable->addRow();
        $serviceTable->addCell($dateWidth * 2, [
            'gridSpan' => 2,
            'valign' => VerticalJc::CENTER,
        ])->addText('Tarix (gün, ay və il)', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        $serviceTable->addCell($descWidth, [
            'vMerge' => Cell::VMERGE_RESTART,
            'valign' => VerticalJc::CENTER,
        ])->addText("İdarə, təşkilat, müəssisə, nazirlik,\nvəzifə", ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        $serviceTable->addRow();
        $serviceTable->addCell($dateWidth)->addText('daxil olduğu', ['size' => 10], ['alignment' => Jc::CENTER]);
        $serviceTable->addCell($dateWidth)->addText('çıxdığı', ['size' => 10], ['alignment' => Jc::CENTER]);
        $serviceTable->addCell($descWidth, ['vMerge' => Cell::VMERGE_CONTINUE]);

        $rows = [];
        foreach ($cvData['service_history']['military'] as $military) {
            $rows[] = [
                'join' => $military['start_date'] ?? '',
                'leave' => $military['end_date'] ?? 'hal/hazıra kimi',
                'desc' => ($military['location'] ?? '').';',
            ];
        }
        foreach ($cvData['service_history']['labor'] as $labor) {
            $rows[] = [
                'join' => $labor['join_date'] ?? '',
                'leave' => $labor['leave_date'] ?? 'hal/hazıra kimi',
                'desc' => ($labor['structure'] ?? '').';',
            ];
        }
        $targetRows = 22;
        while (count($rows) < $targetRows) {
            $rows[] = ['join' => '', 'leave' => '', 'desc' => ''];
        }

        foreach ($rows as $row) {
            $serviceTable->addRow();
            $serviceTable->addCell($dateWidth)->addText($row['join'], ['italic' => true], ['alignment' => Jc::CENTER]);
            $serviceTable->addCell($dateWidth)->addText($row['leave'], ['italic' => true], ['alignment' => Jc::CENTER]);
            $serviceTable->addCell($descWidth)->addText($row['desc'], ['italic' => true]);
        }

        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);
        $filename = 'cv_'.$personnel->id.'.docx';
        $path = $dir.'/'.$filename;

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    private function addInfoLine($section, string $label, string $value, array $labelStyle, array $valueStyle, int $tabStop): void
    {
        $run = $section->addTextRun([
            'tabs' => [new Tab(Tab::TAB_STOP_LEFT, $tabStop)],
            'indentation' => [
                'left' => $tabStop,
                'hanging' => $tabStop,
            ],
            'spaceAfter' => 0,
            'spaceBefore' => 0,
        ]);
        $run->addText($label, $labelStyle);
        $run->addText("\t");
        $run->addText($value, $valueStyle);
    }

    private function addInfoLines($section, string $label, string $value, array $labelStyle, array $valueStyle, int $tabStop): void
    {
        $lines = preg_split("/\\r\\n|\\r|\\n/", $value) ?: [''];
        foreach ($lines as $index => $line) {
            $currentLabel = $index === 0 ? $label : '';
            $this->addInfoLine($section, $currentLabel, $line, $labelStyle, $valueStyle, $tabStop);
        }
    }

    private function watermarkImagePath(): ?string
    {
        $source = public_path('assets/images/gerb.png');
        if (! is_file($source)) {
            return null;
        }

        if (! function_exists('imagecreatefrompng')) {
            return $source;
        }

        $dir = storage_path('app/tmp');
        File::ensureDirectoryExists($dir);
        $target = $dir.'/cv-watermark.png';

        $img = imagecreatefrompng($source);
        if (! $img) {
            return $source;
        }

        imagealphablending($img, false);
        imagesavealpha($img, true);
        $width = imagesx($img);
        $height = imagesy($img);
        $alphaBoost = 120;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $a = ($rgba >> 24) & 0x7F;
                $newAlpha = min(127, $a + $alphaBoost);
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;
                $gray = (int) round(0.3 * $r + 0.59 * $g + 0.11 * $b);
                $color = imagecolorallocatealpha($img, $gray, $gray, $gray, $newAlpha);
                imagesetpixel($img, $x, $y, $color);
            }
        }

        imagepng($img, $target);
        imagedestroy($img);

        return $target;
    }
}
