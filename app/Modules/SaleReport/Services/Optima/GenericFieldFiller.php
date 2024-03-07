<?php

namespace App\Modules\SaleReport\Services\Optima;

class GenericFieldFiller
{
    /**
     * Start number for FLAGA_, STAWKA_, NETTO_, VAT_ fields (FLAGA_1, NETTO_1, ...).
     */
    const TAXES_START_NUMBER = 1;

    /**
     * End number for FLAGA_, STAWKA_, NETTO_, VAT_ fields (FLAGA_18, NETTO_18, ...).
     */
    const TAXES_END_NUMBER = 18;

    /**
     * Get all row fields with default values (Some fields won't be ever filled with other values
     * and some will be filled only in some cases).
     *
     * @return array
     */
    public function fieldsWithDefaultValues()
    {
        $fields = array_fill_keys($this->fields(), '');

        foreach ($fields as $key => $value) {
            $fields[$key] = $this->getDefaultFieldValue($key, $value);
        }

        return $fields;
    }

    /**
     * Get default value for given field.
     *
     * @param string $key
     * @param string $value
     *
     * @return int|string
     */
    protected function getDefaultFieldValue($key, $value)
    {
        if (in_array($key, $this->decimalFields())) {
            return number_format_output(0, '.');
        }

        if (in_array($key, $this->integerFields())) {
            return 0;
        }

        if (in_array($key, $this->stringFields())) {
            return '';
        }

        return $value;
    }

    /**
     * Get fields that are decimals.
     *
     * @return array
     */
    protected function decimalFields()
    {
        $fields = [
            'NETTO1',
            'NETTO2',
            'NETTO3',
            'NETTO4',
            'NETTO5',
            'VAT3',
            'VAT4',
            'VAT5',
            'WARTOSC_Z',
            'CLO',
            'AKCYZA',
            'POD_IMP',
            'KAUCJA',
            'NETTO6',
            'NETTO7',
            'VAT6',
            'VAT7',
            'X1',
            'X2',
            'X3',
            'X4',
            'X5',
            'WARTOSC_S',
            'VAT_S',
        ];

        return array_merge($fields, $this->getTaxesOtherFields());
    }

    /**
     * Get fields that are integers.
     *
     * @return array
     */
    protected function integerFields()
    {
        $fields = [
            'ID_FPP',
            'NR_FPP',
        ];

        return array_merge($fields, $this->getTaxesFlagFields());
    }

    /**
     * Get fields that are strings.
     *
     * @return array
     */
    protected function stringFields()
    {
        return [
            'IK',
            'KON',
            'KONTO',
            'ID_O',
            'KOD_O',
            'OPIS',
            'ST5',
            'USLUGI',
            'PRODUKCJA',
            'USER',
        ];
    }

    /**
     * Return all available fields.
     *
     * @return array
     */
    protected function fields()
    {
        $fields = [
            'ID',
            'GRUPA',
            'DATA_TR',
            'DATA_WYST',
            'IK',
            'DOKUMENT',
            'KOREKTA_DO',
            'TYP',
            'KOREKTA',
            'ZAKUP',
            'ODLICZENIA',
            'KASA',
            'KON',
            'K_NAZWA1',
            'K_NAZWA2',
            'K_ADRES1',
            'K_KODP',
            'K_MIASTO',
            'NIP',
            'KONTO',
            'FIN',
            'EXPORT',
            'ID_O',
            'KOD_O',
            'OPIS',
            'NETTO1',
            'NETTO2',
            'NETTO3',
            'NETTO4',
            'NETTO5',
            'VAT3',
            'VAT4',
            'VAT5',
            'ST5',
            'USLUGI',
            'PRODUKCJA',
            'ROZLICZONO',
            'PLATNOSC',
            'TERMIN',
            'BRUTTO',
            'ZAPLATA',
            'ID_FPP',
            'NR_FPP',
            'WARTOSC_Z',
            'CLO',
            'AKCYZA',
            'POD_IMP',
            'USER',
            'KAUCJA',
            'NETTO6',
            'NETTO7',
            'VAT6',
            'VAT7',
            'X1',
            'X2',
            'X3',
            'X4',
            'X5',
            'WARTOSC_S',
            'VAT_S',
        ];

        return array_merge($fields, $this->getTaxesOtherFields(true));
    }

    /**
     * Generate all FLAGA_ fields.
     *
     * @return array
     */
    protected function getTaxesFlagFields()
    {
        $fields = [];

        foreach (range(static::TAXES_START_NUMBER, static::TAXES_END_NUMBER) as $number) {
            $fields = array_merge($fields, $this->getTaxesFields($number, true, false));
        }

        return $fields;
    }

    /**
     * Generate all STAWKA_, NETTO_ and VAT_ fields and optionally FLAGA_ fields.
     *
     * @param bool $add_flag
     *
     * @return array
     */
    protected function getTaxesOtherFields($add_flag = false)
    {
        $fields = [];

        foreach (range(static::TAXES_START_NUMBER, static::TAXES_END_NUMBER) as $number) {
            $fields =
                array_merge($fields, $this->getTaxesFields($number, $add_flag, true));
        }

        return $fields;
    }

    /**
     * Get taxes fields.
     *
     * @param int $number
     * @param bool $include_flag_field
     * @param bool $include_other_fields
     *
     * @return array
     */
    protected function getTaxesFields($number, $include_flag_field, $include_other_fields)
    {
        $fields = [];

        if ($include_flag_field) {
            $fields[] = 'FLAGA_' . $number;
        }

        if ($include_other_fields) {
            $fields[] = 'STAWKA_' . $number;
            $fields[] = 'NETTO_' . $number;
            $fields[] = 'VAT_' . $number;
        }

        return $fields;
    }
}
