<?php

namespace App\Support\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\DefaultValueBinder;

/**
 * İdxal (import) üçün paketin adi bağlayıcısı: oxunan dəyərlər olduğu kimi qalır.
 *
 * `config/excel.php`-dəki standart bağlayıcı ({@see SafeValueBinder}) ixrac üçündür və mətnin
 * əvvəlinə apostrof əlavə edə bilər; idxalda bu, məlumatı korlayardı.
 */
class RawImportValueBinder extends DefaultValueBinder implements WithCustomValueBinder {}
