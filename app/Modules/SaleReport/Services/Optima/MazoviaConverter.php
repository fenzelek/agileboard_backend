<?php

namespace App\Modules\SaleReport\Services\Optima;

class MazoviaConverter
{
    /**
     * Convert string from UTF-8 to Mazovia.
     *
     * @param $string
     *
     * @return string
     */
    public function fromUtf8($string)
    {
        return strtr($string, $this->conversionArray());
    }

    /**
     * Convert string from Mazowia to UTF-8.
     *
     * @param $string
     *
     * @return string
     */
    public function toUtf8($string)
    {
        return strtr($string, array_flip($this->conversionArray()));
    }

    /**
     * Get characters conversions array.
     *
     * @return array
     */
    protected function conversionArray()
    {
        return [
            chr(0xc4) . chr(0x85) => chr(0x86), // ą
            chr(0xc4) . chr(0x87) => chr(0x8D), // ć
            chr(0xc4) . chr(0x99) => chr(0x91), // ę
            chr(0xc5) . chr(0x82) => chr(0x92), // ł
            chr(0xc5) . chr(0x84) => chr(0xA4), // ń
            chr(0xc3) . chr(0xb3) => chr(0xA2), // ó
            chr(0xc5) . chr(0x9b) => chr(0x9E), // ś
            chr(0xc5) . chr(0xba) => chr(0xA6), // ź
            chr(0xc5) . chr(0xbc) => chr(0xA7), // ż
            chr(0xc4) . chr(0x84) => chr(0x8F), // Ą
            chr(0xc4) . chr(0x86) => chr(0x95), // Ć
            chr(0xc4) . chr(0x98) => chr(0x90), // Ę
            chr(0xc5) . chr(0x81) => chr(0x9C), // Ł
            chr(0xc5) . chr(0x83) => chr(0xA5), // Ń
            chr(0xc3) . chr(0x93) => chr(0xA3), // Ó
            chr(0xc5) . chr(0x9a) => chr(0x98), // Ś
            chr(0xc5) . chr(0xb9) => chr(0xA0), // Ź
            chr(0xc5) . chr(0xbb) => chr(0xA1), // Ż
        ];
    }
}
