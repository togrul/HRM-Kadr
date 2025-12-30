<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\PersonnelBusinessTrip;
use PhpOffice\PhpWord\Style\Cell;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\VerticalJc;

class PersonnelServiceBookWordExportService extends BaseWordExportService
{
    public function export(Personnel $personnel): string
    {
        [$phpWord, $section] = $this->bootWord([
            'paperSize' => 'A4',
            'marginTop' => Converter::inchToTwip(0.3),
            'marginRight' => Converter::inchToTwip(0.6),
            'marginBottom' => Converter::inchToTwip(0.3),
            'marginLeft' => Converter::inchToTwip(0.6),
        ]);

        $this->renderPage1($section, $personnel);
        $section->addPageBreak();
        $this->renderPage2($section, $personnel);
        $section->addPageBreak();
        $this->renderPage3($section, $personnel);
        $section->addTextBreak(1);
        $this->renderPage4($section, $personnel);
        $section->addPageBreak();
        $this->renderPage5($section, $personnel);
        $section->addPageBreak();
        $this->renderPage14($section, $personnel);
        $section->addPageBreak();
        $this->renderPage15($section, $personnel);
        $section->addTextBreak(1);
        $this->renderPage16($section, $personnel);

        return $this->saveWord($phpWord, 'personnel_'.$personnel->id);
    }

