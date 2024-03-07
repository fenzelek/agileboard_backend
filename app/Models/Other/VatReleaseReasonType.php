<?php

namespace App\Models\Other;

class VatReleaseReasonType
{
    /**
     * Company type.
     */
    const TYPE = 'type';

    /**
     * Income type.
     */
    const INCOME = 'income';

    /**
     * legal regulation type.
     */
    const LEGAL_REGULATION = 'legal_regulation';

    /**
     *  legal basis type.
     */
    const LEGAL_BASIS = 'legal_basis';

    /**
     * Get all available statuses for Vat release reasons.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::TYPE,
            self::INCOME,
            self::LEGAL_REGULATION,
            self::LEGAL_REGULATION,
        ];
    }
}
