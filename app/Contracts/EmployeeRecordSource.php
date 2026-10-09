<?php

namespace App\Contracts;

/**
 * Bir modulun əməkdaş haqqında saxladığı qeydlərin sayı.
 *
 * Hər modul bunu öz Application/Infrastructure qatında həyata keçirir və öz service
 * provider-ində `employee.record_sources` teqi ilə qeydə alır. Beləliklə, məsələn, işə
 * qəbul əmrinin təsdiqini geri alan Orders modulu başqa modulun cədvəllərini oxumadan
 * "bu əməkdaşın artıq qeydləri varmı?" sualına cavab alır.
 *
 * İşə qəbulun özünün yaratdığı qeydlər (avtomatik əmək haqqı layihəsi, adaptasiya
 * hadisəsi) sayılmır — onları geri alma prosesi özü təmizləyir.
 */
interface EmployeeRecordSource
{
    /** Bütün implementasiyaların qeydə alındığı teq. */
    public const TAG = 'employee.record_sources';

    /**
     * Qeyd növü => say. Yalnız sayı sıfırdan böyük olan növlər qaytarılır.
     * Açar sabitdir (məs. `payslips`); onun mətnini istifadə edən modul tərcümə edir.
     *
     * @return array<string, int>
     */
    public function recordCounts(int $personnelId, string $tabelNo): array;
}
