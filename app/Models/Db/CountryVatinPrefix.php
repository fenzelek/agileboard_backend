<?php

namespace App\Models\Db;

class CountryVatinPrefix extends Model
{
    /**
     * Key for Poland country.
     */
    const KEY_POLAND = 'PL';

    protected $guarded = [];

    /**
     * Verify whether this prefix is for Poland.
     *
     * @return bool
     */
    public function isPoland()
    {
        return $this->key == static::KEY_POLAND;
    }

    /**
     * Verify whether this prefix is in Europe Union (including Poland).
     *
     * @return bool
     */
    public function inEuropeUnion()
    {
        return in_array($this->key, $this->getEuropeUnionKeys());
    }

    /**
     * Get keys for Europe Union countries.
     */
    protected function getEuropeUnionKeys()
    {
        return [
            'AT',
            'BE',
            'BG',
            'HR',
            'CY',
            'CZ',
            'DK',
            'EE',
            'FI',
            'FR',
            'GR',
            'ES',
            'NL',
            'IE',
            'LT',
            'LU',
            'LV',
            'MT',
            'DE',
            static::KEY_POLAND,
            'PT',
            'RO',
            'SK',
            'SI',
            'SE',
            'HU',
            'IT',
            'GB',
        ];
    }
}
