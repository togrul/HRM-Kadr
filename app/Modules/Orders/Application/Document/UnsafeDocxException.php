<?php

namespace App\Modules\Orders\Application\Document;

use RuntimeException;

/**
 * Yüklənən .docx arxivi təhlükəsiz ölçü hədlərini aşır (zip bomb və ya zədələnmiş fayl).
 */
class UnsafeDocxException extends RuntimeException {}
