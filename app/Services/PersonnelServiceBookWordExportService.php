<?php

namespace App\Services;

use App\Models\Personnel;
use App\Services\PersonnelServiceBook\ServiceBookPageRenderer;
use PhpOffice\PhpWord\Shared\Converter;

class PersonnelServiceBookWordExportService extends BaseWordExportService
{
    public function __construct(private ServiceBookPageRenderer $pageRenderer) {}

    public function export(Personnel $personnel): string
    {
        [$phpWord, $section] = $this->bootWord([
            'paperSize' => 'A4',
            'marginTop' => Converter::inchToTwip(0.3),
            'marginRight' => Converter::inchToTwip(0.6),
            'marginBottom' => Converter::inchToTwip(0.3),
            'marginLeft' => Converter::inchToTwip(0.6),
        ]);

        $this->pageRenderer->renderPage1($section, $personnel);
        $section->addPageBreak();
        $this->pageRenderer->renderPage2($section, $personnel);
        $section->addPageBreak();
        $this->pageRenderer->renderPage3($section, $personnel);
        $section->addTextBreak(1);
        $this->pageRenderer->renderPage4($section, $personnel);
        $section->addPageBreak();
        $this->pageRenderer->renderPage5($section, $personnel);
        $section->addPageBreak();
        $this->pageRenderer->renderPage14($section, $personnel);
        $section->addPageBreak();
        $this->pageRenderer->renderPage15($section, $personnel);
        $section->addTextBreak(1);
        $this->pageRenderer->renderPage16($section, $personnel);

        return $this->saveWord($phpWord, 'personnel_'.$personnel->id);
    }
}
