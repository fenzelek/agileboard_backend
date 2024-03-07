<?php

namespace App\Models\Other;

class InvoiceTypeStatus
{
    /**
     * Regular invoice type.
     */
    const VAT = 'vat';

    /**
     * Correction invoice type.
     */
    const CORRECTION = 'correction';

    /**
     * Proforma invoice type.
     */
    const PROFORMA = 'proforma';

    /**
     * Margin invoice type.
     */
    const MARGIN = 'margin';

    /**
     * Margin correction invoice type.
     */
    const MARGIN_CORRECTION = 'margin_correction';

    /**
     * Reverse Charge invoice type.
     */
    const REVERSE_CHARGE = 'reverse_charge';

    /**
     * Reverse Charge correction invoice type.
     */
    const REVERSE_CHARGE_CORRECTION = 'reverse_charge_correction';

    /**
     * Advance invoice type.
     */
    const ADVANCE = 'advance';

    /**
     * Advance correction invoice type.
     */
    const ADVANCE_CORRECTION = 'advance_correction';

    /**
     * Final invoice type.
     */
    const FINAL_ADVANCE = 'final_advance';

    /**
     * Get all available statuses for invoice type.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::VAT,
            self::CORRECTION,
            self::PROFORMA,
            self::MARGIN,
            self::MARGIN_CORRECTION,
            self::REVERSE_CHARGE,
            self::REVERSE_CHARGE_CORRECTION,
            self::ADVANCE,
            self::ADVANCE_CORRECTION,
            self::FINAL_ADVANCE,
        ];
    }
}
