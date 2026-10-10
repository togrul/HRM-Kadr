<?php

namespace App\Support\Http;

use RuntimeException;

/**
 * Server tərəfindən açılmasına icazə verilməyən URL (daxili şəbəkə, yanlış sxem və s.).
 * Mesaj yalnız jurnal üçündür; istifadəçiyə ümumi mətn göstərilir.
 */
class UnsafeUrlException extends RuntimeException {}
