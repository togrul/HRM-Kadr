<?php

namespace App\Services\Orders\Document;

use App\Services\Orders\Document\Nodes\Paragraph;

/**
 * A starter library of the customer's real order types, encoded as block lists.
 *
 * These seed the template designer (an HR user clones one and tweaks the text) and
 * prove the block model handles the structural variety of the real orders: numbered
 * clauses (məzuniyyət, işə qəbul) and unnumbered clauses with the applicant named in
 * the preamble (soyadın dəyişdirilməsi).
 */
class OrderTemplatePresets
{
    private const ORG = '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ';

    private const SIGNATORY_TITLE = [
        'Baş direktorun İnsan resursları,',
        'təşkilati idarəetmə və',
        'kommunikasiyalar üzrə müavini',
    ];

    private const SIGNATORY_NAME = 'Sübhan İsmayılov';

    /**
     * @return array<string,string> code => Azerbaijani label
     */
    public function available(): array
    {
        return [
            'leave' => 'Əmək məzuniyyəti',
            'hire' => 'İşə qəbul',
            'surname_change' => 'Soyadın dəyişdirilməsi',
        ];
    }

    /**
     * @return TemplateBlock[]
     */
    public function blocks(string $code): array
    {
        return match ($code) {
            'leave' => $this->leave(),
            'hire' => $this->hire(),
            'surname_change' => $this->surnameChange(),
            default => [],
        };
    }

    /**
     * @return TemplateBlock[]
     */
    private function leave(): array
    {
        return $this->wrap(
            subject: 'Əmək məzuniyyətinin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 138-ci maddəsinin 2-ci hissəsini rəhbər tutaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_dative }} {{ field.work_year }} iş ilinə görə {{ field.days }} təqvim günü müddətində əmək məzuniyyəti verilsin.',
                    'Məzuniyyətin başlanma tarixi {{ field.start_date }}, məzuniyyətin bitmə tarixi {{ field.end_date }}, işə başlama tarixi {{ field.return_date }} müəyyən edilsin.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ employee.initials_genitive }} ərizəsi.', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function hire(): array
    {
        return $this->wrap(
            subject: 'Əmək müqaviləsinin rəsmiləşdirilməsi haqqında',
            preamble: 'Azərbaycan Respublikasının Əmək məcəlləsinin 81-ci maddəsinin 1-ci hissəsini rəhbər tutaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.full_name_with_suffix }} {{ field.start_date }} tarixindən {{ employee.structure_dative }} {{ field.position }} peşəsinə qəbul edilsin.',
                    'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi {{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ employee.initials_genitive }} ilə bağlanılmış əmək müqaviləsi və ərizəsi.', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * Unnumbered clauses + applicant named in the preamble.
     *
     * @return TemplateBlock[]
     */
    private function surnameChange(): array
    {
        return $this->wrap(
            subject: 'Soyadın dəyişdirilməsi haqqında',
            preamble: '{{ employee.full_name_genitive }} ərizəsini və Şəxsiyyət vəsiqəsinin dəyişdirilməsini nəzərə alaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_genitive }} soyadının dəyişdirilərək “{{ field.new_surname }}” olması nəzərə alınsın.',
                    'İnsan Resursları və Maliyyə, Vergi, Mühasibatlıq departamentləri zəruri sənədlərdə dəyişikliklərin edilməsini və bu əmrdən irəli gələn digər məsələlərin həllini təmin etsinlər.',
                ], numbered: false),
                TemplateBlock::paragraph('Əsas: {{ field.basis }}', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * Wrap a per-type body with the shared org chrome (header + signatory).
     *
     * @param  TemplateBlock[]  $body
     * @return TemplateBlock[]
     */
    private function wrap(string $subject, string $preamble, array $body): array
    {
        return array_merge(
            [
                TemplateBlock::heading(self::ORG),
                TemplateBlock::spacer(),
                TemplateBlock::heading('ƏMR'),
                TemplateBlock::heading('№ {{ system.order_number }}', bold: false),
                TemplateBlock::spacer(),
                TemplateBlock::split('{{ system.organization_city }}', '{{ system.order_date }}'),
                TemplateBlock::spacer(),
                TemplateBlock::paragraph($subject, Paragraph::ALIGN_CENTER, bold: true),
                TemplateBlock::paragraph($preamble, Paragraph::ALIGN_LEFT),
            ],
            $body,
            [
                TemplateBlock::spacer(2),
                TemplateBlock::signature(self::SIGNATORY_TITLE, self::SIGNATORY_NAME),
            ],
        );
    }
}
