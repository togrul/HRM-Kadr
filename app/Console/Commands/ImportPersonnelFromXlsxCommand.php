<?php

namespace App\Console\Commands;

use App\Models\CountryTranslation;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\WorkNorm;
use App\Modules\Personnel\Support\PersonnelFieldRules;
use App\Services\Staff\StaffScheduleVacancyService;
use App\Support\OrderLookupCache;
use App\Support\PersonnelDropdownCache;
use App\Support\PositionLevel;
use Carbon\Carbon;
use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

/**
 * One-off onboarding import of a company's employee list ("İşçilər.xlsx").
 *
 * The data file is passed at runtime and never committed: it holds FINs, phones
 * and addresses. Safety rails for a fleet of separate production apps:
 *  - dry-run by default; nothing is written without --apply;
 *  - --target must equal this install's database name or APP_URL host, so it cannot land on the wrong app;
 *  - --apply asks for confirmation naming the database it is about to write into;
 *  - any invalid row blocks the whole import, and the write is one transaction;
 *  - re-running is safe: rows whose tabel_no already exists are skipped.
 *
 * Missing/placeholder FİNs and addresses do not block: a FİN that is malformed, repeated
 * in the file or owned by another employee is replaced with "Z" + 6-digit tabel number,
 * and an address shorter than 3 characters with a "not known" marker. Both are counted
 * in the report so they can be corrected in the UI later.
 */
class ImportPersonnelFromXlsxCommand extends Command
{
    protected $signature = 'personnel:import-xlsx
        {file : Path to the .xlsx employee list}
        {--target= : This install database name or APP_URL host (guards against the wrong app)}
        {--parent= : Structure id under which missing departments are created (default: top level)}
        {--structure=* : Map an Excel department to an existing structure id, e.g. --structure="İnsan resursları=4"}
        {--work-norm=ştat : work_norms.name_az given to every imported employee}
        {--apply : Write to the database (without it the command only validates and reports)}
        {--force : Skip the confirmation prompt (non-interactive runs)}';

    protected $description = 'Import a company employee list (xlsx) into personnels — dry-run unless --apply.';

    /** Excel header => internal key. Columns are found by header, not by position. */
    private const HEADERS = [
        'Ad' => 'name',
        'Soyad' => 'surname',
        'Ata adı' => 'patronymic',
        'Vətəndaşlıq' => 'citizenship',
        'Cinsi' => 'gender',
        'Doğum tarixi' => 'birthdate',
        'Vəzifəsi' => 'position',
        'Struktur' => 'structure',
        'Mobil' => 'mobile',
        'FİN' => 'pin',
        'Yaşayış ünvanı' => 'residental_address',
        'Qeydiyyat ünvanı' => 'registered_address',
        'Təhsil dərəcəsi' => 'education',
        'İşə başlama tarixi' => 'join_work_date',
        'Tabel #' => 'tabel_no',
    ];

    private const GENDERS = ['kişi' => 1, 'qadın' => 2];

    private const UNKNOWN_ADDRESS = 'Məlum deyil';

    private int $placeholderPins = 0;

    /** @var array<string, int> normalised structure name => id */
    private array $structureIds = [];

    /** @var array<string, int> normalised position name => id */
    private array $positionIds = [];

    private int $placeholderAddresses = 0;

