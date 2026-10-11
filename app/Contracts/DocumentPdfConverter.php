<?php

namespace App\Contracts;

/**
 * Hazır .docx faylını PDF-ə çevirən ortaq səth (headless LibreOffice). Əmrlərin yekun PDF-i
 * ilə eyni çevirici; Orders modulundan kənar modullar (məs. Əməkdaş 360 ixracı) bundan asılıdır.
 * Çevirici olmayan hostda isAvailable() false qaytarır və çağıran PDF təklif etmir.
 */
interface DocumentPdfConverter
{
    public function isAvailable(): bool;

    /**
     * @return string|null yaradılmış PDF-in mütləq yolu (çağıran silir) və ya uğursuzluqda null
     */
    public function convert(string $docxPath): ?string;
}
