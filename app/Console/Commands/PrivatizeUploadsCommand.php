<?php

namespace App\Console\Commands;

use App\Support\Uploads\PrivateFiles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Həssas yükləmələri veb ilə açıq `public` diskdən (storage/app/public → /storage/*) özəl
 * `local` diskə köçürür və qeydlərdəki disk sütununu yeniləyir.
 *
 * Nisbi yol dəyişmir (məs. `leaves/abc.pdf`), ona görə yol sütunlarına toxunulmur; disk
 * sütunu olan cədvəllərdə `public` → `local` yazılır. İdempotentdir: artıq köçürülmüş fayl
 * yenidən köçürülmür, hər iki diskdə eyni fayl varsa public nüsxə silinir. Yalnız
 * verilənlər bazasında istinad olunan fayllar köçürülür.
 */
class PrivatizeUploadsCommand extends Command
{
    protected $signature = 'files:privatize
        {--dry-run : Heç nə yazmadan nə köçürüləcəyini göstər}';

    protected $description = 'Həssas yükləmələri (sənəd, sertifikat, foto, portfel, onboarding) public diskdən özəl local diskə köçürür';

    /**
     * cədvəl => [yol sütunu, disk sütunu|null]
     *
     * @var array<string, array{0: string, 1: ?string}>
     */
    public const TARGETS = [
        'leaves' => ['document_path', null],
        'training_delivery_records' => ['certificate_path', null],
        'professional_record_attachments' => ['file_path', 'disk'],
        'onboarding_document_templates' => ['file_path', 'disk'],
        'personnels' => ['photo', null],
        'personnel_documents' => ['file', null],
        'candidate_documents' => ['file_path', 'disk'],
    ];

    /** @var array{moved: int, deduplicated: int, already_private: int, missing: int, failed: int, rows_updated: int} */
    private array $stats = ['moved' => 0, 'deduplicated' => 0, 'already_private' => 0, 'missing' => 0, 'failed' => 0, 'rows_updated' => 0];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach (self::TARGETS as $table => [$pathColumn, $diskColumn]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $pathColumn)) {
                continue;
            }

            $query = DB::table($table)->select(['id', $pathColumn])->whereNotNull($pathColumn)->where($pathColumn, '!=', '');

            if ($diskColumn !== null && Schema::hasColumn($table, $diskColumn)) {
                // Disk sütunu olan cədvəldə yalnız public (və ya boş — köhnə standart public idi) qeydlər.
                $query->where(fn ($q) => $q->whereNull($diskColumn)->orWhere($diskColumn, '')->orWhere($diskColumn, PrivateFiles::LEGACY_DISK));
            } else {
                $diskColumn = null;
            }

            $query->orderBy('id')->chunkById(200, function ($rows) use ($table, $pathColumn, $diskColumn, $dryRun): void {
                foreach ($rows as $row) {
                    $ok = $this->moveFile((string) $row->{$pathColumn}, $dryRun);

                    if ($ok && $diskColumn !== null && ! $dryRun) {
                        $this->stats['rows_updated'] += DB::table($table)->where('id', $row->id)->update([$diskColumn => PrivateFiles::DISK]);
                    }
                }
            });
        }

        $this->table(array_keys($this->stats), [array_values($this->stats)]);

        if ($dryRun) {
            $this->info('Dry-run: heç nə dəyişdirilmədi.');
        }

        return $this->stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return bool fayl özəl diskdədir (və ya artıq heç yerdə yoxdur və qeyd yenilənə bilər)
     */
    private function moveFile(string $path, bool $dryRun): bool
    {
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            $this->stats['failed']++;
            $this->warn("Yanlış yol ötürüldü: {$path}");

            return false;
        }

        $public = Storage::disk(PrivateFiles::LEGACY_DISK);
        $private = Storage::disk(PrivateFiles::DISK);
        $onPublic = $public->exists($path);
        $onPrivate = $private->exists($path);

        if (! $onPublic) {
            $this->stats[$onPrivate ? 'already_private' : 'missing']++;

            return true;
        }

        if ($dryRun) {
            $this->line("→ {$path}");
            $this->stats[$onPrivate ? 'deduplicated' : 'moved']++;

            return true;
        }

        try {
            if ($onPrivate) {
                if ($private->size($path) !== $public->size($path)) {
                    $this->stats['failed']++;
                    $this->warn("Hər iki diskdə fərqli fayl var, toxunulmadı: {$path}");

                    return false;
                }

                $public->delete($path);
                $this->stats['deduplicated']++;

                return true;
            }

            $stream = $public->readStream($path);
            if ($stream === null || ! $private->writeStream($path, $stream)) {
                throw new RuntimeException('yazıla bilmədi');
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($private->size($path) !== $public->size($path)) {
                $private->delete($path);
                throw new RuntimeException('ölçü uyğun gəlmir');
            }

            $public->delete($path);
            $this->stats['moved']++;

            return true;
        } catch (Throwable $e) {
            $this->stats['failed']++;
            $this->warn("Köçürülmədi ({$e->getMessage()}): {$path}");

            return false;
        }
    }
}