    private function renderPage1($section, Personnel $personnel): void
    {
        $pageWidth = $this->cm(18.0);

        $secretBoxWidth = $this->cm(4.0);
        $secretTable = $section->addTable([
            'alignment' => Jc::LEFT,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 0,
        ]);
        $secretTable->addRow();
        $secretTable->addCell($pageWidth - $secretBoxWidth, [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $secretCell = $secretTable->addCell($secretBoxWidth, [
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
        ]);
        $secretCell->addText('Məxfi', ['bold' => true, 'underline' => 'single', 'size' => 10], ['alignment' => Jc::CENTER]);
        $secretCell->addText('(doldurulduqda)', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);

        $section->addText('Azərbaycan Respublikası', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER, 'spaceAfter' => 20]);
        $section->addText('Dövlət Mühafizə Xidməti', ['bold' => true, 'size' => 12], ['alignment' => Jc::CENTER]);

        $section->addTextBreak(3);
        $title = $this->spaceLetters('XİDMƏT DƏFTƏRÇƏSİ');
        $section->addText($title, ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $section->addTextBreak(2);
        $numberTable = $section->addTable(['alignment' => Jc::CENTER, 'cellMargin' => 0]);
        $numberTable->addRow();
        $numberTable->addCell($this->cm(4.0))->addText('Şəxsi nömrəsi', ['size' => 9, 'bold' => true], ['alignment' => Jc::RIGHT]);
        $numberTable->addCell($this->cm(6.5), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $section->addTextBreak(1);
        $this->addUnderlinedLine($section, $personnel->surname ?? '', '( soyadı,', $this->cm(14.0));
        $this->addUnderlinedLine($section, trim(($personnel->name ?? '').' '.($personnel->patronymic ?? '')), 'adı və atasının adı )', $this->cm(14.0));

        $section->addTextBreak(1);

        $rankTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);
        $rankTable->addRow();
        $rankTable->addCell($this->cm(3.5))->addText('Hərbi və ya xüsusi rütbə', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $rankTable->addCell($this->cm(6.0))->addText('əmr kim tərəfindən verilib,əmrin №-si və tarixi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $rankTable->addCell($this->cm(3.5))->addText('Hərbi və ya xüsusi rütbə', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $rankTable->addCell($this->cm(6.0))->addText('əmr kim tərəfindən verilib,əmrin №-si və tarixi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $rankChunks = $personnel->ranksASC->chunk(18);
        $maxRows = 18;
        for ($i = 0; $i < $maxRows; $i++) {
            $rankTable->addRow();
            if (isset($rankChunks[0][$i])) {
                $rankTable->addCell($this->cm(3.5))->addText($rankChunks[0][$i]->rank->name ?? '', ['size' => 10], ['alignment' => Jc::LEFT]);
                $rankTable->addCell($this->cm(6.0))->addText('AR PTX rəisinin əmri №'.$rankChunks[0][$i]->order_no.' '.$rankChunks[0][$i]->order_date->format('d.m.Y'), ['size' => 10], ['alignment' => Jc::LEFT]);
            } else {
                $rankTable->addCell($this->cm(3.5))->addText('');
                $rankTable->addCell($this->cm(6.0))->addText('');
            }
            if (isset($rankChunks[1][$i])) {
                $rankTable->addCell($this->cm(3.5))->addText($rankChunks[1][$i]->rank->name ?? '', ['size' => 10], ['alignment' => Jc::LEFT]);
                $rankTable->addCell($this->cm(6.0))->addText($rankChunks[1][$i]->order_given_by.', '.$rankChunks[1][$i]->order_no.' '.$rankChunks[1][$i]->order_date->format('d.m.Y'), ['size' => 10], ['alignment' => Jc::LEFT]);
            } else {
                $rankTable->addCell($this->cm(3.5))->addText('');
                $rankTable->addCell($this->cm(6.0))->addText('');
            }
        }

        $section->addTextBreak(1);

        $footerTable = $section->addTable(['alignment' => Jc::CENTER, 'cellMargin' => 0]);
        $footerTable->addRow();
        $leftCell = $footerTable->addCell((int) ($pageWidth * 0.35), ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $leftInner = $leftCell->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        $leftInner->addRow();
        $leftInner->addCell($this->cm(2.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'valign' => VerticalJc::BOTTOM,
        ])->addText('');
        $leftInner->addCell($this->cm(5.5), ['valign' => VerticalJc::BOTTOM])->addText('şəxsi №-li jetonu aldım', ['size' => 11], ['alignment' => Jc::LEFT]);

        $middleCell = $footerTable->addCell((int) ($pageWidth * 0.3), ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $middleInner = $middleCell->addTable(['alignment' => Jc::CENTER, 'cellMargin' => 0]);
        $middleInner->addRow();
        $middleInner->addCell($this->cm(0.6), ['valign' => VerticalJc::BOTTOM])->addText('"', ['size' => 11], ['alignment' => Jc::CENTER]);
        $middleInner->addCell($this->cm(1.6), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'valign' => VerticalJc::BOTTOM,
        ])->addText('');
        $middleInner->addCell($this->cm(0.6), ['valign' => VerticalJc::BOTTOM])->addText('"', ['size' => 11], ['alignment' => Jc::CENTER]);
        $middleInner->addCell($this->cm(2.8), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'valign' => VerticalJc::BOTTOM,
        ])->addText('');
        $middleInner->addCell($this->cm(0.8), ['valign' => VerticalJc::BOTTOM])->addText('20', ['size' => 11], ['alignment' => Jc::CENTER]);
        $middleInner->addCell($this->cm(1.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'valign' => VerticalJc::BOTTOM,
        ])->addText('');
        $middleInner->addCell($this->cm(1.0), ['valign' => VerticalJc::BOTTOM])->addText('ildə', ['size' => 11], ['alignment' => Jc::LEFT]);

        $rightCell = $footerTable->addCell((int) ($pageWidth * 0.35), ['borderSize' => 0, 'borderColor' => 'FFFFFF']);
        $this->addSignatureLine($rightCell, $this->cm(5.5));
    }

    private function renderPage2($section, Personnel $personnel): void
    {
        $col1Width = $this->cm(2.0);
        $col2Width = $this->cm(3.4);
        $valueWidth = $this->cm(12.6);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $this->addMergedLabelRow(
            $table,
            '1. Anadan olduğu gün, ay və il',
            optional($personnel->birthdate)->format('d.m.Y'),
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(40)
        );

        $birthPlace = trim(($personnel->idDocuments?->bornCountry?->title ?? '').','.$personnel->idDocuments?->bornCity?->name);
        $this->addMergedLabelRow(
            $table,
            "2. Anadan olduğu yer\n(doldurulduğu günə qədər inzibati bölgü üzrə)",
            $birthPlace,
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(70),
            11
        );

        $this->addMergedLabelRow(
            $table,
            '3. Milliyəti',
            $personnel->nationality?->title ?? '',
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(20)
        );

        $this->addMergedLabelRow(
            $table,
            '4. Sosial mənşəyi',
            $personnel->socialOrigin?->name ?? '',
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(20)
        );

        $educationRowHeight = $this->rowHeight(290);
        $table->addRow($educationRowHeight);
        $labelCell = $table->addCell($col1Width, [
            'valign' => VerticalJc::CENTER,
            'vMerge' => Cell::VMERGE_RESTART,
        ]);
        $labelCell->addText('5. Təhsili', ['bold' => true, 'size' => 10]);

        $civilLabelCell = $table->addCell($col2Width, [
            'valign' => VerticalJc::CENTER,
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        $civilLabelCell->addText('a) Mülki', ['bold' => true, 'size' => 10]);
        $civilLabelCell->addText('(nə vaxt və hansı təhsil müəssisələrini bitirib; ixtisası)', ['size' => 9]);

        $civilValueCell = $table->addCell($valueWidth, [
            'valign' => VerticalJc::CENTER,
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ]);
        $this->addEducationList($civilValueCell, $personnel, false);

        $table->addRow($educationRowHeight);
        $table->addCell($col1Width, [
            'valign' => VerticalJc::CENTER,
            'vMerge' => Cell::VMERGE_CONTINUE,
        ]);

        $militaryLabelCell = $table->addCell($col2Width, ['valign' => VerticalJc::CENTER]);
        $militaryLabelCell->addText('b) Hərbi (xüsusi)', ['bold' => true, 'size' => 10]);
        $militaryLabelCell->addText('(nə vaxt və hansı təhsil müəssisələrini və kursları bitirib; ixtisası)', ['size' => 9]);

        $militaryValueCell = $table->addCell($valueWidth, ['valign' => VerticalJc::CENTER]);
        $this->addEducationList($militaryValueCell, $personnel, true);

        $languages = $personnel->foreignLanguages
            ? $personnel->foreignLanguages
                ->map(fn ($lang) => $lang->language?->name.' - '.$lang->knowledge_status)
                ->filter()
                ->implode(', ')
            : '';

        $this->addMergedLabelRow(
            $table,
            '6. Hansı xarici dilləri bilir',
            $languages,
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(60)
        );

        $degrees = $personnel->degreeAndNames
            ? $personnel->degreeAndNames->map(function ($degree) {
                $date = optional($degree->given_date)->format('d.m.Y');
                return trim(($degree->degreeAndName?->name ?? '').', '.$degree->science.' - '.$date);
            })->filter()->implode("\n")
            : '';

        $this->addMergedLabelRow(
            $table,
            '7. Elmi dərəcələri, elmi adı və verildiyi tarix',
            $degrees,
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(60)
        );

        $this->addMergedLabelRow(
            $table,
            '8. Hansı elmi əsərləri və ixtiraları var',
            $personnel->scientific_works_inventions ?? '',
            $col1Width + $col2Width,
            $valueWidth,
            $this->rowHeight(50)
        );
    }

    private function renderPage3($section, Personnel $personnel): void
    {
        $dateWidth = $this->cm(3.2);
        $companyWidth = $this->cm(8.2);
        $positionWidth = $this->cm(3.4);

        $section->addText('9. Əmək fəaliyyəti', ['bold' => true, 'size' => 11], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 80,
        ]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow($this->rowHeight(10));
        $table->addCell($dateWidth * 2, [
            'gridSpan' => 2,
            'valign' => VerticalJc::CENTER,
        ])->addText('Tarix', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $companyHeader = $table->addCell($companyWidth, [
            'vMerge' => Cell::VMERGE_RESTART,
            'valign' => VerticalJc::CENTER,
        ]);
        $companyHeader->addText('İş yeri', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $companyHeader->addText('(müəssisənin,təşkilatın və s. adı)', ['size' => 9], ['alignment' => Jc::CENTER]);
        $companyHeader->addText('və harada yerləşir', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $companyHeader->addText('(şəhər, rayon ,kənd)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $positionHeader = $table->addCell($positionWidth, [
            'vMerge' => Cell::VMERGE_RESTART,
            'valign' => VerticalJc::CENTER,
        ]);
        $positionHeader->addText('Vəzifəsi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $table->addRow($this->rowHeight(30));
        $fromCell = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $fromCell->addText('Nə vaxtdan', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $fromCell->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);
        $toCell = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $toCell->addText('Nə vaxtadək', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $toCell->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell($companyWidth, ['vMerge' => Cell::VMERGE_CONTINUE]);
        $table->addCell($positionWidth, ['vMerge' => Cell::VMERGE_CONTINUE]);

        if ($personnel->laborActivities && $personnel->laborActivities->isNotEmpty()) {
            foreach ($personnel->laborActivities as $labor) {
                $table->addRow($this->rowHeight(26));
                $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER])->addText(optional($labor->join_date)->format('d.m.Y') ?? '', ['size' => 9], ['alignment' => Jc::CENTER]);
                $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER])->addText(optional($labor->leave_date)->format('d.m.Y') ?? '', ['size' => 9], ['alignment' => Jc::CENTER]);
                $table->addCell($companyWidth, ['valign' => VerticalJc::CENTER])->addText($labor->company_name ?? '', ['size' => 9]);
                $table->addCell($positionWidth, ['valign' => VerticalJc::CENTER])->addText($labor->position ?? '', ['size' => 9]);
            }
        } else {
            $table->addRow($this->rowHeight(24));
            $table->addCell($dateWidth)->addText('');
            $table->addCell($dateWidth)->addText('');
            $table->addCell($companyWidth)->addText('Əmək fəaliyyəti yoxdur', ['size' => 9]);
            $table->addCell($positionWidth)->addText('', ['size' => 9]);
        }

        for ($i = 0; $i < 2; $i++) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($dateWidth)->addText('');
            $table->addCell($dateWidth)->addText('');
            $table->addCell($companyWidth)->addText('');
            $table->addCell($positionWidth)->addText('');
        }
    }

    private function renderPage4($section, Personnel $personnel): void
    {
        $dateWidth = $this->cm(2.8);
        $positionWidth = $this->cm(4.0);
        $orgWidth = $this->cm(5.6);
        $orderWidth = $this->cm(3.6);

        $section->addText('10. Silahlı Qüvvələrdə və hüquq-mühafizə orqanlarında xidməti', ['bold' => true, 'size' => 11], [
            'alignment' => Jc::LEFT,
            'spaceAfter' => 80,
        ]);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow($this->rowHeight(50));
        $fromHeader = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $fromHeader->addText('Nə', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $fromHeader->addText('vaxtdan', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $fromHeader->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $toHeader = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $toHeader->addText('Nə', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $toHeader->addText('vaxtadək', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $toHeader->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $table->addCell($positionWidth, ['valign' => VerticalJc::CENTER])
            ->addText('Vəzifəsi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $orgHeader = $table->addCell($orgWidth, ['valign' => VerticalJc::CENTER]);
        $orgHeader->addText('Orqanın, hissənin, dəstənin,', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orgHeader->addText('təhsil müəssisəsinin adı', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orderHeader = $table->addCell($orderWidth, ['valign' => VerticalJc::CENTER]);
        $orderHeader->addText('əmr kim', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orderHeader->addText('tərəfindən', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orderHeader->addText('verilib,', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orderHeader->addText('əmrin №-si və', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $orderHeader->addText('tarixi', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $rows = [];
        foreach ($personnel->military ?? [] as $military) {
            $rows[] = [
                'start' => optional($military->start_date)->format('d.m.Y'),
                'end' => optional($military->end_date)->format('d.m.Y'),
                'position' => $military->rank?->name ?? '',
                'org' => $military->location ?? '',
                'order' => optional($military->given_date)->format('d.m.Y'),
            ];
        }
        foreach ($personnel->specialServices ?? [] as $special) {
            $rows[] = [
                'start' => optional($special->join_date)->format('d.m.Y'),
                'end' => optional($special->leave_date)->format('d.m.Y'),
                'position' => $special->position ?? '',
                'org' => $special->company_name ?? '',
                'order' => trim(($special->order_given_by ?? '')
                    .(! empty($special->order_no) ? ', '.$special->order_no : '')
                    .(! empty($special->order_date) ? ', '.\Carbon\Carbon::parse($special->order_date)->format('d.m.Y') : '')),
            ];
        }

        if ($rows) {
            foreach ($rows as $row) {
                $table->addRow($this->rowHeight(26));
                $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER])->addText($row['start'] ?? '', ['size' => 9], ['alignment' => Jc::CENTER]);
                $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER])->addText($row['end'] ?? '', ['size' => 9], ['alignment' => Jc::CENTER]);
                $table->addCell($positionWidth, ['valign' => VerticalJc::CENTER])->addText($row['position'] ?? '', ['size' => 9]);
                $table->addCell($orgWidth, ['valign' => VerticalJc::CENTER])->addText($row['org'] ?? '', ['size' => 9]);
                $table->addCell($orderWidth, ['valign' => VerticalJc::CENTER])->addText($row['order'] ?? '', ['size' => 9]);
            }
        }

        for ($i = 0; $i < 2; $i++) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($dateWidth)->addText('');
            $table->addCell($dateWidth)->addText('');
            $table->addCell($positionWidth)->addText('');
            $table->addCell($orgWidth)->addText('');
            $table->addCell($orderWidth)->addText('');
        }
    }

    private function renderPage5($section, Personnel $personnel): void
    {
        $docWidth = $this->cm(18.0);
        $docCol = $this->cm(9.8);
        $coeffWidth = $this->cm(1.8);
        $dateWidth = $this->cm(3.2);

        $section->addText(
            '11. Pensiya təyin edilərkən xidmət illərinin güzəştli hesablanmasına hüquq verən xidmət dövrləri',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 80]
        );

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow($this->rowHeight(40));
        $table->addCell($docCol, ['valign' => VerticalJc::CENTER])->addText('Sənədin adı, №-si və tarixi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell($coeffWidth, ['valign' => VerticalJc::CENTER])->addText('əmsal', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);

        $fromHeader = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $fromHeader->addText('Nə vaxtdan', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $fromHeader->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $toHeader = $table->addCell($dateWidth, ['valign' => VerticalJc::CENTER]);
        $toHeader->addText('Nə vaxtadək', ['size' => 9, 'bold' => true], ['alignment' => Jc::CENTER]);
        $toHeader->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);
        for ($i = 0; $i < 20; $i++) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($docCol)->addText('');
            $table->addCell($coeffWidth)->addText('');
            $table->addCell($dateWidth)->addText('');
            $table->addCell($dateWidth)->addText('');
        }
        $section->addTextBreak();
        $section->addText(
            '12. Xidməti vəzifələrini yerinə yetirərkən yaralanması, kontuziyaları (nə vaxt və harada); onların xüsusiyyətləri',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::LEFT, 'spaceAfter' => 80]
        );

        $injuryTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        if ($personnel->injuries && $personnel->injuries->isNotEmpty()) {
            foreach ($personnel->injuries as $injury) {
                $injuryTable->addRow($this->rowHeight(24));
                $injuryTable->addCell($docWidth)->addText(
                    trim($injury->injury_type.' , '.optional($injury->date_time)->format('d.m.Y').' , '.$injury->location.' , '.$injury->description),
                    ['size' => 9]
                );
            }
            $injuryTable->addRow($this->rowHeight(24));
            $injuryTable->addCell($docWidth)->addText('');
        } else {
            for ($i = 0; $i < 3; $i++) {
                $injuryTable->addRow($this->rowHeight(24));
                $injuryTable->addCell($docWidth)->addText('');
            }
        }
    }

    private function renderPage14($section, Personnel $personnel): void
    {
        $col1 = $this->cm(6.0);
        $col2 = $this->cm(6.0);
        $col3 = $this->cm(6.0);

        $section->addText(
            '13. Azərbaycan Respublikasının, yaxud xarici dövlətlərin hansı orden və medalları ilə təltif olunub (həmçinin Azərbaycan Milli Qəhrəmanı və s. adlar qeyd olunmalıdır).',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow($this->rowHeight(50));
        $header1 = $table->addCell($col1, ['valign' => VerticalJc::CENTER]);
        foreach ($this->splitLines("Ordenin, medalların adı və hansı fəxri ada\nlayiq görülüb") as $line) {
            $header1->addText($line, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        }
        $header2 = $table->addCell($col2, ['valign' => VerticalJc::CENTER]);
        foreach ($this->splitLines("Nə üçün təltif olunmuşdur (döyüşdə\nfərqlənməyə, uzun müddətli xidmətə görə)") as $line) {
            $header2->addText($line, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        }
        $header3 = $table->addCell($col3, ['valign' => VerticalJc::CENTER]);
        foreach ($this->splitLines("Təltif və fəxri adın\nverilməsi haqqında\nfərmanın, əmrin kim\ntərəfindən verilmişdir,\nəmrin №-si və tarixi.") as $line) {
            $header3->addText($line, ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        }

        $awards = $personnel->awards ?? collect();
        foreach ($awards as $award) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($col1, ['valign' => VerticalJc::CENTER])->addText($award->award?->name ?? '', ['size' => 9]);
            $table->addCell($col2, ['valign' => VerticalJc::CENTER])->addText($award->reason ?? '', ['size' => 9]);
            $orderDate = $this->formatDate($award->order_date);
            $orderParts = [];
            if (! empty($award->order_given_by)) {
                $orderParts[] = $award->order_given_by;
            }
            if (! empty($award->order_no)) {
                $orderParts[] = '№'.$award->order_no;
            }
            if ($orderDate) {
                $orderParts[] = $orderDate;
            }
            $table->addCell($col3, ['valign' => VerticalJc::CENTER])->addText(implode(', ', $orderParts), ['size' => 9]);
        }

        $remaining = max(0, 18 - $awards->count());
        for ($i = 0; $i < $remaining; $i++) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($col1)->addText('');
            $table->addCell($col2)->addText('');
            $table->addCell($col3)->addText('');
        }
    }

    private function renderPage15($section, Personnel $personnel): void
    {
        $docWidth = $this->cm(18.0);
        $col1 = $this->cm(8.0);
        $col2 = $this->cm(3.4);
        $col3 = $this->cm(3.3);
        $col4 = $this->cm(3.3);

        $section->addText(
            '14. Xarici ezamiyyətlər',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow($this->rowHeight(60));
        $table->addCell($col1, ['valign' => VerticalJc::CENTER])->addText('Harada, nə məqsədlə olub', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $header2 = $table->addCell($col2, ['valign' => VerticalJc::CENTER]);
        $header2->addText('Kimin əmri ilə', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $header2->addText('(əmrin №-si və tarixi)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $header3 = $table->addCell($col3, ['valign' => VerticalJc::CENTER]);
        $header3->addText('Nə vaxtdan', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $header3->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);
        $header4 = $table->addCell($col4, ['valign' => VerticalJc::CENTER]);
        $header4->addText('Nə vaxtadək', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $header4->addText('(gün,ay,il)', ['size' => 9], ['alignment' => Jc::CENTER]);

        $trips = ($personnel->businessTrips ?? collect())
            ->filter(fn ($trip) => $trip->order?->order_type_id === PersonnelBusinessTrip::FOREIGN_BUSINESS_TRIP)
            ->values();
        foreach ($trips as $trip) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($col1, ['valign' => VerticalJc::CENTER])->addText(trim(($trip->location ? $trip->location.', ' : '').$trip->description), ['size' => 9]);
            $table->addCell($col2, ['valign' => VerticalJc::CENTER])->addText(trim($trip->order?->given_by.' '.$trip->order_no.' '.optional($trip->order_date)->format('d.m.Y')), ['size' => 9]);
            $table->addCell($col3, ['valign' => VerticalJc::CENTER])->addText(optional($trip->start_date)->format('d.m.Y'), ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell($col4, ['valign' => VerticalJc::CENTER])->addText(optional($trip->end_date)->format('d.m.Y'), ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        $remaining = max(0, 18 - $trips->count());
        for ($i = 0; $i < $remaining; $i++) {
            $table->addRow($this->rowHeight(24));
            $table->addCell($col1)->addText('');
            $table->addCell($col2)->addText('');
            $table->addCell($col3)->addText('');
            $table->addCell($col4)->addText('');
        }

        $section->addTextBreak(1);
        $run = $section->addTextRun(['alignment' => Jc::LEFT, 'spaceAfter' => 80]);
        $run->addText('15. Hansı seçki orqanlarına seçilmişdir', ['bold' => true, 'size' => 11]);
        $run->addText(' (harada və nə vaxt)', ['size' => 11]);

        $elections = $personnel->elections ?? collect();
        $electionRows = [];
        if ($elections->isNotEmpty()) {
            foreach ($elections as $election) {
                $electionRows[] = trim($election->election_type.' - '.$election->location.' - '.\Carbon\Carbon::parse($election->elected_date)->format('d.m.Y'));
            }
            $electionRows[] = '';
        } else {
            $electionRows = array_fill(0, 3, '');
        }

        $lineTable = $section->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        foreach ($electionRows as $index => $text) {
            $lineTable->addRow($this->rowHeight($index === 0 ? 18 : 24));
            $lineTable->addCell($docWidth, [
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'valign' => VerticalJc::BOTTOM,
            ])->addText($text, ['size' => 9]);
        }

        $section->addTextBreak(1);

        $run = $section->addTextRun(['alignment' => Jc::LEFT, 'spaceAfter' => 80]);
        $run->addText('16. Əsirlikdə olubmu', ['bold' => true, 'size' => 11]);
        $run->addText(' (hansı şəraitdə, harada, nə vaxt əsir düşüb və azad olunub)', ['size' => 11]);

        $captives = $personnel->captives ?? collect();
        $captiveRows = [];
        if ($captives->isNotEmpty()) {
            foreach ($captives as $captive) {
                $line = \Carbon\Carbon::parse($captive->taken_captive_date)->format('d.m.Y')
                    .' tarixində '.$captive->location.' ərazisində '
                    .$captive->condition.' əsirlikdə olub.';
                if (! empty($captive->release_date)) {
                    $line .= ' '.\Carbon\Carbon::parse($captive->release_date)->format('d.m.Y').' tarixində əsirlikdən azad olub.';
                }
                $captiveRows[] = $line;
            }
            $captiveRows[] = '';
        } else {
            $captiveRows = array_fill(0, 6, '');
        }

        $captiveTable = $section->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        foreach ($captiveRows as $index => $text) {
            $captiveTable->addRow($this->rowHeight($index === 0 ? 18 : 24));
            $captiveTable->addCell($docWidth, [
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'valign' => VerticalJc::BOTTOM,
            ])->addText($text, ['size' => 9]);
        }
    }

    private function renderPage16($section, Personnel $personnel): void
    {
        $docWidth = $this->cm(18.0);
        $labelWidth = $this->cm(5.0);
        $valueWidth = $this->cm(13.0);

        $fmTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);
        $fmTable->addRow($this->rowHeight(175));
        $fmTable->addCell($labelWidth, ['valign' => VerticalJc::CENTER])->addText(
            '17. Atasının və anasının soyadı, adı, atasının adı və yaşadığı ünvan',
            ['bold' => true, 'size' => 11],
            ['alignment' => Jc::LEFT]
        );
        $valueCell = $fmTable->addCell($valueWidth, ['valign' => VerticalJc::TOP]);

        $fatherMother = $personnel->fatherMother ?? collect();
        if ($fatherMother->isNotEmpty()) {
            foreach ($fatherMother as $fm) {
                $valueCell->addText(
                    ($fm->kinship?->{"name_".config('app.locale')} ?? '').' - '.$fm->fullname.', '.$fm->residental_address,
                    ['size' => 10]
                );
            }
        } else {
            $lines = $valueCell->addTable(['cellMargin' => 0]);
            for ($i = 0; $i < 7; $i++) {
                $lines->addRow($this->rowHeight(25));
                $lines->addCell($valueWidth, [
                    'borderBottomSize' => $i < 6 ? 6 : 0,
                    'borderBottomColor' => '000000',
                ])->addText('');
            }
        }

        $section->addTextBreak(1);
        $statusTable = $section->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        $statusTable->addRow();
        $statusTable->addCell($this->cm(3.5))->addText('18. Ailə vəziyyəti', ['bold' => true, 'size' => 11]);
        $statusTable->addCell($this->cm(2.5))->addText('(subay, evli)', ['size' => 11]);
        $statusTable->addCell($this->cm(6.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText($personnel->idDocuments?->is_married ? 'Evli' : 'Subay', ['size' => 10]);

        $section->addTextBreak(1);
        $familyTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
            'alignment' => Jc::CENTER,
        ]);
        $col1 = $this->cm(6.0);
        $col2 = $this->cm(3.0);
        $col3 = $this->cm(4.0);
        $col4 = $this->cm(5.0);

        $familyTable->addRow($this->rowHeight(45));
        $cell1 = $familyTable->addCell($col1, ['valign' => VerticalJc::CENTER]);
        $cell1->addText('Həyat yoldaşı və uşaqlarının', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell1->addText('soyadı, adı və atasının adı', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell2 = $familyTable->addCell($col2, ['valign' => VerticalJc::CENTER]);
        $cell2->addText('Qohumluq', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell2->addText('dərəcəsi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell3 = $familyTable->addCell($col3, ['valign' => VerticalJc::CENTER]);
        $cell3->addText('Nə vaxt və', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell3->addText('harada anadan', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell4 = $familyTable->addCell($col4, ['valign' => VerticalJc::CENTER]);
        $cell4->addText('Evlənmək və doğum haqqında', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $cell4->addText('şəhadətnamənin nömrəsi', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        foreach ($personnel->wifeChildren ?? [] as $wf) {
            $familyTable->addRow($this->rowHeight(30));
            $familyTable->addCell($col1, ['valign' => VerticalJc::CENTER])->addText($wf->fullname ?? '', ['size' => 9]);
            $familyTable->addCell($col2, ['valign' => VerticalJc::CENTER])->addText($wf->kinship?->{"name_".config('app.locale')} ?? '', ['size' => 9]);
            $familyTable->addCell($col3, ['valign' => VerticalJc::CENTER])->addText(trim(optional($wf->birthdate)->format('d.m.Y').', '.$wf->birth_place), ['size' => 9]);
            $certCell = $familyTable->addCell($col4, ['valign' => VerticalJc::TOP]);
            if (! empty($wf->birth_certificate_number)) {
                $certCell->addText('Doğum şəhadətnaməsi #: '.$wf->birth_certificate_number, ['size' => 9]);
            }
            if (! empty($wf->marriage_certificate_number)) {
                $certCell->addText('Evlilik şəhadətnaməsi #: '.$wf->marriage_certificate_number, ['size' => 9]);
            }
        }

        for ($i = 0; $i < 2; $i++) {
            $familyTable->addRow($this->rowHeight(24));
            $familyTable->addCell($col1)->addText('');
            $familyTable->addCell($col2)->addText('');
            $familyTable->addCell($col3)->addText('');
            $familyTable->addCell($col4)->addText('');
        }

        $section->addTextBreak(1);
        $section->addText('19. Yaşadığı ünvan', ['bold' => true, 'size' => 11], ['alignment' => Jc::LEFT, 'spaceAfter' => 10]);
        $addressTable = $section->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        if (! empty($personnel->residental_address)) {
            $addressTable->addRow($this->rowHeight(24));
            $addressTable->addCell($docWidth, [
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
                'valign' => VerticalJc::BOTTOM,
            ])->addText($personnel->residental_address, ['size' => 10], ['spaceAfter' => 0, 'spaceBefore' => 0]);
        } else {
            for ($i = 0; $i < 2; $i++) {
                $addressTable->addRow($this->rowHeight(24));
                $addressTable->addCell($docWidth, [
                    'borderBottomSize' => 6,
                    'borderBottomColor' => '000000',
                    'valign' => VerticalJc::BOTTOM,
                ])->addText('');
            }
        }

        $section->addTextBreak(1);
        $footer = $section->addTable(['alignment' => Jc::RIGHT, 'cellMargin' => 0]);
        $footer->addRow();
        $footer->addCell($this->cm(6.5))->addText('Xidmət siyahısı tərtib olunub', ['size' => 10]);
        $footer->addCell($this->cm(0.6))->addText('"', ['size' => 10], ['alignment' => Jc::CENTER]);
        $footer->addCell($this->cm(1.6), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $footer->addCell($this->cm(0.6))->addText('"', ['size' => 10], ['alignment' => Jc::CENTER]);
        $footer->addCell($this->cm(2.8), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $footer->addCell($this->cm(0.8))->addText('20', ['size' => 10], ['alignment' => Jc::CENTER]);
        $footer->addCell($this->cm(1.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $footer->addCell($this->cm(1.0))->addText('ildə', ['size' => 10], ['alignment' => Jc::LEFT]);

        $section->addTextBreak(1);
        $this->addPersonnelSignatureBlock($section, 'Kadrlar idarəsinin (şöbəsinin)');
        $this->addPersonnelSignatureBlock($section, 'Kadrlar idarəsinin (şöbəsinin)', false);

        $section->addText(
            'Xidmət siyahısında qeyd olunan məlumatları təsdiq etmək üçün hərbi qulluqçunun imzası',
            ['size' => 10],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80]
        );

        $grid = $section->addTable(['alignment' => Jc::CENTER, 'cellMargin' => 0]);
        for ($i = 0; $i < 4; $i++) {
            $grid->addRow($this->rowHeight(22));
            $cell1 = $grid->addCell($this->cm(9.0));
            $this->addDateSignatureLine($cell1);
            $cell2 = $grid->addCell($this->cm(9.0));
            $this->addDateSignatureLine($cell2);
        }
    }

    private function addPersonnelSignatureBlock($section, string $label, bool $includePosition = true): void
    {
        $table = $section->addTable(['alignment' => Jc::LEFT, 'cellMargin' => 0]);
        $table->addRow();
        $table->addCell($this->cm(7.0))->addText($label, ['bold' => true, 'size' => 10]);
        $cell = $table->addCell($this->cm(11.0));
        $inner = $cell->addTable(['cellMargin' => 0]);
        if ($includePosition) {
            $inner->addRow();
            $inner->addCell($this->cm(11.0), [
                'borderBottomSize' => 6,
                'borderBottomColor' => '000000',
            ])->addText('');
            $inner->addRow();
            $inner->addCell($this->cm(11.0))->addText('(vəzifəsi)', ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        }
        $inner->addRow();
        $inner->addCell($this->cm(11.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $inner->addRow();
        $inner->addCell($this->cm(11.0))->addText('(rütbəsi, imzası, soyadı)', ['italic' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
    }

    private function addDateSignatureLine($container): void
    {
        $table = $container->addTable(['cellMargin' => 0, 'alignment' => Jc::LEFT]);
        $table->addRow();
        $table->addCell($this->cm(0.6))->addText('"', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell($this->cm(1.4), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $table->addCell($this->cm(0.6))->addText('"', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell($this->cm(2.4), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $table->addCell($this->cm(0.8))->addText('20', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell($this->cm(1.0), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $table->addCell($this->cm(0.8))->addText('ildə', ['size' => 10], ['alignment' => Jc::CENTER]);
        $table->addCell($this->cm(1.4), [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
    }

    private function addUnderlinedLine($section, string $text, string $label, int $width): void
    {
        $table = $section->addTable(['alignment' => Jc::CENTER]);
        $table->addRow();
        $table->addCell($width, [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText($text, ['italic' => true], ['alignment' => Jc::CENTER]);
        $table->addRow();
        $table->addCell($width)->addText($label, ['italic' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
    }

    private function addSignatureLine($container, int $width): void
    {
        $table = $container->addTable(['alignment' => Jc::CENTER, 'cellMargin' => 0]);
        $table->addRow();
        $table->addCell($width, [
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
        ])->addText('');
        $table->addRow();
        $table->addCell($width)->addText('imzası', ['italic' => true, 'size' => 11], ['alignment' => Jc::CENTER]);
    }

    private function spaceLetters(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (! $chars) {
            return $text;
        }
        return implode(' ', $chars);
    }

    private function addMergedLabelRow($table, string $label, string $value, int $labelWidth, int $valueWidth, int $heightTwip, int $labelSize = 10): void
    {
        $table->addRow($heightTwip);
        $labelCell = $table->addCell($labelWidth, ['valign' => VerticalJc::TOP, 'gridSpan' => 2]);
        $valueCell = $table->addCell($valueWidth, ['valign' => VerticalJc::TOP]);

        foreach ($this->splitLines($label) as $line) {
            $labelCell->addText($line, ['bold' => true, 'size' => $labelSize]);
        }

        foreach ($this->splitLines($value) as $line) {
            $valueCell->addText($line, ['size' => 10]);
        }
    }

    private function splitLines(string $text): array
    {
        $lines = preg_split("/\\r\\n|\\r|\\n/", $text) ?: [''];
        return array_values(array_filter($lines, fn ($line) => $line !== ''));
    }

    private function addEducationList($cell, Personnel $personnel, bool $isMilitary): void
    {
        $entries = [];

        if ($personnel->education && $personnel->education->is_military === $isMilitary) {
            $entries[] = [
                'name' => $personnel->education->institution?->name,
                'specialty' => $personnel->education->specialty,
                'admission' => $personnel->education->admission_year,
                'graduated' => $personnel->education->graduated_year,
            ];
        }

        foreach ($personnel->extraEducations ?? [] as $extraEdu) {
            if ($extraEdu->is_military === $isMilitary) {
                $entries[] = [
                    'name' => $extraEdu->institution?->name,
                    'specialty' => $extraEdu->education_program_name,
                    'admission' => $extraEdu->admission_year,
                    'graduated' => $extraEdu->graduated_year,
                ];
            }
        }

        foreach ($entries as $entry) {
            $line = trim(($entry['name'] ?? '').', '.$entry['specialty'].' - '.$this->formatDate($entry['admission']).' - '.$this->formatDate($entry['graduated']));
            if ($line === ',  -  -') {
                continue;
            }
            $cell->addText($line, ['size' => 11]);
        }
    }

}
