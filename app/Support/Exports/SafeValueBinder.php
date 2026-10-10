<?php

namespace App\Support\Exports;

use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Excel/CSV ixracı üçün formula yeridilməsinə (CSV/formula injection) qarşı dəyər bağlayıcısı.
 *
 * - Rəqəmlər (və rəqəm kimi görünən sətirlər, məs. FromView-dan gələn "-5", "12.50") rəqəm qalır.
 * - Qalan bütün sətirlər mətn (TYPE_STRING) kimi yazılır — heç vaxt formula kimi yox.
 * - `=`, `+`, `-`, `@`, TAB, CR ilə başlayan mətnlərin əvvəlinə apostrof (') qoyulur ki,
 *   fayl CSV kimi yenidən saxlanıb açılanda da Excel/LibreOffice onu formula saymasın.
 *
 * `config/excel.php` vasitəsilə bütün ixraclar üçün standart bağlayıcıdır. İdxal (import) üçün
 * {@see RawImportValueBinder} işlədilir ki, oxunan məlumat dəyişməsin.
 */
class SafeValueBinder extends DefaultValueBinder
{
    /** @var list<string> */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function bindValue(Cell $cell, $value)
    {
        if (is_array($value)) {
            $value = (string) json_encode($value);
        }

        if (! is_string($value)) {
            return parent::bindValue($cell, $value);
        }

        $value = StringHelper::sanitizeUTF8($value);

        if ($value !== '' && static::dataTypeForValue($value) === DataType::TYPE_NUMERIC) {
            return parent::bindValue($cell, $value);
        }

        $cell->setValueExplicit(self::neutralise($value), DataType::TYPE_STRING);

        return true;
    }

    /**
     * Təhlükəli prefiksli mətni zərərsizləşdirir. Tək simvol ("-" kimi boş yer tutucu) formula
     * ola bilmədiyi üçün toxunulmur.
     */
    public static function neutralise(string $value): string
    {
        if (mb_strlen($value) > 1 && in_array($value[0], self::DANGEROUS_PREFIXES, true)) {
            return "'".$value;
        }

        return $value;
    }
}
