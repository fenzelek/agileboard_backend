<?php

namespace App\Modules\SaleReport\Traits;

trait PriceNormalize
{
    /**
     * Undo normalize amount from integer to decimal.
     *
     * @param $amount
     * @return float
     */
    public function undoNormalizeAmount($amount)
    {
        return denormalize_price($amount);
    }

    /**
     * Undo normalize price.
     *
     * @return float
     */
    public function undoNormalizePrice()
    {
        return $this->undoNormalizeAmount($this->amount);
    }
}
