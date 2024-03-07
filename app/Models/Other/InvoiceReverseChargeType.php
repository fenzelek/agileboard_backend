<?php

namespace App\Models\Other;

class InvoiceReverseChargeType
{
    /**
     *In type.
     */
    const IN = 'in';

    /**
     * Out EU type.
     */
    const OUT_EU = 'out_eu';

    /**
     * Out EU with tax back type.
     */
    const OUT_EU_TAX_BACK = 'out_eu_tax_back';

    /**
     *  In EU type.
     */
    const IN_UE = 'in_eu';

    /**
     *  In EU triple type.
     */
    const IN_EU_TRIPLE = 'in_eu_triple';

    /**
     *  Tax payer is the customer type.
     */
    const CUSTOMER_TAX = 'customer_tax';

    /**
     *  Out type.
     */
    const OUT = 'out';

    /**
     *  Out NP type.
     */
    const OUT_NP = 'out_np';

    /**
     *  In EU tax payer is the customer type.
     */
    const IN_EU_CUSTOMER_TAX = 'in_eu_customer_tax';

    /**
     *  Out EU tax payer is the customer type.
     */
    const OUT_EU_CUSTOMER_TAX = 'out_eu_customer_tax';

    /**
     * Get all available statuses for margin procedures.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::IN,
            self::OUT,
            self::IN_UE,
            self::IN_EU_TRIPLE,
            self::OUT_EU,
            self::OUT_EU_TAX_BACK,
            self::CUSTOMER_TAX,
            self::OUT_NP,
            self::IN_EU_CUSTOMER_TAX,
            self::OUT_EU_CUSTOMER_TAX,
        ];
    }
}
