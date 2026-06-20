<?php

namespace Tests\Unit\Support;

use App\Support\Language\AzerbaijaniDeclension;
use PHPUnit\Framework\TestCase;

class AzerbaijaniDeclensionTest extends TestCase
{
    private AzerbaijaniDeclension $d;

    protected function setUp(): void
    {
        parent::setUp();
        $this->d = new AzerbaijaniDeclension;
    }

    /** The possessive suffixes take the -n- buffer and have fixed forms. */
    public function test_possessive_oglu_qizi_forms(): void
    {
        $this->assertSame('oğlunun', $this->d->genitive('oğlu'));
        $this->assertSame('oğluna', $this->d->dative('oğlu'));
        $this->assertSame('oğlunu', $this->d->accusative('oğlu'));
        $this->assertSame('qızının', $this->d->genitive('qızı'));
        $this->assertSame('qızına', $this->d->dative('qızı'));
    }

    /**
     * Real names pulled from the customer's sample orders, declined the way the
     * documents actually write them.
     *
     * @dataProvider realNameCases
     */
    public function test_full_name_declension_matches_real_orders(string $nominative, string $case, string $expected): void
    {
        $this->assertSame($expected, $this->d->declineName($nominative, $case));
    }

    public static function realNameCases(): array
    {
        return [
            // dative (… oğluna … verilsin)
            ['Cəfərova Fidan Məsud oğlu', 'dative', 'Cəfərova Fidan Məsud oğluna'],
            ['Dadaşov Loğman Ramiz oğlu', 'dative', 'Dadaşov Loğman Ramiz oğluna'],
            ['Fərzəliyev Həsən Elnur oğlu', 'dative', 'Fərzəliyev Həsən Elnur oğluna'],
            // genitive (… oğlunun … iştirakına / ərizəsini)
            ['Rzayev Murad Elşən oğlu', 'genitive', 'Rzayev Murad Elşən oğlunun'],
            ['Həsənova Ləman Asif qızı', 'genitive', 'Həsənova Ləman Asif qızının'],
            // short "Name Surname" forms decline the trailing surname
            ['Mahir Sadıxov', 'genitive', 'Mahir Sadıxovun'],
            ['Ləman Bababəyli', 'genitive', 'Ləman Bababəylinin'],
            ['Namiq Məmmədli', 'genitive', 'Namiq Məmmədlinin'],
        ];
    }

    /**
     * @dataProvider surnameGenitiveCases
     */
    public function test_surname_genitive_vowel_harmony(string $surname, string $expected): void
    {
        $this->assertSame($expected, $this->d->genitive($surname));
    }

    public static function surnameGenitiveCases(): array
    {
        return [
            ['Sadıxov', 'Sadıxovun'],      // last vowel o → u, consonant
            ['Fərzəliyev', 'Fərzəliyevin'], // last vowel e → i, consonant
            ['Bayramov', 'Bayramovun'],     // o → u
            ['Bababəyli', 'Bababəylinin'],  // ends in vowel i → n-buffer
            ['Məmmədli', 'Məmmədlinin'],    // ends in vowel i
            ['İsmayılzadə', 'İsmayılzadənin'], // ends in vowel ə → i, n-buffer
            ['Qurbanova', 'Qurbanovanın'],  // ends in vowel a → ı, n-buffer
        ];
    }

    /**
     * @dataProvider dativeCases
     */
    public function test_dative_vowel_harmony(string $word, string $expected): void
    {
        $this->assertSame($expected, $this->d->dative($word));
    }

    public static function dativeCases(): array
    {
        return [
            ['Sadıxov', 'Sadıxova'],    // back, consonant → a
            ['Elçin', 'Elçinə'],        // front, consonant → ə
            ['Bakı', 'Bakıya'],         // back, vowel → ya
            ['ölkə', 'ölkəyə'],         // front, vowel → yə
        ];
    }

    /**
     * @dataProvider locativeAblativeCases
     */
    public function test_locative_and_ablative(string $word, string $loc, string $abl): void
    {
        $this->assertSame($loc, $this->d->locative($word));
        $this->assertSame($abl, $this->d->ablative($word));
    }

    public static function locativeAblativeCases(): array
    {
        return [
            ['Bakı', 'Bakıda', 'Bakıdan'],
            ['Xəzər', 'Xəzərdə', 'Xəzərdən'],
        ];
    }

    /**
     * Org-unit / position names carry the 3rd-person possessive suffix and inflect
     * with the -n- buffer — the genitive form is how structures appear in clauses.
     *
     * @dataProvider possessiveCases
     */
    public function test_possessive_noun_declension(string $phrase, string $genitive, string $dative): void
    {
        $this->assertSame($genitive, $this->d->possessiveGenitive($phrase));
        $this->assertSame($dative, $this->d->possessiveDative($phrase));
    }

    public static function possessiveCases(): array
    {
        return [
            ['şöbəsi', 'şöbəsinin', 'şöbəsinə'],
            ['anbarı', 'anbarının', 'anbarına'],
            ['mərkəzi', 'mərkəzinin', 'mərkəzinə'],
            ['departamenti', 'departamentinin', 'departamentinə'],
            // multi-word unit names inflect the trailing possessive head
            ['Keşlə Qeyri-Qida Satış mərkəzi', 'Keşlə Qeyri-Qida Satış mərkəzinin', 'Keşlə Qeyri-Qida Satış mərkəzinə'],
            ['Mərkəzi Qida Məhsulları anbarı', 'Mərkəzi Qida Məhsulları anbarının', 'Mərkəzi Qida Məhsulları anbarına'],
        ];
    }

    public function test_initials_without_vowel_are_left_untouched(): void
    {
        // No harmonizable vowel — engine must not invent a suffix.
        $this->assertSame('N', $this->d->genitive('N'));
    }

    public function test_empty_input_is_safe(): void
    {
        $this->assertSame('', $this->d->genitive(''));
        $this->assertSame('', $this->d->nameDative('   '));
    }
}
