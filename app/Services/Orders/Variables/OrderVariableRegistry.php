<?php

namespace App\Services\Orders\Variables;

/**
 * The single catalog of variables a template author may insert — the source of
 * truth for the designer palette AND for validating that every `{{ placeholder }}`
 * in a template is actually resolvable (no more silent "___" at generation time).
 *
 * Three namespaces:
 *  - system.*   — order/org context (number, date, signatory…), resolved per order
 *  - employee.* — from the selected personnel (see OrderEmployeeVariableResolver),
 *                 incl. Azerbaijani case variants
 *  - field.*    — author-defined form inputs, declared per template (dynamic), so
 *                 they are always resolvable and not listed statically here
 */
class OrderVariableRegistry
{
    /**
     * @return array<int,array{key:string,label:string,group:string,sample:string}>
     */
    public function all(): array
    {
        return array_merge($this->systemVariables(), $this->employeeVariables());
    }

    /**
     * @return array<string,array<int,array{key:string,label:string,group:string,sample:string}>>
     */
    public function grouped(): array
    {
        $grouped = [];
        foreach ($this->all() as $variable) {
            $grouped[$variable['group']][] = $variable;
        }

        return $grouped;
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return array_map(static fn (array $v) => $v['key'], $this->all());
    }

    /**
     * A static catalog key OR any author-declared field.* key is resolvable.
     *
     * @param  string[]  $fieldKeys  field.* keys declared by the current template
     */
    public function isResolvable(string $key, array $fieldKeys = []): bool
    {
        return in_array($key, $this->keys(), true) || in_array($key, $fieldKeys, true);
    }

    /**
     * @return array<int,array{key:string,label:string,group:string,sample:string}>
     */
    private function systemVariables(): array
    {
        return [
            $this->def('system.order_number', 'Əmrin nömrəsi', 'Sistem', '135-K'),
            $this->def('system.order_date', 'Əmrin tarixi', 'Sistem', '06 iyun 2026-cı il'),
            $this->def('system.order_subject', 'Əmrin mövzusu', 'Sistem', 'Əmək məzuniyyətinin verilməsi haqqında'),
            $this->def('system.order_basis', 'Əsas', 'Sistem', 'F.M.Cəfərovanın ərizəsi'),
            $this->def('system.organization_name', 'Təşkilatın adı', 'Sistem', '“DİNÇER VƏ CARÇIOĞLU” BİRGƏ MÜƏSSİSƏSİ'),
            $this->def('system.organization_city', 'Şəhər', 'Sistem', 'Bakı şəhəri'),
            $this->def('system.signatory_full_name', 'İmzalayan', 'Sistem', 'Sübhan İsmayılov'),
            $this->def('system.signatory_title', 'İmzalayanın vəzifəsi', 'Sistem', 'Baş direktorun İnsan resursları üzrə müavini'),
        ];
    }

    /**
     * @return array<int,array{key:string,label:string,group:string,sample:string}>
     */
    private function employeeVariables(): array
    {
        return [
            $this->def('employee.full_name', 'Tam ad (S.A.A.)', 'İşçi', 'Bayramov Ruslan Bəxtiyar'),
            $this->def('employee.full_name_with_suffix', 'Tam ad + oğlu/qızı', 'İşçi', 'Bayramov Ruslan Bəxtiyar oğlu'),
            $this->def('employee.full_name_dative', 'Tam ad – yönlük hal', 'İşçi', 'Bayramov Ruslan Bəxtiyar oğluna'),
            $this->def('employee.full_name_genitive', 'Tam ad – yiyəlik hal', 'İşçi', 'Bayramov Ruslan Bəxtiyar oğlunun'),
            $this->def('employee.initials', 'İnisiallar', 'İşçi', 'R.B.Bayramov'),
            $this->def('employee.initials_genitive', 'İnisiallar – yiyəlik hal', 'İşçi', 'R.B.Bayramovun'),
            $this->def('employee.surname', 'Soyad', 'İşçi', 'Bayramov'),
            $this->def('employee.name', 'Ad', 'İşçi', 'Ruslan'),
            $this->def('employee.patronymic', 'Ata adı', 'İşçi', 'Bəxtiyar'),
            $this->def('employee.gender_suffix', 'oğlu/qızı', 'İşçi', 'oğlu'),
            $this->def('employee.tabel_no', 'Tabel nömrəsi', 'İşçi', '1024'),
            $this->def('employee.position', 'Vəzifə', 'İşçi', 'sürücü-ekspeditor'),
            $this->def('employee.position_genitive', 'Vəzifə – yiyəlik hal', 'İşçi', 'sürücü-ekspeditorunun'),
            $this->def('employee.position_dative', 'Vəzifə – yönlük hal', 'İşçi', 'sürücü-ekspeditoruna'),
            $this->def('employee.structure', 'Struktur', 'İşçi', 'Mərkəzi Qida Məhsulları anbarı'),
            $this->def('employee.structure_genitive', 'Struktur – yiyəlik hal', 'İşçi', 'Mərkəzi Qida Məhsulları anbarının'),
            $this->def('employee.structure_dative', 'Struktur – yönlük hal', 'İşçi', 'Mərkəzi Qida Məhsulları anbarına'),
        ];
    }

    /**
     * @return array{key:string,label:string,group:string,sample:string}
     */
    private function def(string $key, string $label, string $group, string $sample): array
    {
        return ['key' => $key, 'label' => $label, 'group' => $group, 'sample' => $sample];
    }
}
