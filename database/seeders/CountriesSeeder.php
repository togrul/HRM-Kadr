<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountryTranslation;
use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sql = file_get_contents(base_path('countries.sql'));
        if (! $sql) {
            return;
        }

        $countryInsert = $this->extractInsert($sql, 'countries');
        $translationInsert = $this->extractInsert($sql, 'country_translations');

        $countryRows = $this->parseRows($countryInsert, 2);
        if ($countryRows) {
            $countries = array_map(
                fn (array $row) => [
                    'id' => (int) $row[0],
                    'code' => (string) $row[1],
                ],
                $countryRows
            );

            Country::upsert($countries, ['id'], ['code']);
        }

        $translationRows = $this->parseRows($translationInsert, 4);
        if ($translationRows) {
            $translations = array_map(
                fn (array $row) => [
                    'id' => (int) $row[0],
                    'country_id' => (int) $row[1],
                    'locale' => (string) $row[2],
                    'title' => (string) $row[3],
                ],
                $translationRows
            );

            CountryTranslation::upsert($translations, ['id'], ['country_id', 'locale', 'title']);
        }
    }

    private function extractInsert(string $sql, string $table): string
    {
        $pattern = sprintf('/INSERT INTO `%s`[^;]+;/s', preg_quote($table, '/'));
        if (! preg_match($pattern, $sql, $matches)) {
            return '';
        }

        return $matches[0];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseRows(string $insertSql, int $expectedColumns): array
    {
        if ($insertSql === '') {
            return [];
        }

        $rows = [];
        if (! preg_match_all('/\(([^)]+)\)/', $insertSql, $matches)) {
            return [];
        }

        foreach ($matches[1] as $tuple) {
            $fields = str_getcsv($tuple, ',', "'");
            if (count($fields) !== $expectedColumns) {
                continue;
            }

            $rows[] = array_map('trim', $fields);
        }

        return $rows;
    }
}
