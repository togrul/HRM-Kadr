<?php

namespace Database\Seeders;

use App\Enums\StructureEnum;
use App\Models\Structure;
use Illuminate\Database\Seeder;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
      if(env('DB_DATABASE') == 'dmx_hr')
          $this->militaryStructureData();
      else
          $this->fakeStructureData();
    }

    private function fakeStructureData(): void
    {
        $structures = [
            [
                'id' => 1,
                'parent_id' => 0,
                'name' => 'Azərbaycan Respublikası Təhsil Nazirliyi',
                'shortname' => 'Təhsil Nazirliyi',
                'code' => 1,
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'name' => 'Təhsil Siyasəti və Keyfiyyətə Nəzarət Şöbəsi',
                'shortname' => 'Təhsil Siyasəti',
                'code' => 2,
            ],
            [
                'id' => 3,
                'parent_id' => 2,
                'name' => 'Tədrisin keyfiyyətinə nəzarət bölməsi',
                'shortname' => 'Keyfiyyət Nəzarəti',
                'code' => 3,
            ],
            [
                'id' => 4,
                'parent_id' => 2,
                'name' => 'Təhsil proqramlarının qiymətləndirilməsi bölməsi',
                'shortname' => 'Proqram Qiymətləndirmə',
                'code' => 4,
            ],
            [
                'id' => 5,
                'parent_id' => 1,
                'name' => 'Ali və Orta İxtisas Təhsili Şöbəsi',
                'shortname' => 'Ali Təhsil',
                'code' => 5,
            ],
            [
                'id' => 6,
                'parent_id' => 5,
                'name' => 'Universitetlər üzrə İdarə',
                'shortname' => 'Universitetlər',
                'code' => 6,
            ],
            [
                'id' => 7,
                'parent_id' => 5,
                'name' => 'Texnikum və Kolleclər üzrə İdarə',
                'shortname' => 'Texnikum və Kolleclər',
                'code' => 7,
            ],
            [
                'id' => 8,
                'parent_id' => 6,
                'name' => 'Dövlət Universitetləri şöbəsi',
                'shortname' => 'Dövlət Universitetləri',
                'code' => 8,
            ],
            [
                'id' => 9,
                'parent_id' => 6,
                'name' => 'Özəl Universitetlər şöbəsi',
                'shortname' => 'Özəl Universitetlər',
                'code' => 9,
            ],
            [
                'id' => 10,
                'parent_id' => 1,
                'name' => 'Ümumi Təhsil Şöbəsi',
                'shortname' => 'Ümumi Təhsil',
                'code' => 10,
            ],
            [
                'id' => 11,
                'parent_id' => 10,
                'name' => 'Məktəblər və Liseylər üzrə İdarə',
                'shortname' => 'Məktəblər və Liseylər',
                'code' => 11,
            ],
            [
                'id' => 12,
                'parent_id' => 10,
                'name' => 'İbtidai və Orta Təhsil Şöbəsi',
                'shortname' => 'İbtidai və Orta Təhsil',
                'code' => 12,
            ],
            [
                'id' => 13,
                'parent_id' => 11,
                'name' => 'Dövlət Məktəbləri İdarəsi',
                'shortname' => 'Dövlət Məktəbləri',
                'code' => 13,
            ],
            [
                'id' => 14,
                'parent_id' => 11,
                'name' => 'Özəl Məktəblər və Liseylər İdarəsi',
                'shortname' => 'Özəl Məktəblər',
                'code' => 14,
            ],
            [
                'id' => 15,
                'parent_id' => 1,
                'name' => 'Peşə Təhsili Şöbəsi',
                'shortname' => 'Peşə Təhsili',
                'code' => 15,
            ],
            [
                'id' => 16,
                'parent_id' => 15,
                'name' => 'Sənaye və Texniki Peşə Məktəbləri',
                'shortname' => 'Sənaye Peşə Məktəbləri',
                'code' => 16,
            ],
            [
                'id' => 17,
                'parent_id' => 15,
                'name' => 'İnformasiya Texnologiyaları Peşə Məktəbləri',
                'shortname' => 'İT Peşə Məktəbləri',
                'code' => 17,
            ],
            [
                'id' => 18,
                'parent_id' => 1,
                'name' => 'Beynəlxalq Əlaqələr Şöbəsi',
                'shortname' => 'Beynəlxalq Əlaqələr',
                'code' => 18,
            ],
            [
                'id' => 19,
                'parent_id' => 18,
                'name' => 'Xarici Universitetlərlə Əməkdaşlıq Bölməsi',
                'shortname' => 'Xarici Universitetlər',
                'code' => 19,
            ],
            [
                'id' => 20,
                'parent_id' => 18,
                'name' => 'Təhsil mübadilə proqramları şöbəsi',
                'shortname' => 'Mübadilə Proqramları',
                'code' => 20,
            ],
            [
                'id' => 21,
                'parent_id' => 1,
                'name' => 'Elm və İnnovasiya Şöbəsi',
                'shortname' => 'Elm və İnnovasiya',
                'code' => 21,
            ],
            [
                'id' => 22,
                'parent_id' => 21,
                'name' => 'Elmi Tədqiqat İnstitutları İdarəsi',
                'shortname' => 'Elmi Tədqiqat',
                'code' => 22,
            ],
            [
                'id' => 23,
                'parent_id' => 21,
                'name' => 'Texnologiya və İnnovasiya Mərkəzi',
                'shortname' => 'Texnologiya Mərkəzi',
                'code' => 23,
            ],
            [
                'id' => 24,
                'parent_id' => 1,
                'name' => 'Maliyyə və Təsərrüfat Şöbəsi',
                'shortname' => 'Maliyyə',
                'code' => 24,
            ],
            [
                'id' => 25,
                'parent_id' => 24,
                'name' => 'Büdcə Planlaşdırılması və Hesabat İdarəsi',
                'shortname' => 'Büdcə Planlaşdırma',
                'code' => 25,
            ],
            [
                'id' => 26,
                'parent_id' => 24,
                'name' => 'Təhsil İnfrastrukturunun İnkişafı İdarəsi',
                'shortname' => 'İnfrastruktur',
                'code' => 26,
            ],
            [
                'id' => 27,
                'parent_id' => 1,
                'name' => 'İnsan Resursları və Kadr Məsələləri Şöbəsi',
                'shortname' => 'İnsan Resursları',
                'code' => 27,
            ],
            [
                'id' => 28,
                'parent_id' => 27,
                'name' => 'Müəllimlərin Seçilməsi və Təlimi İdarəsi',
                'shortname' => 'Müəllim Seçimi',
                'code' => 28,
            ],
            [
                'id' => 29,
                'parent_id' => 27,
                'name' => 'Kadrların Peşəkar İnkişafı Mərkəzi',
                'shortname' => 'Kadr İnkişafı',
                'code' => 29,
            ],
        ];

        foreach ($structures as $structure) {
            Structure::updateOrCreate(['id' => $structure['id']], $structure);
        }
    }

    private function militaryStructureData(): void
    {
        $structures = [
            [
                'id' => 1,
                'parent_id' => null,
                'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti',
                'shortname' => 'PTX',
                'code' => 0,
            ],
            [
                'id' => 18,
                'parent_id' => 1,
                'name' => 'Texniki vasitələr və rabitə idarəsi',
                'shortname' => 'TVRİ',
                'code' => 18,
            ],
            [
                'id' => 12,
                'parent_id' => 1,
                'name' => 'Katiblik',
                'shortname' => 'Katiblik',
                'code' => 12,
            ],
            [
                'id' => 8,
                'parent_id' => 1,
                'name' => 'Kadrlar idarəsi',
                'shortname' => 'Kİ',
                'code' => 8,
            ],
            [
                'id' => 13,
                'parent_id' => 1,
                'name' => 'Əks-kəşfiyyat idarəsi',
                'shortname' => 'ƏKİ',
                'code' => 13,
            ],
            [
                'id' => 4,
                'parent_id' => 1,
                'name' => 'Daxili nəzarət idarəsi',
                'shortname' => 'DNİ',
                'code' => 4,
            ],
            [
                'id' => 5,
                'parent_id' => 1,
                'name' => 'Hüquq idarəsi',
                'shortname' => 'Hİ',
                'code' => 5,
            ],
            [
                'id' => 6,
                'parent_id' => 1,
                'name' => 'Təşkilat İnspeksiya idarəsi',
                'shortname' => 'Tİİ',
                'code' => 6,
            ],
            [
                'id' => 9,
                'parent_id' => 1,
                'name' => 'Maliyyə İdarəsi',
                'shortname' => 'Mİ',
                'code' => 9,
            ],
            [
                'id' => 7,
                'parent_id' => 1,
                'name' => 'Əməliyyat növbətçi idarə',
                'shortname' => 'ƏNİ',
                'code' => 7,
            ],
            [
                'id' => 10,
                'parent_id' => 1,
                'name' => 'Peşə hazırlığı idarəsi',
                'shortname' => 'PHİ',
                'code' => 10,
            ],
            [
                'id' => 11,
                'parent_id' => 1,
                'name' => 'Şəxsi heyətlə iş üzrə idarə',
                'shortname' => 'ŞHİİ',
                'code' => 11,
            ],
            [
                'id' => 14,
                'parent_id' => 1,
                'name' => 'Mühafizə Tədbir İdarəsi',
                'shortname' => 'MTİ',
                'code' => 14,
            ],
            [
                'id' => 15,
                'parent_id' => 1,
                'name' => 'Xüsusi təyinatlı idarə',
                'shortname' => 'XTİ',
                'code' => 15,
            ],
            [
                'id' => 16,
                'parent_id' => 1,
                'name' => 'Obyektlərin mühafizəsi idarəsi',
                'shortname' => 'OMİ',
                'code' => 16,
            ],
            [
                'id' => 17,
                'parent_id' => 1,
                'name' => 'Texniki müayinə idarəsi',
                'shortname' => 'TMİ',
                'code' => 17,
            ],
            [
                'id' => 20,
                'parent_id' => 1,
                'name' => 'Tibb idarəsi',
                'shortname' => 'Tİ',
                'code' => 20,
            ],
            [
                'id' => 21,
                'parent_id' => 1,
                'name' => 'Maddi Texniki Təchizat İdarəsi',
                'shortname' => 'MTTİ',
                'code' => 21,
            ],
            [
                'id' => 22,
                'parent_id' => 1,
                'name' => 'Əsaslı tikinti idarəsi',
                'shortname' => 'ƏTİ',
                'code' => 22,
            ],
            [
                'id' => 23,
                'parent_id' => 1,
                'name' => 'Xüsusi təminat şöbəsi',
                'shortname' => 'XTŞ',
                'code' => 23,
            ],
            [
                'id' => 24,
                'parent_id' => 1,
                'name' => 'Hərbi ovçuluq idarəsi',
                'shortname' => 'HOİ',
                'code' => 24,
            ],
            [
                'id' => 2,
                'parent_id' => 1,
                'name' => 'Milli Qvardiya',
                'shortname' => 'MQ',
                'code' => 0,
            ],
            [
                'id' => 25,
                'parent_id' => 21,
                'name' => 'Avtomobil parkı',
                'shortname' => 'MTTİ AP',
                'code' => 1,
            ],
            [
                'id' => 26,
                'parent_id' => 10,
                'name' => 'Təlim Tədris Mərkəzi',
                'shortname' => 'PHİ TTM',
                'code' => 1,
            ],
            [
                'id' => 19,
                'parent_id' => 1,
                'name' => 'Kompleks tədqiqatlar idarəsi',
                'shortname' => 'KTİ',
                'code' => 19,
            ],
            [
                'id' => 27,
                'parent_id' => 21,
                'name' => 'Ulduz istirahət mərkəzi',
                'shortname' => 'MTTİ Ulduz',
                'code' => 2,
            ],
            [
                'id' => 3,
                'parent_id' => 1,
                'name' => 'PTX-1',
                'shortname' => 'PTX-1',
                'code' => 1,
            ],
            [
                'id' => 30,
                'parent_id' => 1,
                'name' => 'PTX-2',
                'shortname' => 'PTX-2',
                'code' => 2,
            ],
            [
                'id' => 28,
                'parent_id' => 1,
                'name' => 'PTX-3',
                'shortname' => 'PTX-3',
                'code' => 3,
            ],
            [
                'id' => 29,
                'parent_id' => 14,
                'name' => 'Səfərlərin təşkili şöbəsi',
                'shortname' => 'MTİ-STŞ',
                'code' => 1,
            ],
            [
                'id' => 4,
                'parent_id' => 1,
                'name' => 'Daxili nəzarət idarəsi',
                'shortname' => 'DNİ',
                'code' => 4,
            ],

            [
                'id' => 31,
                'parent_id' => 2,
                'name' => 'Mühafizə taboru',
                'shortname' => 'MT',
                'code' => '1',
                'level' => StructureEnum::TABOR->value,
            ],
            [
                'id' => 32,
                'parent_id' => 31,
                'name' => '1-ci mühafizə bölüyü',
                'shortname' => '1MB',
                'code' => '2',
                'level' => StructureEnum::BOLUK->value,
            ],
            [
                'id' => 33,
                'parent_id' => 31,
                'name' => '1-ci mühafizə taqımı',
                'shortname' => '1MT',
                'code' => '3',
                'level' => StructureEnum::TAQIM->value,
            ],
            [
                'id' => 34,
                'parent_id' => 31,
                'name' => '2-ci mühafizə taqımı',
                'shortname' => '2MT',
                'code' => '3',
                'level' => StructureEnum::TAQIM->value,
            ],
        ];

        foreach ($structures as $structure) {
            Structure::updateOrCreate(['id' => $structure['id']], $structure);
        }
    }
}
