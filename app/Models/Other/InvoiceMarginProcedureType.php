<?php

namespace App\Models\Other;

class InvoiceMarginProcedureType
{
    /**
     * Used product procedure type.
     */
    const USED_PRODUCT = 'used_product';

    /**
     * Tour operator procedure type.
     */
    const TOUR_OPERATOR = 'tour_operator';

    /**
     * Work of art procedure type.
     */
    const ART = 'art';

    /**
     *  Antique procedure type.
     */
    const ANTIQUE = 'antique';

    /**
     * Get all available statuses for margin procedures.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::USED_PRODUCT,
            self::TOUR_OPERATOR,
            self::ART,
            self::ANTIQUE,
        ];
    }
}
