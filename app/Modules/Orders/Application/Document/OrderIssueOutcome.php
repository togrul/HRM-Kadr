<?php

namespace App\Modules\Orders\Application\Document;

/**
 * What happened when the composer tried to issue (or re-save) an order, for the UI to
 * map onto form errors, toasts and the download.
 */
final readonly class OrderIssueOutcome
{
    private const REJECTED = 'rejected';

    private const VACANCY_MISSING = 'vacancy_missing';

    private const SAVED = 'saved';

    /**
     * @param  array<string,string>  $errors  error bag key => message
     */
    private function __construct(
        private string $status,
        public array $errors = [],
        public ?string $message = null,
        public ?string $documentPath = null,
    ) {}

    /**
     * Blocked by validation: per-input errors and/or a summary toast.
     *
     * @param  array<string,string>  $errors
     */
    public static function rejected(array $errors = [], ?string $message = null): self
    {
        return new self(self::REJECTED, $errors, $message);
    }

    /**
     * A hire has no free staff-schedule slot; the author is asked to create one.
     */
    public static function vacancyMissing(string $message): self
    {
        return new self(self::VACANCY_MISSING, message: $message);
    }

    /**
     * The order was persisted; a new order also carries its stored document to download.
     */
    public static function saved(string $message, ?string $documentPath = null): self
    {
        return new self(self::SAVED, message: $message, documentPath: $documentPath);
    }

    public function isSaved(): bool
    {
        return $this->status === self::SAVED;
    }

    public function isVacancyMissing(): bool
    {
        return $this->status === self::VACANCY_MISSING;
    }
}
