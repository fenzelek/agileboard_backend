<?php

namespace App\Models\Other;

use App\Models\Db\Company;

class InvoiceCorrectionType
{
    /**
     * Tax correction type.
     */
    const TAX = 'tax';

    /**
     * Price correction type.
     */
    const PRICE = 'price';

    /**
     * Quantity correction type.
     */
    const QUANTITY = 'quantity';

    public static function all(Company $company)
    {
        $types = [
            self::PRICE => 'Korekta wartości/ceny',
            self::QUANTITY => 'Korekta ilości',
        ];

        if ($company->isVatPayer()) {
            $types[self::TAX] = 'Korekta stawki VAT';
        }

        return $types;
    }
}
