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
            'paternity_leave' => 'Atalıq məzuniyyəti',
            'maternity_leave' => 'Hamiləlik və doğuş məzuniyyəti',
            'unpaid_leave' => 'Ödənişsiz məzuniyyət',
            'education_leave' => 'Təhsil məzuniyyəti',
            'hire' => 'İşə qəbul',
            'transfer' => 'Başqa işə keçirilmə',
            'surname_change' => 'Soyadın dəyişdirilməsi',
            'military_gathering' => 'Hərbi toplantı',
            'termination_request' => 'Əmək müqaviləsinə xitam',
            'termination_cause' => 'Maddə ilə xitam',
        ];
    }

    /**
     * @return TemplateBlock[]
     */
    public function blocks(string $code): array
    {
        return match ($code) {
            'leave' => $this->leave(),
            'paternity_leave' => $this->paternityLeave(),
            'maternity_leave' => $this->maternityLeave(),
            'unpaid_leave' => $this->unpaidLeave(),
            'education_leave' => $this->educationLeave(),
            'hire' => $this->hire(),
            'transfer' => $this->transfer(),
            'surname_change' => $this->surnameChange(),
            'military_gathering' => $this->militaryGathering(),
            'termination_request' => $this->terminationRequest(),
            'termination_cause' => $this->terminationCause(),
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
                    'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi {{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
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
     * @return TemplateBlock[]
     */
    private function paternityLeave(): array
    {
        return $this->wrap(
            subject: 'Atalıq məzuniyyətinin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 125-ci maddəsinin 4-cü hissəsini rəhbər tutaraq',
            body: $this->leaveBody(
                grant: '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_dative }} {{ field.days }} təqvim günü müddətinə ödənişli atalıq məzuniyyəti verilsin.',
                basis: 'Əsas: {{ field.basis }}',
            ),
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function maternityLeave(): array
    {
        return $this->wrap(
            subject: 'Hamiləliyə və doğuşa görə məzuniyyətin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 125-ci maddəsinin 1-ci hissəsini rəhbər tutaraq',
            body: $this->leaveBody(
                grant: '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_dative }} {{ field.days }} təqvim günü müddətində hamiləliyə və doğuşa görə məzuniyyət verilsin.',
                basis: 'Əsas: {{ field.basis }}',
            ),
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function unpaidLeave(): array
    {
        return $this->wrap(
            subject: 'Ödənişsiz məzuniyyətin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 129-cu maddəsinin 1-ci hissəsini rəhbər tutaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_dative }}, {{ field.reason }}, {{ field.period }} tarixləri ödənişsiz məzuniyyət günləri hesab edilsin.',
                    'İşə başlama tarixi {{ field.return_date }} müəyyən edilsin.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ employee.initials_genitive }} ərizəsi.', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function educationLeave(): array
    {
        return $this->wrap(
            subject: 'Ödənişli təhsil məzuniyyətinin verilməsi haqqında',
            preamble: 'Azərbaycan Respublikası Əmək Məcəlləsinin 124-cü maddəsinin 3-cü hissəsini rəhbər tutaraq',
            body: $this->leaveBody(
                grant: '{{ employee.structure_genitive }} {{ employee.position }}, {{ field.institution }} {{ field.course }} tələbəsi {{ employee.full_name_dative }} {{ field.days }} təqvim günü müddətində ödənişli təhsil məzuniyyəti verilsin.',
                basis: 'Əsas: {{ employee.initials_genitive }} ərizəsi, {{ field.basis }}',
            ),
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function transfer(): array
    {
        return $this->wrap(
            subject: 'Başqa işə keçirilmə haqqında',
            preamble: 'Təsdiq edilmiş yeni təşkilati strukturu və ştat cədvəlini nəzərə alaraq, Azərbaycan Respublikası Əmək Məcəlləsinin 59-cu maddəsinə əsasən',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_with_suffix }} {{ field.start_date }} tarixdən {{ field.new_structure }} {{ field.new_position }} vəzifəsinə keçirilsin.',
                    'İnsan Resursları və Maliyyə, Vergi, Mühasibatlıq departamentləri bu əmrdən irəli gələn məsələləri həll etsinlər.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ employee.initials_genitive }} ərizəsi və əmək müqaviləsinə edilmiş dəyişiklik.', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function militaryGathering(): array
    {
        return $this->wrap(
            subject: 'Hərbi toplantıda iştirak barədə',
            preamble: 'Səfərbərlik və Hərbi Xidmətə Çağırış üzrə Dövlət Xidmətinin çağırış vərəqəsini nəzərə alaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_genitive }}, Azərbaycan Respublikası Əmək Məcəlləsinin 179-cu maddəsinin 2-ci hissəsinin “g” bəndinə əsasən, orta əmək haqqı ödənilməklə, {{ field.start_date }} tarixindən {{ field.end_date }} tarixinədək, {{ field.days }} təqvim günü müddətinə, {{ field.location }} keçiriləcək hərbi toplantıda iştirakına icazə verilsin.',
                    'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi {{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ field.basis }}', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * @return TemplateBlock[]
     */
    private function terminationRequest(): array
    {
        return $this->wrap(
            subject: 'Əmək müqaviləsinə xitam verilməsi haqqında',
            preamble: '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_genitive }} ərizəsini nəzərə alaraq',
            body: [
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.position }} {{ employee.full_name_with_suffix }} ilə bağlanmış əmək müqaviləsinə {{ field.legal_basis }}, {{ field.reason }}, {{ field.date }} tarixdən xitam verilsin.',
                    'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi {{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
                ]),
                TemplateBlock::paragraph('Əsas: {{ employee.initials_genitive }} ərizəsi, əmək müqaviləsi.', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * Termination for cause — a multi-paragraph narrative preamble, unnumbered
     * clauses and the control/notification lines.
     *
     * @return TemplateBlock[]
     */
    private function terminationCause(): array
    {
        return $this->wrap(
            subject: 'Əmək müqaviləsinin ləğv edilməsi haqqında',
            preamble: '{{ employee.structure_genitive }} {{ employee.position }} {{ employee.full_name_genitive }} {{ field.violation }}',
            body: [
                TemplateBlock::paragraph('{{ field.investigation }}', Paragraph::ALIGN_JUSTIFY),
                TemplateBlock::paragraph('Yuxarıda göstərilənləri nəzərə alaraq, {{ field.legal_frame }} rəhbər tutaraq', Paragraph::ALIGN_JUSTIFY),
                TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
                TemplateBlock::clauses([
                    '{{ employee.full_name_with_suffix }} ilə bağlanılmış əmək müqaviləsi, {{ field.legal_basis }}, {{ field.reason }}, {{ field.date }} tarixdən ləğv edilsin.',
                    '{{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
                    'Əmrin surəti bütün struktur bölmələrinə göndərilsin.',
                    'Əmrin icrasına nəzarəti öz üzərimdə saxlayıram.',
                ], numbered: false),
                TemplateBlock::paragraph('Əsas: {{ field.basis }}', Paragraph::ALIGN_LEFT),
            ],
        );
    }

    /**
     * Shared "grant + dates + responsible" body used by the paid-leave variants.
     *
     * @return TemplateBlock[]
     */
    private function leaveBody(string $grant, string $basis): array
    {
        return [
            TemplateBlock::paragraph('Əmr edirəm:', Paragraph::ALIGN_LEFT, bold: true),
            TemplateBlock::clauses([
                $grant,
                'Məzuniyyətin başlanma tarixi {{ field.start_date }}, məzuniyyətin bitmə tarixi {{ field.end_date }}, işə başlama tarixi {{ field.return_date }} müəyyən edilsin.',
                'Mühasibatlıq və Hesabatlıq şöbəsinin rəisi {{ field.responsible }} bu əmrdən irəli gələn məsələləri həll etsin.',
            ]),
            TemplateBlock::paragraph($basis, Paragraph::ALIGN_LEFT),
        ];
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
                TemplateBlock::paragraph($preamble, Paragraph::ALIGN_JUSTIFY),
            ],
            $body,
            [
                TemplateBlock::spacer(2),
                TemplateBlock::signature(self::SIGNATORY_TITLE, self::SIGNATORY_NAME),
            ],
        );
    }
}
