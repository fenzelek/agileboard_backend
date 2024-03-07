<?php

namespace Tests\Unit\App\Modules\SaleReport\Services\Optima\GenericFieldFiller;

use App\Modules\SaleReport\Services\Optima\GenericFieldFiller;
use Tests\TestCase;

class FieldsWithDefaultValuesTest extends TestCase
{
    /** @test */
    public function it_returns_valid_number_of_fields()
    {
        $filler = new GenericFieldFiller();

        $this->assertCount(132, $filler->fieldsWithDefaultValues());
    }

    /** @test */
    public function it_returns_same_field_names()
    {
        $filler = new GenericFieldFiller();

        $this->assertSame(array_keys($this->getExpectedFields()), array_keys($filler->fieldsWithDefaultValues()));
    }

    /** @test */
    public function it_returns_valid_values_for_fields()
    {
        $filler = new GenericFieldFiller();

        $expected_fields = $this->getExpectedFields();
        $fields = $filler->fieldsWithDefaultValues();

        foreach ($expected_fields as $field => $value) {
            $this->assertSame($value, $fields[$field], 'Field ' . $field . ' has valid value');
        }
    }

    protected function getExpectedFields()
    {
        return [
            'ID' => '',
            'GRUPA' => '',
            'DATA_TR' => '',
            'DATA_WYST' => '',
            'IK' => '',
            'DOKUMENT' => '',
            'KOREKTA_DO' => '',
            'TYP' => '',
            'KOREKTA' => '',
            'ZAKUP' => '',
            'ODLICZENIA' => '',
            'KASA' => '',
            'KON' => '',
            'K_NAZWA1' => '',
            'K_NAZWA2' => '',
            'K_ADRES1' => '',
            'K_KODP' => '',
            'K_MIASTO' => '',
            'NIP' => '',
            'KONTO' => '',
            'FIN' => '',
            'EXPORT' => '',
            'ID_O' => '',
            'KOD_O' => '',
            'OPIS' => '',
            'NETTO1' => '0.00',
            'NETTO2' => '0.00',
            'NETTO3' => '0.00',
            'NETTO4' => '0.00',
            'NETTO5' => '0.00',
            'VAT3' => '0.00',
            'VAT4' => '0.00',
            'VAT5' => '0.00',
            'ST5' => '',
            'USLUGI' => '',
            'PRODUKCJA' => '',
            'ROZLICZONO' => '',
            'PLATNOSC' => '',
            'TERMIN' => '',
            'BRUTTO' => '',
            'ZAPLATA' => '',
            'ID_FPP' => 0,
            'NR_FPP' => 0,
            'WARTOSC_Z' => '0.00',
            'CLO' => '0.00',
            'AKCYZA' => '0.00',
            'POD_IMP' => '0.00',
            'USER' => '',
            'KAUCJA' => '0.00',
            'NETTO6' => '0.00',
            'NETTO7' => '0.00',
            'VAT6' => '0.00',
            'VAT7' => '0.00',
            'X1' => '0.00',
            'X2' => '0.00',
            'X3' => '0.00',
            'X4' => '0.00',
            'X5' => '0.00',
            'WARTOSC_S' => '0.00',
            'VAT_S' => '0.00',
            'FLAGA_1' => 0,
            'STAWKA_1' => '0.00',
            'NETTO_1' => '0.00',
            'VAT_1' => '0.00',
            'FLAGA_2' => 0,
            'STAWKA_2' => '0.00',
            'NETTO_2' => '0.00',
            'VAT_2' => '0.00',
            'FLAGA_3' => 0,
            'STAWKA_3' => '0.00',
            'NETTO_3' => '0.00',
            'VAT_3' => '0.00',
            'FLAGA_4' => 0,
            'STAWKA_4' => '0.00',
            'NETTO_4' => '0.00',
            'VAT_4' => '0.00',
            'FLAGA_5' => 0,
            'STAWKA_5' => '0.00',
            'NETTO_5' => '0.00',
            'VAT_5' => '0.00',
            'FLAGA_6' => 0,
            'STAWKA_6' => '0.00',
            'NETTO_6' => '0.00',
            'VAT_6' => '0.00',
            'FLAGA_7' => 0,
            'STAWKA_7' => '0.00',
            'NETTO_7' => '0.00',
            'VAT_7' => '0.00',
            'FLAGA_8' => 0,
            'STAWKA_8' => '0.00',
            'NETTO_8' => '0.00',
            'VAT_8' => '0.00',
            'FLAGA_9' => 0,
            'STAWKA_9' => '0.00',
            'NETTO_9' => '0.00',
            'VAT_9' => '0.00',
            'FLAGA_10' => 0,
            'STAWKA_10' => '0.00',
            'NETTO_10' => '0.00',
            'VAT_10' => '0.00',
            'FLAGA_11' => 0,
            'STAWKA_11' => '0.00',
            'NETTO_11' => '0.00',
            'VAT_11' => '0.00',
            'FLAGA_12' => 0,
            'STAWKA_12' => '0.00',
            'NETTO_12' => '0.00',
            'VAT_12' => '0.00',
            'FLAGA_13' => 0,
            'STAWKA_13' => '0.00',
            'NETTO_13' => '0.00',
            'VAT_13' => '0.00',
            'FLAGA_14' => 0,
            'STAWKA_14' => '0.00',
            'NETTO_14' => '0.00',
            'VAT_14' => '0.00',
            'FLAGA_15' => 0,
            'STAWKA_15' => '0.00',
            'NETTO_15' => '0.00',
            'VAT_15' => '0.00',
            'FLAGA_16' => 0,
            'STAWKA_16' => '0.00',
            'NETTO_16' => '0.00',
            'VAT_16' => '0.00',
            'FLAGA_17' => 0,
            'STAWKA_17' => '0.00',
            'NETTO_17' => '0.00',
            'VAT_17' => '0.00',
            'FLAGA_18' => 0,
            'STAWKA_18' => '0.00',
            'NETTO_18' => '0.00',
            'VAT_18' => '0.00',
        ];
    }
}
