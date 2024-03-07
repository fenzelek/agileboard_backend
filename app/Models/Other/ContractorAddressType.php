<?php

namespace App\Models\Other;

class ContractorAddressType
{
    /**
     * Delivery type.
     */
    const DELIVERY = 'delivery';

    /**
     * Get all available addresses type.
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::DELIVERY,
        ];
    }
}