    public function handle(StaffScheduleVacancyService $staff): int
    {
        $database = (string) DB::connection()->getDatabaseName();
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $target = mb_strtolower(trim((string) $this->option('target')));

        if ($target === '' || ! in_array($target, [mb_strtolower($database), mb_strtolower($host)], true)) {
            $this->error("This install is host \"{$host}\", database \"{$database}\". Pass one of them as --target. Nothing written.");

            return self::FAILURE;
        }

        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $workNorm = WorkNorm::query()->where('name_az', $this->option('work-norm'))->first();

        if (! $workNorm) {
            $this->error('Work norm not found: '.$this->option('work-norm').'. Available (--work-norm="..."): '.WorkNorm::query()->pluck('name_az')->implode(', '));

            return self::FAILURE;
        }

        $parent = $this->option('parent') ? Structure::query()->find((int) $this->option('parent')) : null;

        if ($this->option('parent') && ! $parent) {
            $this->error('Parent structure not found: '.$this->option('parent'));

            return self::FAILURE;
        }

        try {
            $rows = $this->readRows($file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        [$records, $errors] = $this->validateRows($rows);
        $existingPeople = Personnel::withTrashed()->whereIn('tabel_no', array_column($records, 'tabel_no'))
            ->get(['tabel_no', 'surname', 'name'])->keyBy('tabel_no');

        // A taken tabel_no is only "already imported" when it is the same person; otherwise it is a clash.
        foreach ($records as $line => $record) {
            $person = $existingPeople[$record['tabel_no']] ?? null;

            if ($person && mb_strtolower($person->surname.' '.$person->name) !== mb_strtolower($record['surname'].' '.$record['name'])) {
                $errors[$line] = "Tabel # {$record['tabel_no']} already belongs to {$person->surname} {$person->name}";
            }
        }

        $existing = $existingPeople->keys()->map(fn ($t): string => (string) $t)->all();
        $toImport = array_values(array_filter($records, fn (array $r): bool => ! in_array($r['tabel_no'], $existing, true)));
        // Matched in PHP, not SQL: MySQL LOWER() and mb_strtolower() disagree on "İ".
        foreach (Structure::query()->get(['id', 'name']) as $structure) {
            $this->structureIds[$this->key($structure->name, true)] ??= (int) $structure->id;
        }

        foreach (Position::query()->get(['id', 'name']) as $position) {
            $this->positionIds[$this->key($position->name)] ??= (int) $position->id;
        }

        foreach ((array) $this->option('structure') as $mapping) {
            [$name, $id] = array_pad(explode('=', (string) $mapping, 2), 2, '');

            if (! Structure::query()->whereKey((int) $id)->exists()) {
                $this->error("--structure=\"{$mapping}\": structure id \"{$id}\" not found.");

                return self::FAILURE;
            }

            $this->structureIds[$this->key($name, true)] = (int) $id;
        }

        $newStructures = $this->missing(array_column($toImport, 'structure'), $this->structureIds, true);
        $newPositions = $this->missing(array_column($toImport, 'position'), $this->positionIds);

        $this->report($rows, $errors, $existing, $toImport, $newStructures, $newPositions);
        $this->table(['Excel struktur', 'DB structure'], collect(array_unique(array_column($records, 'structure')))
            ->map(fn (string $n): array => [$n, isset($this->structureIds[$this->key($n, true)])
                ? '['.$this->structureIds[$this->key($n, true)].'] '.Structure::query()->whereKey($this->structureIds[$this->key($n, true)])->value('name')
                : '+ new'])->values()->all());

        if ($errors !== []) {
            $this->error('Fix the errors above and re-run. Nothing written.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->warn('Dry run — nothing written. Re-run with --apply to import.');

            return self::SUCCESS;
        }

        if ($toImport === []) {
            $this->info('Nothing to import.');

            return self::SUCCESS;
        }

        $target = config('app.url').' / db: '.DB::connection()->getDatabaseName();

        if (! $this->option('force') && ! $this->confirm('Import '.count($toImport)." employees into {$target}?")) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($toImport, $parent, $workNorm, $staff): void {
            foreach ($toImport as $record) {
                $structureId = $this->structureId($record['structure'], $parent);
                $positionId = $this->positionId($record['position']);

                Personnel::query()->create([
                    'tabel_no' => $record['tabel_no'],
                    'name' => $record['name'],
                    'surname' => $record['surname'],
                    'patronymic' => $record['patronymic'],
                    'birthdate' => $record['birthdate'],
                    'gender' => $record['gender'],
                    'mobile' => $record['mobile'],
                    'nationality_id' => $record['nationality_id'],
                    'pin' => $record['pin'],
                    'residental_address' => $record['residental_address'],
                    'registered_address' => $record['registered_address'],
                    'education_degree_id' => $record['education_degree_id'],
                    'structure_id' => $structureId,
                    'position_id' => $positionId,
                    'work_norm_id' => $workNorm->id,
                    'join_work_date' => $record['join_work_date'],
                    'is_pending' => false,
                ]);

                $staff->consumeForHire($structureId, $positionId);
            }
        });

        PersonnelDropdownCache::forgetStructures();
        OrderLookupCache::bump('structures');

        $this->info(count($toImport).' employees imported into '.$target.'.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>> keyed by Excel row number
     */
    private function readRows(string $file): array
    {
        $sheet = IOFactory::load($file)->getActiveSheet()->toArray(null, true, false, false);
        $header = array_map(fn ($h): string => trim((string) $h), array_shift($sheet) ?? []);
        $columns = [];

        foreach (self::HEADERS as $label => $key) {
            $index = array_search($label, $header, true);

            if ($index === false) {
                throw new RuntimeException("Missing column \"{$label}\" in {$file}");
            }

            $columns[$key] = $index;
        }

        $rows = [];

        foreach ($sheet as $i => $cells) {
            if (array_filter($cells, fn ($c): bool => trim((string) $c) !== '') === []) {
                continue;
            }

            $rows[$i + 2] = array_map(fn (int $index) => is_string($cells[$index] ?? null) ? trim($cells[$index]) : ($cells[$index] ?? null), $columns);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function validateRows(array $rows): array
    {
        $countries = CountryTranslation::query()->get(['country_id', 'title'])
            ->mapWithKeys(fn (CountryTranslation $c): array => [mb_strtolower($c->title) => $c->country_id]);
        $degrees = EducationDegree::query()->get(['id', 'title_az'])
            ->mapWithKeys(fn (EducationDegree $d): array => [mb_strtolower((string) EducationDegree::normalizeTitle($d->title_az)) => $d->id]);

        $records = [];
        $errors = [];
        $seen = [];
        $pinCounts = array_count_values(array_map(fn (array $r): string => Str::upper((string) $r['pin']), $rows));
        $taken = Personnel::withTrashed()->whereIn('pin', array_keys($pinCounts))->pluck('tabel_no', 'pin');

        foreach ($rows as $line => $row) {
            $problems = [];
            $record = $row;
            $record['tabel_no'] = (string) $row['tabel_no'];
            $record['pin'] = Str::upper((string) $row['pin']);

            // Same 7-character rule as the personnel form (PersonnelFieldRules).
            if (! preg_match(PersonnelFieldRules::PIN_PATTERN, $record['pin'])
                || $pinCounts[$record['pin']] > 1
                || (isset($taken[$record['pin']]) && $taken[$record['pin']] !== $record['tabel_no'])) {
                $record['pin'] = 'Z'.str_pad(substr(preg_replace('/\D/', '', $record['tabel_no']) ?: (string) $line, -6), 6, '0', STR_PAD_LEFT);
                $this->placeholderPins++;
            }
            $record['gender'] = self::GENDERS[mb_strtolower((string) $row['gender'])] ?? null;
            $record['nationality_id'] = $countries[mb_strtolower((string) $row['citizenship'])] ?? null;
            $record['education_degree_id'] = $degrees[mb_strtolower((string) EducationDegree::normalizeTitle((string) $row['education']))] ?? $degrees[$this->degreeKey((string) $row['education'])] ?? null;
            $record['birthdate'] = $this->date($row['birthdate']);
            $record['join_work_date'] = $this->date($row['join_work_date']);

            foreach (['name', 'surname', 'patronymic', 'mobile', 'position', 'structure', 'tabel_no'] as $key) {
                if (blank($record[$key])) {
                    $problems[] = "{$key} is empty";
                }
            }

            foreach (['gender' => 'gender', 'nationality_id' => 'citizenship', 'education_degree_id' => 'education', 'birthdate' => 'birthdate', 'join_work_date' => 'join_work_date'] as $key => $source) {
                if ($record[$key] === null) {
                    $problems[] = 'unrecognised '.array_search($source, self::HEADERS, true)." \"{$row[$source]}\"";
                }
            }

            if (mb_strlen((string) $record['residental_address']) < 3) {
                $record['residental_address'] = self::UNKNOWN_ADDRESS;
                $this->placeholderAddresses++;
            }

            if (mb_strlen((string) $record['registered_address']) < 3) {
                $record['registered_address'] = null;
            }

            if (isset($seen[$record['tabel_no']])) {
                $problems[] = "Tabel # {$record['tabel_no']} duplicates row {$seen[$record['tabel_no']]}";
            }

            $seen[$record['tabel_no']] ??= $line;

            if ($problems !== []) {
                $errors[$line] = implode('; ', $problems);
            }

            $records[$line] = $record;
        }

        return [$records, $errors];
    }

    /** "Ali təhsil - bakalavriat" (or " — ") => "ali", "Orta ixtisas təhsili" => "orta ixtisas". */
    private function degreeKey(string $label): string
    {
        $level = Str::before((string) EducationDegree::normalizeTitle($label), ' — ');

        return trim(preg_replace('/\s+/u', ' ', str_ireplace(['təhsili', 'təhsil'], '', mb_strtolower($level))));
    }

    private function date(mixed $value): ?string
    {
        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        foreach (['d.m.Y', 'Y-m-d', 'd/m/Y'] as $format) {
            $date = DateTime::createFromFormat('!'.$format, (string) $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /** Case/space-insensitive name key; for departments a trailing "şöbəsi" is ignored. */
    private function key(string $name, bool $department = false): string
    {
        $key = trim(preg_replace('/\s+/u', ' ', mb_strtolower($name)));

        return $department ? trim(preg_replace('/\s*şöbəsi$/u', '', $key)) : $key;
    }

    /**
     * @param  array<int, string>  $wanted
     * @param  array<string, int>  $known
     * @return array<int, string>
     */
    private function missing(array $wanted, array $known, bool $department = false): array
    {
        return array_values(array_unique(array_filter($wanted, fn (string $n): bool => ! isset($known[$this->key($n, $department)]))));
    }

    private function structureId(string $name, ?Structure $parent): int
    {
        return $this->structureIds[$this->key($name, true)] ??= (int) Structure::query()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'shortname' => Str::limit($name, 64, ''),
            'code' => (int) Structure::query()->where('parent_id', $parent?->id)->max('code') + 1,
            'level' => $parent ? (int) $parent->level + 1 : 1,
            'coefficient' => 1,
        ])->id;
    }

    private function positionId(string $name): int
    {
        $key = $this->key($name);

        if (! isset($this->positionIds[$key])) {
            // positions.id is not auto-increment, but the model thinks it is: on MySQL
            // ->id after create() is lastInsertId() = 0, so keep the id we chose.
            $id = (int) Position::query()->max('id') + 1;
            Position::query()->insert(['id' => $id, 'name' => $name, 'level' => PositionLevel::guess($name)]);
            $this->positionIds[$key] = $id;
        }

        return $this->positionIds[$key];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     * @param  array<int, string>  $existing
     * @param  array<int, array<string, mixed>>  $toImport
     * @param  array<int, string>  $newStructures
     * @param  array<int, string>  $newPositions
     */
    private function report(array $rows, array $errors, array $existing, array $toImport, array $newStructures, array $newPositions): void
    {
        $this->line('Target: '.config('app.url').' / db: '.DB::connection()->getDatabaseName());
        $this->line('Parent for new structures: '.($this->option('parent') ?: 'none (top level)').'. Top-level structures:');
        Structure::query()->whereNull('parent_id')->orderBy('id')->get(['id', 'name'])
            ->each(fn (Structure $s) => $this->line("  [{$s->id}] {$s->name}"));
        $this->table(['metric', 'value'], [
            ['rows in file', count($rows)],
            ['invalid rows', count($errors)],
            ['already imported (tabel_no exists, skipped)', count($existing)],
            ['to import', count($toImport)],
            ['new structures to create', count($newStructures)],
            ['new positions to create', count($newPositions)],
            ['FİN missing/invalid → placeholder Z######', $this->placeholderPins],
            ['address missing → "'.self::UNKNOWN_ADDRESS.'"', $this->placeholderAddresses],
        ]);

        if ($errors !== []) {
            $this->table(['row', 'error'], collect($errors)->map(fn ($e, $line) => [$line, $e])->values()->all());
        }

        foreach (['New structures' => $newStructures, 'New positions' => $newPositions] as $title => $names) {
            if ($names !== []) {
                $this->line("<comment>{$title}:</comment>");
                collect($names)->each(fn (string $n) => $this->line("  + {$n}"));
            }
        }
    }
}
